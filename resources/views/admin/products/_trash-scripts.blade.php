<script>
    (function () {
        const form = document.getElementById('admin-products-trash-filters');
        if (!form) return;

        let submitTimer = null;
        let isSubmitting = false;

        const submitFilters = () => {
            if (isSubmitting) return;
            isSubmitting = true;
            form.requestSubmit ? form.requestSubmit() : form.submit();
        };

        const scheduleSubmit = (delay = 250) => {
            clearTimeout(submitTimer);
            submitTimer = setTimeout(submitFilters, delay);
        };

        const multiSelects = Array.from(document.querySelectorAll('[data-multi-select]'));

        const getMultiSelectByKey = (key) =>
            multiSelects.find((root) => root.dataset.multiSelectKey === key) || null;

        const updateSummary = (root) => {
            const summary = root.querySelector('[data-multi-select-summary]');
            const placeholder = root.dataset.placeholder || 'Seleccionar...';
            if (!summary) return;

            const checked = Array.from(root.querySelectorAll('[data-multi-select-option]:checked:not(:disabled)'));
            const labels = checked.map((input) => input.dataset.label).filter(Boolean);

            if (labels.length === 0) {
                summary.textContent = placeholder;
                summary.classList.add('text-muted');
                summary.classList.remove('text-text');
                return;
            }

            summary.classList.remove('text-muted');
            summary.classList.add('text-text');

            if (labels.length === 1) {
                summary.textContent = labels[0];
            } else if (labels.length <= 3) {
                summary.textContent = labels.join(', ');
            } else {
                summary.textContent = labels.length + ' seleccionadas';
            }
        };

        const syncDependentMultiSelect = (dependentRoot) => {
            const dependsOnKey = dependentRoot.dataset.dependsOn;
            if (!dependsOnKey) return false;

            const parent = getMultiSelectByKey(dependsOnKey);
            if (!parent) return false;

            const selectedGroups = Array.from(
                parent.querySelectorAll('[data-multi-select-option]:checked'),
            ).map((input) => String(input.value));

            const requiresParent = selectedGroups.length > 0;
            let changedSelection = false;
            let visibleCount = 0;

            dependentRoot.querySelectorAll('[data-multi-select-item]').forEach((item) => {
                const groupId = item.dataset.groupId;
                const matches = requiresParent && selectedGroups.includes(String(groupId));
                const option = item.querySelector('[data-multi-select-option]');

                item.classList.toggle('hidden', !matches);

                if (!matches && option?.checked) {
                    option.checked = false;
                    changedSelection = true;
                    item.setAttribute('aria-selected', 'false');
                }

                if (option) {
                    option.disabled = !matches;
                }

                if (matches) {
                    visibleCount += 1;
                }
            });

            const emptyHint = dependentRoot.querySelector('[data-multi-select-filtered-empty]');
            if (emptyHint) {
                emptyHint.textContent = requiresParent
                    ? 'No hay modelos para las marcas seleccionadas.'
                    : 'Selecciona una marca para ver modelos.';
                emptyHint.classList.toggle('hidden', visibleCount > 0);
            }

            const trigger = dependentRoot.querySelector('[data-multi-select-trigger]');
            if (trigger) {
                trigger.disabled = !requiresParent;
                trigger.classList.toggle('opacity-60', !requiresParent);
                trigger.classList.toggle('cursor-not-allowed', !requiresParent);
            }

            if (!requiresParent) {
                const panel = dependentRoot.querySelector('[data-multi-select-panel]');
                if (panel) panel.classList.add('hidden');
            }

            updateSummary(dependentRoot);

            return changedSelection;
        };

        multiSelects.forEach((root) => {
            const trigger = root.querySelector('[data-multi-select-trigger]');
            const panel = root.querySelector('[data-multi-select-panel]');

            if (!trigger || !panel) return;

            const close = () => {
                panel.classList.add('hidden');
                trigger.setAttribute('aria-expanded', 'false');
                root.classList.remove('z-50');
                root.classList.add('z-20');
            };

            const open = () => {
                multiSelects.forEach((other) => {
                    other.classList.remove('z-50');
                    other.classList.add('z-20');
                });
                root.classList.remove('z-20');
                root.classList.add('z-50');
                panel.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');
            };

            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                if (trigger.disabled) return;

                const isOpen = !panel.classList.contains('hidden');
                document.querySelectorAll('[data-multi-select-panel]').forEach((other) => {
                    if (other !== panel) other.classList.add('hidden');
                });
                document.querySelectorAll('[data-multi-select-trigger]').forEach((other) => {
                    if (other !== trigger) other.setAttribute('aria-expanded', 'false');
                });
                multiSelects.forEach((other) => {
                    if (other !== root) {
                        other.classList.remove('z-50');
                        other.classList.add('z-20');
                    }
                });

                if (isOpen) {
                    close();
                } else {
                    open();
                }
            });

            root.querySelectorAll('[data-multi-select-option]').forEach((input) => {
                input.addEventListener('change', () => {
                    const option = input.closest('[role="option"]');
                    if (option) {
                        option.setAttribute('aria-selected', input.checked ? 'true' : 'false');
                    }

                    updateSummary(root);

                    multiSelects
                        .filter((other) => other.dataset.dependsOn === root.dataset.multiSelectKey)
                        .forEach((dependent) => syncDependentMultiSelect(dependent));

                    scheduleSubmit(200);
                });

                input.addEventListener('click', (event) => {
                    event.stopPropagation();
                });
            });

            panel.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            document.addEventListener('click', () => close());
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') close();
            });

            updateSummary(root);
        });

        multiSelects
            .filter((root) => root.dataset.dependsOn)
            .forEach((root) => syncDependentMultiSelect(root));

        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.addEventListener('input', () => scheduleSubmit(450));
            searchInput.addEventListener('search', () => scheduleSubmit(0));
        }

        const statusSelect = document.getElementById('status');
        if (statusSelect) {
            statusSelect.addEventListener('change', () => scheduleSubmit(150));
        }
    })();

    (function () {
        const selectAll = document.getElementById('select-all-trashed-products');
        const checkboxes = () => Array.from(document.querySelectorAll('[data-product-checkbox]'));
        const bulkRestoreBtn = document.getElementById('bulk-restore-btn');
        const bulkRestoreCount = document.getElementById('bulk-restore-count');
        const bulkForceDeleteBtn = document.getElementById('bulk-force-delete-btn');
        const bulkForceDeleteCount = document.getElementById('bulk-force-delete-count');

        const selectedCheckboxes = () => checkboxes().filter((cb) => cb.checked);

        const syncSelectionUi = () => {
            const all = checkboxes();
            const selected = selectedCheckboxes();
            const count = selected.length;

            if (selectAll) {
                selectAll.checked = all.length > 0 && count === all.length;
                selectAll.indeterminate = count > 0 && count < all.length;
            }

            [bulkRestoreBtn, bulkForceDeleteBtn].forEach((btn) => {
                if (btn) btn.disabled = count === 0;
            });

            [bulkRestoreCount, bulkForceDeleteCount].forEach((el) => {
                if (!el) return;
                if (count > 0) {
                    el.textContent = '(' + count + ')';
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            });
        };

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                checkboxes().forEach((cb) => {
                    cb.checked = selectAll.checked;
                });
                syncSelectionUi();
            });
        }

        checkboxes().forEach((cb) => {
            cb.addEventListener('change', syncSelectionUi);
        });

        syncSelectionUi();

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

            const form = modal.querySelector('[data-confirm-form]');
            const extra = form?.querySelector('[data-confirm-extra-fields]');
            if (extra) {
                extra.innerHTML = '';
            }
        };

        const fillBulkIds = (extra, selected) => {
            if (!extra) return;
            extra.innerHTML = '';
            selected.forEach((cb) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                extra.appendChild(input);
            });
        };

        document.querySelectorAll('[data-confirm-modal]').forEach((modal) => {
            modal.querySelectorAll('[data-confirm-cancel], [data-confirm-overlay]').forEach((el) => {
                el.addEventListener('click', () => closeModal(modal));
            });

            modal.querySelector('[data-confirm-submit]')?.addEventListener('click', () => {
                modal.querySelector('[data-confirm-form]')?.submit();
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('[data-confirm-modal]:not(.hidden)').forEach((modal) => {
                closeModal(modal);
            });
        });

        document.querySelectorAll('[data-open-confirm]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                if (trigger.disabled) return;

                const modalId = trigger.getAttribute('data-open-confirm');
                const modal = document.getElementById(modalId);
                if (!modal) return;

                const form = modal.querySelector('[data-confirm-form]');
                const messageEl = modal.querySelector('[data-confirm-message]');
                const extra = form?.querySelector('[data-confirm-extra-fields]');
                const selected = selectedCheckboxes();

                if (modalId === 'single-force-delete-modal') {
                    const url = trigger.getAttribute('data-delete-url');
                    const message = trigger.getAttribute('data-delete-message');
                    if (form && url) {
                        form.action = url;
                    }
                    if (messageEl && message) {
                        messageEl.textContent = message;
                    }
                }

                if (modalId === 'bulk-restore-modal') {
                    if (selected.length === 0) return;
                    if (messageEl) {
                        messageEl.textContent = selected.length === 1
                            ? '¿Restaurar 1 producto seleccionado?'
                            : '¿Restaurar ' + selected.length + ' productos seleccionados?';
                    }
                    fillBulkIds(extra, selected);
                }

                if (modalId === 'bulk-force-delete-modal') {
                    if (selected.length === 0) return;
                    if (messageEl) {
                        messageEl.textContent = selected.length === 1
                            ? '¿Eliminar permanentemente 1 producto seleccionado? Solo es posible si no tiene pedidos.'
                            : '¿Eliminar permanentemente ' + selected.length + ' productos seleccionados? Los que tengan pedidos no se eliminarán.';
                    }
                    fillBulkIds(extra, selected);
                }

                openModal(modal);
            });
        });
    })();
</script>
