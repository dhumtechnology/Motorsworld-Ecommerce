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
        const section = document.querySelector('[data-product-images]');
        if (!section) return;

        const isImageFile = (file) => file && file.type && file.type.startsWith('image/');

        const setFilesOnInput = (input, files, append) => {
            const transfer = new DataTransfer();
            const incoming = Array.from(files).filter(isImageFile);

            if (append) {
                Array.from(input.files || []).forEach((file) => transfer.items.add(file));
            }

            incoming.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
        };

        const renderPrimaryPreview = (input, container) => {
            container.innerHTML = '';
            const file = input.files?.[0];

            if (!file) {
                container.classList.add('hidden');
                return;
            }

            const url = URL.createObjectURL(file);
            container.classList.remove('hidden');
            container.innerHTML =
                '<div class="inline-flex items-center gap-3 rounded-lg border border-border bg-surface p-2">' +
                '<img src="' + url + '" alt="" class="h-16 w-16 rounded object-cover">' +
                '<div class="text-left"><p class="text-sm text-text font-semibold truncate max-w-[14rem]">' + file.name + '</p>' +
                '<button type="button" data-clear-primary class="mt-1 text-xs font-bold uppercase tracking-wide text-red-600 hover:text-red-300">Quitar</button></div></div>';

            container.querySelector('[data-clear-primary]')?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                input.value = '';
                renderPrimaryPreview(input, container);
            });
        };

        const renderSecondaryPreview = (input, container) => {
            container.innerHTML = '';
            const files = Array.from(input.files || []);

            if (files.length === 0) {
                container.classList.add('hidden');
                return;
            }

            container.classList.remove('hidden');

            files.forEach((file, index) => {
                const url = URL.createObjectURL(file);
                const card = document.createElement('div');
                card.className = 'relative overflow-hidden rounded-lg border border-border bg-surface';
                card.innerHTML =
                    '<img src="' + url + '" alt="" class="h-28 w-full object-cover">' +
                    '<button type="button" data-remove-index="' + index + '" class="absolute inset-x-0 bottom-0 bg-black/70 py-1.5 text-[11px] font-bold uppercase tracking-wide text-red-300 hover:text-red-200">Quitar</button>';
                container.appendChild(card);
            });

            container.querySelectorAll('[data-remove-index]').forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const removeIndex = Number(button.getAttribute('data-remove-index'));
                    const transfer = new DataTransfer();
                    Array.from(input.files || []).forEach((file, index) => {
                        if (index !== removeIndex) transfer.items.add(file);
                    });
                    input.files = transfer.files;
                    renderSecondaryPreview(input, container);
                });
            });
        };

        const setupDropzone = (type) => {
            const zone = section.querySelector('[data-dropzone="' + type + '"]');
            const input = section.querySelector('[data-file-input="' + type + '"]');
            const preview = section.querySelector('[data-preview="' + type + '"]');
            if (!zone || !input || !preview) return;

            let stashedFiles = [];

            const highlight = (on) => {
                zone.classList.toggle('border-primary', on);
                zone.classList.toggle('bg-primary-soft/20', on);
            };

            const refresh = () => {
                if (type === 'primary') renderPrimaryPreview(input, preview);
                else renderSecondaryPreview(input, preview);
            };

            zone.querySelector('[data-dropzone-trigger]')?.addEventListener('click', () => {
                if (type === 'secondary') stashedFiles = Array.from(input.files || []);
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
                const files = event.dataTransfer?.files;
                if (!files?.length) return;

                if (type === 'primary') setFilesOnInput(input, [files[0]], false);
                else setFilesOnInput(input, files, true);

                refresh();
            });

            input.addEventListener('change', () => {
                if (type === 'secondary' && stashedFiles.length > 0) {
                    const next = Array.from(input.files || []);
                    const transfer = new DataTransfer();
                    stashedFiles.concat(next).filter(isImageFile).forEach((file) => transfer.items.add(file));
                    input.files = transfer.files;
                    stashedFiles = [];
                }

                refresh();
            });
        };

        setupDropzone('primary');
        setupDropzone('secondary');
    })();
</script>
