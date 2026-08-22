<script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const formRoot = document.querySelector('[data-product-form]');

        const initSearchableSelect = (root) => {
            const trigger = root.querySelector('[data-ss-trigger]');
            const panel = root.querySelector('[data-ss-panel]');
            const search = root.querySelector('[data-ss-search]');
            const list = root.querySelector('[data-ss-list]');
            const empty = root.querySelector('[data-ss-empty]');
            const valueInput = root.querySelector('[data-ss-value]');
            const labelEl = root.querySelector('[data-ss-label]');
            if (!trigger || !panel || !search || !list || !valueInput || !labelEl) return;

            const options = () => Array.from(root.querySelectorAll('[data-ss-option]'));

            const open = () => {
                document.querySelectorAll('[data-searchable-select] [data-ss-panel]').forEach((other) => {
                    if (other !== panel) other.classList.add('hidden');
                });
                panel.classList.remove('hidden');
                search.value = '';
                filterOptions('');
                search.focus();
            };

            const close = () => panel.classList.add('hidden');

            const setValue = (value, label, silent) => {
                valueInput.value = value;
                labelEl.textContent = label;
                labelEl.classList.toggle('text-muted', value === '');
                labelEl.classList.toggle('text-text', value !== '');

                options().forEach((btn) => {
                    const active = btn.dataset.value === value;
                    btn.classList.toggle('bg-primary-soft', active);
                    btn.classList.toggle('text-primary', active);
                    btn.classList.toggle('font-semibold', active);
                });

                if (!silent) {
                    valueInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };

            const isOptionVisibleForFilter = (btn) => {
                const currentValue = valueInput.value || '';
                if (currentValue !== '' && btn.dataset.value === currentValue) {
                    return true;
                }

                const sourceId = root.dataset.filterSource;
                if (!sourceId) return true;

                const source = document.querySelector('[data-ss-id="' + sourceId + '"] [data-ss-value]');
                const sourceValue = source?.value || '';
                if (!sourceValue) {
                    return btn.dataset.value === '';
                }

                const filterValue = btn.dataset.filterValue || '';
                return btn.dataset.value === '' || filterValue === sourceValue;
            };

            const filterOptions = (query) => {
                const q = (query || '').trim().toLowerCase();
                let visible = 0;

                options().forEach((btn) => {
                    const matchesFilter = isOptionVisibleForFilter(btn);
                    const matchesQuery = !q || (btn.dataset.label || '').toLowerCase().includes(q);
                    const show = matchesFilter && matchesQuery;
                    btn.parentElement.classList.toggle('hidden', !show);
                    if (show) visible += 1;
                });

                if (empty) empty.classList.toggle('hidden', visible > 0);
            };

            root._ssSetValue = setValue;
            root._ssFilterOptions = filterOptions;
            root._ssAddOption = (value, label, filterValue) => {
                const li = document.createElement('li');
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('data-ss-option', '');
                btn.dataset.value = String(value);
                btn.dataset.label = label;
                if (filterValue !== undefined && filterValue !== null) {
                    btn.dataset.filterValue = String(filterValue);
                }
                btn.className = 'block w-full px-3 py-2 text-left text-sm text-text hover:bg-secondary';
                btn.textContent = label;
                btn.addEventListener('click', () => {
                    setValue(String(value), label, false);
                    close();
                });
                li.appendChild(btn);

                const emptyOption = options().find((opt) => opt.dataset.value === '');
                if (emptyOption?.parentElement) {
                    list.insertBefore(li, emptyOption.parentElement.nextSibling);
                } else {
                    list.prepend(li);
                }

                filterOptions(search.value);
            };

            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                if (trigger.disabled) return;
                if (panel.classList.contains('hidden')) open();
                else close();
            });

            search.addEventListener('input', () => filterOptions(search.value));

            options().forEach((btn) => {
                btn.addEventListener('click', () => {
                    setValue(btn.dataset.value || '', btn.dataset.label || '', false);
                    close();
                });
            });

            document.addEventListener('click', (event) => {
                if (!root.contains(event.target)) close();
            });

            filterOptions('');
        };

        document.querySelectorAll('[data-searchable-select]').forEach(initSearchableSelect);

        const brandSelect = document.querySelector('[data-ss-id="brand"] [data-ss-value]');
        const modelRoot = document.querySelector('[data-ss-id="model"]');

        const syncModelFilter = (resetInvalid) => {
            if (!modelRoot?._ssFilterOptions) return;
            modelRoot._ssFilterOptions('');

            const brandId = brandSelect?.value || '';
            const modelValue = modelRoot.querySelector('[data-ss-value]');
            const selectedBtn = modelRoot.querySelector('[data-ss-option][data-value="' + CSS.escape(modelValue?.value || '') + '"]');
            const stillValid = !modelValue?.value
                || !brandId
                || selectedBtn?.dataset.filterValue === brandId;

            if (resetInvalid && !stillValid && modelRoot._ssSetValue) {
                modelRoot._ssSetValue('', 'Sin modelo', true);
            }
        };

        brandSelect?.addEventListener('change', () => syncModelFilter(true));
        syncModelFilter(false);

        const openModal = (modal) => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        };

        const closeModal = (modal) => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
            const form = modal.querySelector('[data-quick-form]');
            const error = modal.querySelector('[data-quick-error]');
            form?.reset();
            if (error) {
                error.textContent = '';
                error.classList.add('hidden');
            }
        };

        document.querySelectorAll('[data-open-quick-modal]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const modal = document.getElementById(btn.getAttribute('data-open-quick-modal'));
                if (!modal) return;

                if (modal.id === 'model-modal') {
                    const brandId = brandSelect?.value || '';
                    const select = modal.querySelector('[data-model-modal-brand]');
                    if (select && brandId) select.value = brandId;
                }

                openModal(modal);
                modal.querySelector('input[name="name"]')?.focus();
            });
        });

        document.querySelectorAll('[data-quick-modal]').forEach((modal) => {
            modal.querySelectorAll('[data-quick-cancel], [data-quick-overlay]').forEach((el) => {
                el.addEventListener('click', () => closeModal(modal));
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('[data-quick-modal]:not(.hidden)').forEach((modal) => closeModal(modal));
        });

        const firstValidationError = (payload) => {
            if (!payload?.errors) return payload?.message || 'No se pudo guardar.';
            const first = Object.values(payload.errors)[0];
            return Array.isArray(first) ? first[0] : String(first);
        };

        const appendBrandToModelModal = (id, name) => {
            const select = document.querySelector('[data-model-modal-brand]');
            if (!select) return;
            if (Array.from(select.options).some((opt) => opt.value === String(id))) return;
            const option = document.createElement('option');
            option.value = String(id);
            option.textContent = name;
            select.appendChild(option);
        };

        document.querySelectorAll('[data-quick-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const type = form.getAttribute('data-quick-form');
                const modal = form.closest('[data-quick-modal]');
                const errorEl = modal?.querySelector('[data-quick-error]');
                const submitBtn = form.querySelector('button[type="submit"]');

                const urls = {
                    category: formRoot?.dataset.categoriesStoreUrl,
                    brand: formRoot?.dataset.brandsStoreUrl,
                    model: formRoot?.dataset.modelsStoreUrl,
                };

                const url = urls[type];
                if (!url) return;

                if (errorEl) {
                    errorEl.classList.add('hidden');
                    errorEl.textContent = '';
                }

                submitBtn.disabled = true;

                try {
                    const body = new FormData(form);
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body,
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(firstValidationError(payload));
                    }

                    if (type === 'category') {
                        const root = document.querySelector('[data-ss-id="category"]');
                        root?._ssAddOption?.(payload.id, payload.name);
                        root?._ssSetValue?.(String(payload.id), payload.name, false);
                    }

                    if (type === 'brand') {
                        const root = document.querySelector('[data-ss-id="brand"]');
                        root?._ssAddOption?.(payload.id, payload.name);
                        root?._ssSetValue?.(String(payload.id), payload.name, false);
                        appendBrandToModelModal(payload.id, payload.name);
                        syncModelFilter(true);
                    }

                    if (type === 'model') {
                        const root = document.querySelector('[data-ss-id="model"]');
                        root?._ssAddOption?.(payload.id, payload.name, payload.brand_id);
                        const brandRoot = document.querySelector('[data-ss-id="brand"]');
                        if (brandRoot?._ssSetValue && payload.brand_id) {
                            const brandOption = brandRoot.querySelector('[data-ss-option][data-value="' + CSS.escape(String(payload.brand_id)) + '"]');
                            if (brandOption) {
                                brandRoot._ssSetValue(String(payload.brand_id), brandOption.dataset.label || '', true);
                            }
                        }
                        root?._ssSetValue?.(String(payload.id), payload.name, false);
                        syncModelFilter(false);
                    }

                    closeModal(modal);
                } catch (error) {
                    if (errorEl) {
                        errorEl.textContent = error.message || 'No se pudo guardar.';
                        errorEl.classList.remove('hidden');
                    }
                } finally {
                    submitBtn.disabled = false;
                }
            });
        });
    })();

    (function () {
        const section = document.querySelector('[data-product-technical-sheet]');
        if (!section) return;

        const zone = section.querySelector('[data-dropzone="technical"]');
        const input = section.querySelector('[data-file-input="technical"]');
        const preview = section.querySelector('[data-preview="technical"]');
        const empty = section.querySelector('[data-dropzone-empty]');
        if (!zone || !input || !preview) return;

        const highlight = (on) => {
            zone.classList.toggle('border-primary', on);
            zone.classList.toggle('bg-primary-soft/20', on);
        };

        const renderPreview = () => {
            const file = input.files?.[0];
            preview.innerHTML = '';

            if (!file) {
                preview.classList.add('hidden');
                empty?.classList.remove('hidden');
                return;
            }

            empty?.classList.add('hidden');
            preview.classList.remove('hidden');
            preview.innerHTML =
                '<div class="inline-flex items-center gap-3 rounded-lg border border-border bg-white px-3 py-2 text-left">' +
                '<div class="flex h-10 w-10 items-center justify-center rounded bg-red-50 text-red-600 shrink-0">' +
                '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5"/></svg>' +
                '</div>' +
                '<div class="min-w-0"><p class="text-sm font-semibold text-text truncate max-w-[16rem]">' + file.name + '</p>' +
                '<p class="text-xs text-muted">' + Math.max(1, Math.round(file.size / 1024)) + ' KB</p></div>' +
                '<button type="button" data-clear-tech class="ml-2 text-xs font-bold uppercase tracking-wide text-red-600 hover:underline">Quitar</button></div>';

            preview.querySelector('[data-clear-tech]')?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                input.value = '';
                renderPreview();
            });
        };

        zone.addEventListener('click', (event) => {
            if (event.target.closest('[data-clear-tech]')) return;
            if (event.target.closest('[data-dropzone-trigger]') || event.target === zone || empty?.contains(event.target) || preview.contains(event.target)) {
                if (!event.target.closest('[data-clear-tech]')) input.click();
            }
        });

        zone.querySelector('[data-dropzone-trigger]')?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            input.click();
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            zone.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                highlight(true);
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            zone.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                highlight(false);
            });
        });

        zone.addEventListener('drop', (event) => {
            const file = Array.from(event.dataTransfer?.files || []).find(
                (item) => item.type === 'application/pdf' || /\.pdf$/i.test(item.name)
            );
            if (!file) return;

            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            renderPreview();
        });

        input.addEventListener('change', renderPreview);
    })();

    window.productVariantsForm = function (config) {
        const isImageFile = (file) => file && file.type && file.type.startsWith('image/');
        const pendingKey = () => 'p-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
        const normalizeName = (value) => String(value || '').trim().toLowerCase();

        const toExistingGalleryItem = (img) => ({
            key: 'e-' + img.id,
            type: 'existing',
            id: Number(img.id),
            path: img.path,
        });

        const toPendingGalleryItem = (file) => ({
            key: pendingKey(),
            type: 'pending',
            file,
            name: file.name,
            url: URL.createObjectURL(file),
        });

        const buildGalleryItems = (images) => (images || []).map(toExistingGalleryItem);

        const syncInputFiles = (input, pendingList) => {
            if (!input) return;
            const transfer = new DataTransfer();
            (pendingList || []).forEach((item) => {
                if (item?.file) transfer.items.add(item.file);
            });
            input.files = transfer.files;
        };

        const mapVariant = (v, i) => ({
            _key: v.id ? 'v-' + v.id : 'n-' + i + '-' + Date.now(),
            id: v.id || null,
            sku: v.sku || '',
            color_ids: (v.color_ids || []).map(String),
            new_colors: Array.isArray(v.new_colors) ? v.new_colors.filter((c) => (c.name || '').trim()) : [],
            available_stock: Number(v.available_stock || 0),
            galleryItems: buildGalleryItems(v.images || []),
            remove_image_ids: Array.isArray(v.remove_image_ids)
                ? v.remove_image_ids.map(Number)
                : [],
            dropActive: false,
            colorQuery: '',
            colorPickerOpen: false,
            newColorHex: '#FF6600',
        });

        const initialVariants = (config.variants || []).map(mapVariant);
        const initialMode = config.mode === 'variants' ? 'variants' : 'unique';

        return {
            productMode: initialMode,
            variants: initialMode === 'variants'
                ? (initialVariants.length ? initialVariants : [mapVariant({ available_stock: config.defaultStock || 0 }, 0)])
                : initialVariants,
            availableColors: config.colors || [],
            defaultGalleryItems: buildGalleryItems(config.defaultImages || []),
            defaultRemoveImageIds: [],
            defaultDropActive: false,
            defaultStock: Number(config.defaultStock || 0),
            removedVariantIds: [],
            initialColoredVariantIds: (config.coloredVariantIds || []).map(Number),
            draggingGalleryKey: null,
            dragOverGalleryKey: null,
            init() {
                const form = this.$el.closest('form');
                form?.addEventListener('submit', (event) => {
                    if (form.dataset.submitting === '1') {
                        event.preventDefault();
                        return;
                    }

                    if (!this.validateBeforeSubmit()) {
                        event.preventDefault();
                        return;
                    }

                    this.syncAllImageInputs();
                    form.dataset.submitting = '1';

                    form.querySelectorAll('button[type="submit"]').forEach((btn) => {
                        btn.disabled = true;
                        btn.classList.add('opacity-60', 'cursor-wait');
                        if (!btn.dataset.originalLabel) {
                            btn.dataset.originalLabel = btn.textContent.trim();
                        }
                        btn.textContent = 'Guardando…';
                    });
                });
            },
            setMode(mode) {
                if (mode === this.productMode) return;
                this.productMode = mode;
                if (mode === 'variants' && this.variants.length === 0) {
                    this.addVariant(true);
                }
            },
            validateBeforeSubmit() {
                if (this.productMode !== 'variants') return true;
                if (this.variants.length === 0) {
                    alert('Agrega al menos una variante.');
                    return false;
                }
                const invalid = this.variants.some((variant) => !this.hasColors(variant));
                if (invalid) {
                    alert('Cada variante debe tener al menos un color.');
                    return false;
                }
                return true;
            },
            visibleGalleryItems(items, removeIds) {
                return (items || []).filter((item) => {
                    if (item.type === 'pending') return true;
                    return !removeIds.includes(Number(item.id));
                });
            },
            galleryOrderTokens(items, removeIds) {
                const visible = this.visibleGalleryItems(items, removeIds);
                let newIndex = 0;

                return visible.map((item) => {
                    if (item.type === 'existing') {
                        return 'existing:' + item.id;
                    }

                    return 'new:' + (newIndex++);
                });
            },
            onGalleryDragStart(key) {
                this.draggingGalleryKey = key;
            },
            onGalleryDragEnd() {
                this.draggingGalleryKey = null;
                this.dragOverGalleryKey = null;
            },
            onGalleryDragOver(event, key) {
                event.preventDefault();
                if (this.draggingGalleryKey && this.draggingGalleryKey !== key) {
                    this.dragOverGalleryKey = key;
                }
            },
            onGalleryDrop(items, targetKey) {
                const fromKey = this.draggingGalleryKey;
                this.onGalleryDragEnd();

                if (!fromKey || fromKey === targetKey) return;

                const fromIndex = items.findIndex((item) => item.key === fromKey);
                const toIndex = items.findIndex((item) => item.key === targetKey);

                if (fromIndex < 0 || toIndex < 0 || fromIndex === toIndex) return;

                const [moved] = items.splice(fromIndex, 1);
                items.splice(toIndex, 0, moved);
            },
            appendGalleryFiles(items, fileList) {
                Array.from(fileList || [])
                    .filter(isImageFile)
                    .forEach((file) => items.push(toPendingGalleryItem(file)));
            },
            syncGalleryInput(input, items, removeIds) {
                const pending = this.visibleGalleryItems(items, removeIds)
                    .filter((item) => item.type === 'pending');
                syncInputFiles(input, pending);
            },
            syncAllImageInputs() {
                if (this.productMode === 'unique') {
                    this.syncGalleryInput(
                        this.$refs.defaultImagesInput,
                        this.defaultGalleryItems,
                        this.defaultRemoveImageIds,
                    );
                    return;
                }

                this.variants.forEach((variant) => {
                    const root = this.$el.querySelector(`[data-variant-key="${variant._key}"]`);
                    const input = root?.querySelector('input[type="file"]');
                    this.syncGalleryInput(input, variant.galleryItems, variant.remove_image_ids);
                });
            },
            colorById(colorId) {
                const id = String(colorId);
                return this.availableColors.find((c) => String(c.id) === id) || null;
            },
            hasColors(variant) {
                return (variant.color_ids || []).length > 0 || (variant.new_colors || []).length > 0;
            },
            variantLabel(variant) {
                const names = (variant.color_ids || [])
                    .map((id) => this.colorById(id)?.name)
                    .filter(Boolean);
                const newNames = (variant.new_colors || [])
                    .map((c) => (c.name || '').trim())
                    .filter(Boolean);
                return [...names, ...newNames].join(' / ');
            },
            filteredColors(variant) {
                const q = normalizeName(variant.colorQuery);
                const selected = new Set((variant.color_ids || []).map(String));
                return (this.availableColors || []).filter((color) => {
                    if (selected.has(String(color.id))) return false;
                    if (!q) return true;
                    return normalizeName(color.name).includes(q);
                });
            },
            canCreateFromQuery(variant) {
                const name = (variant.colorQuery || '').trim();
                if (!name) return false;
                const q = normalizeName(name);
                const existsAvailable = (this.availableColors || []).some((c) => normalizeName(c.name) === q);
                const existsSelected = (variant.new_colors || []).some((c) => normalizeName(c.name) === q);
                const existsIdSelected = (variant.color_ids || []).some((id) => normalizeName(this.colorById(id)?.name) === q);
                return !existsAvailable && !existsSelected && !existsIdSelected;
            },
            selectExistingColor(variant, color) {
                const id = String(color.id);
                if (!variant.color_ids.includes(id)) variant.color_ids.push(id);
                variant.colorQuery = '';
                variant.colorPickerOpen = false;
            },
            createColorFromQuery(variant) {
                const name = (variant.colorQuery || '').trim();
                if (!name || !this.canCreateFromQuery(variant)) return;
                variant.new_colors.push({
                    name,
                    hex: variant.newColorHex || '#FF6600',
                });
                variant.colorQuery = '';
                variant.colorPickerOpen = false;
                variant.newColorHex = '#FF6600';
            },
            commitColorQuery(variant) {
                const q = normalizeName(variant.colorQuery);
                if (!q) return;
                const exact = (this.availableColors || []).find((c) => normalizeName(c.name) === q);
                if (exact && !variant.color_ids.includes(String(exact.id))) {
                    this.selectExistingColor(variant, exact);
                    return;
                }
                const partial = this.filteredColors(variant);
                if (partial.length === 1) {
                    this.selectExistingColor(variant, partial[0]);
                    return;
                }
                if (this.canCreateFromQuery(variant)) {
                    this.createColorFromQuery(variant);
                }
            },
            removeColorFromVariant(variant, colorId) {
                variant.color_ids = variant.color_ids.filter((c) => c !== String(colorId));
            },
            removeNewColor(variant, index) {
                variant.new_colors.splice(index, 1);
            },
            addVariant(fromModeSwitch = false) {
                const carryStock = fromModeSwitch || this.variants.length === 0
                    ? Number(this.defaultStock || 0)
                    : 0;
                const carryItems = (fromModeSwitch || this.variants.length === 0)
                    ? this.defaultGalleryItems.splice(0)
                    : [];

                this.variants.push({
                    _key: 'n-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7),
                    id: null,
                    sku: '',
                    color_ids: [],
                    new_colors: [],
                    available_stock: carryStock,
                    galleryItems: carryItems,
                    remove_image_ids: [],
                    dropActive: false,
                    colorQuery: '',
                    colorPickerOpen: false,
                    newColorHex: '#FF6600',
                });
            },
            removeVariant(index) {
                const variant = this.variants[index];
                if (variant?.id) {
                    this.removedVariantIds.push(variant.id);
                }
                this.variants.splice(index, 1);
            },
            removeDefaultGalleryItem(item) {
                if (item.type === 'existing') {
                    const id = Number(item.id);
                    if (!this.defaultRemoveImageIds.includes(id)) {
                        this.defaultRemoveImageIds.push(id);
                    }
                    return;
                }

                const index = this.defaultGalleryItems.findIndex((entry) => entry.key === item.key);
                if (index >= 0) {
                    const [removed] = this.defaultGalleryItems.splice(index, 1);
                    if (removed?.url) URL.revokeObjectURL(removed.url);
                }

                this.$nextTick(() => {
                    this.syncGalleryInput(
                        this.$refs.defaultImagesInput,
                        this.defaultGalleryItems,
                        this.defaultRemoveImageIds,
                    );
                });
            },
            removeVariantGalleryItem(variant, item) {
                if (item.type === 'existing') {
                    const id = Number(item.id);
                    if (!variant.remove_image_ids.includes(id)) {
                        variant.remove_image_ids.push(id);
                    }
                    return;
                }

                const index = variant.galleryItems.findIndex((entry) => entry.key === item.key);
                if (index >= 0) {
                    const [removed] = variant.galleryItems.splice(index, 1);
                    if (removed?.url) URL.revokeObjectURL(removed.url);
                }

                this.$nextTick(() => {
                    const root = this.$el.querySelector(`[data-variant-key="${variant._key}"]`);
                    const input = root?.querySelector('input[type="file"]');
                    this.syncGalleryInput(input, variant.galleryItems, variant.remove_image_ids);
                });
            },
            onDefaultDrop(event) {
                const types = Array.from(event.dataTransfer?.types || []);
                if (!types.includes('Files')) {
                    this.defaultDropActive = false;
                    return;
                }

                this.defaultDropActive = false;
                this.appendGalleryFiles(this.defaultGalleryItems, event.dataTransfer?.files);
                this.$nextTick(() => {
                    this.syncGalleryInput(
                        this.$refs.defaultImagesInput,
                        this.defaultGalleryItems,
                        this.defaultRemoveImageIds,
                    );
                });
            },
            onDefaultInputChange(event) {
                this.appendGalleryFiles(this.defaultGalleryItems, event.target.files);
                event.target.value = '';
                this.$nextTick(() => {
                    this.syncGalleryInput(
                        this.$refs.defaultImagesInput,
                        this.defaultGalleryItems,
                        this.defaultRemoveImageIds,
                    );
                });
            },
            onVariantDrop(variant, event) {
                const types = Array.from(event.dataTransfer?.types || []);
                if (!types.includes('Files')) {
                    variant.dropActive = false;
                    return;
                }

                variant.dropActive = false;
                this.appendGalleryFiles(variant.galleryItems, event.dataTransfer?.files);
                this.$nextTick(() => {
                    const root = this.$el.querySelector(`[data-variant-key="${variant._key}"]`);
                    const input = root?.querySelector('input[type="file"]');
                    this.syncGalleryInput(input, variant.galleryItems, variant.remove_image_ids);
                });
            },
            onVariantInputChange(variant, event) {
                this.appendGalleryFiles(variant.galleryItems, event.target.files);
                event.target.value = '';
                this.$nextTick(() => {
                    const root = this.$el.querySelector(`[data-variant-key="${variant._key}"]`);
                    const input = root?.querySelector('input[type="file"]');
                    this.syncGalleryInput(input, variant.galleryItems, variant.remove_image_ids);
                });
            },
        };
    };

</script>
