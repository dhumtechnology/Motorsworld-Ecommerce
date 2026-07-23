@php
    $filterFormId = $filterFormId ?? null;
    $entityLabelSingular = $entityLabelSingular ?? 'elemento';
    $entityLabelPlural = $entityLabelPlural ?? 'elementos';
@endphp

<script>
    (function () {
        @if ($filterFormId)
            const form = document.getElementById(@json($filterFormId));
            if (form) {
                let submitTimer = null;
                let isSubmitting = false;

                const setHint = (text) => {
                    const hint = document.getElementById('filters-live-hint');
                    if (hint) hint.textContent = text;
                };

                const submitFilters = () => {
                    if (isSubmitting) return;
                    isSubmitting = true;
                    setHint('Actualizando resultados…');
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                };

                const scheduleSubmit = (delay = 250) => {
                    clearTimeout(submitTimer);
                    setHint('Aplicando filtros…');
                    submitTimer = setTimeout(submitFilters, delay);
                };

                const searchInput = document.getElementById('search');
                if (searchInput) {
                    searchInput.addEventListener('input', () => scheduleSubmit(450));
                    searchInput.addEventListener('search', () => scheduleSubmit(0));
                }

                form.querySelectorAll('select').forEach((select) => {
                    select.addEventListener('change', () => scheduleSubmit(150));
                });

                document.querySelectorAll('[data-multi-select]').forEach((root) => {
                    const trigger = root.querySelector('[data-multi-select-trigger]');
                    const panel = root.querySelector('[data-multi-select-panel]');
                    const summary = root.querySelector('[data-multi-select-summary]');
                    const placeholder = root.dataset.placeholder || 'Seleccionar...';

                    if (!trigger || !panel || !summary) return;

                    const close = () => {
                        panel.classList.add('hidden');
                        trigger.setAttribute('aria-expanded', 'false');
                    };

                    const updateSummary = () => {
                        const checked = Array.from(root.querySelectorAll('[data-multi-select-option]:checked'));
                        const labels = checked.map((input) => input.dataset.label).filter(Boolean);

                        if (labels.length === 0) {
                            summary.textContent = placeholder;
                            summary.classList.add('text-muted');
                            summary.classList.remove('text-text');
                            return;
                        }

                        summary.classList.remove('text-muted');
                        summary.classList.add('text-text');
                        summary.textContent = labels.length === 1
                            ? labels[0]
                            : (labels.length <= 3 ? labels.join(', ') : labels.length + ' seleccionadas');
                    };

                    trigger.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        const isOpen = !panel.classList.contains('hidden');
                        document.querySelectorAll('[data-multi-select-panel]').forEach((other) => {
                            if (other !== panel) other.classList.add('hidden');
                        });
                        if (isOpen) close();
                        else {
                            panel.classList.remove('hidden');
                            trigger.setAttribute('aria-expanded', 'true');
                        }
                    });

                    root.querySelectorAll('[data-multi-select-option]').forEach((input) => {
                        input.addEventListener('change', () => {
                            updateSummary();
                            scheduleSubmit(200);
                        });
                        input.addEventListener('click', (event) => event.stopPropagation());
                    });

                    panel.addEventListener('click', (event) => event.stopPropagation());
                    document.addEventListener('click', () => close());
                    updateSummary();
                });
            }
        @endif

        const selectAll = document.getElementById('select-all-items');
        const checkboxes = () => Array.from(document.querySelectorAll('[data-row-checkbox]'));
        const bulkBtn = document.getElementById('bulk-delete-btn');
        const bulkCount = document.getElementById('bulk-delete-count');
        const selectedCheckboxes = () => checkboxes().filter((cb) => cb.checked);
        const entitySingular = @json($entityLabelSingular);
        const entityPlural = @json($entityLabelPlural);

        const syncSelectionUi = () => {
            const all = checkboxes();
            const selected = selectedCheckboxes();
            const count = selected.length;

            if (selectAll) {
                selectAll.checked = all.length > 0 && count === all.length;
                selectAll.indeterminate = count > 0 && count < all.length;
            }

            if (bulkBtn) bulkBtn.disabled = count === 0;

            if (bulkCount) {
                if (count > 0) {
                    bulkCount.textContent = '(' + count + ')';
                    bulkCount.classList.remove('hidden');
                } else {
                    bulkCount.classList.add('hidden');
                }
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                checkboxes().forEach((cb) => { cb.checked = selectAll.checked; });
                syncSelectionUi();
            });
        }

        checkboxes().forEach((cb) => cb.addEventListener('change', syncSelectionUi));
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
            const extra = modal.querySelector('[data-confirm-extra-fields]');
            if (extra) extra.innerHTML = '';
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
            document.querySelectorAll('[data-confirm-modal]:not(.hidden)').forEach((modal) => closeModal(modal));
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

                if (modalId === 'single-delete-modal') {
                    const url = trigger.getAttribute('data-delete-url');
                    const message = trigger.getAttribute('data-delete-message');
                    if (form && url) form.action = url;
                    if (messageEl && message) messageEl.textContent = message;
                }

                if (modalId === 'bulk-delete-modal') {
                    const selected = selectedCheckboxes();
                    if (selected.length === 0) return;

                    if (messageEl) {
                        messageEl.textContent = selected.length === 1
                            ? '¿Eliminar 1 ' + entitySingular + ' seleccionado/a? Esta acción no se puede deshacer.'
                            : '¿Eliminar ' + selected.length + ' ' + entityPlural + ' seleccionados/as? Esta acción no se puede deshacer.';
                    }

                    if (extra) {
                        extra.innerHTML = '';
                        selected.forEach((cb) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'ids[]';
                            input.value = cb.value;
                            extra.appendChild(input);
                        });
                    }
                }

                openModal(modal);
            });
        });
    })();
</script>
