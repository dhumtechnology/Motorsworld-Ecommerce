<script>
    (function () {
        const initSearchableSelect = (root) => {
            if (root.dataset.ssReady === '1') return;

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
            root.dataset.ssReady = '1';
        };

        document.querySelectorAll('[data-searchable-select]').forEach(initSearchableSelect);
        window.initAdminSearchableSelect = initSearchableSelect;
    })();
</script>
