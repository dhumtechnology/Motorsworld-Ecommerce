(function () {
    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function xsrfToken() {
        const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function updateBadge(itemCount) {
        const link = document.querySelector('[data-cart-icon]');
        if (!link) return;

        let badge = link.querySelector('[data-cart-badge]');

        if (itemCount <= 0) {
            badge?.remove();
            return;
        }

        if (!badge) {
            badge = document.createElement('span');
            badge.setAttribute('data-cart-badge', '');
            badge.className =
                'absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-orange-600 text-white text-[10px] font-black leading-[18px] text-center';
            link.appendChild(badge);
        }

        badge.textContent = itemCount > 99 ? '99+' : String(itemCount);
    }

    function formatMoney(amount, symbol = 'S/') {
        return String(symbol || 'S/') + ' ' + Number(amount).toLocaleString('es-PE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function cartDisplayCurrency(page) {
        return page?.dataset?.displayCurrency === 'USD' ? 'USD' : 'PEN';
    }

    function setCartDisplayCurrency(page, currency) {
        if (!page) return;
        page.dataset.displayCurrency = currency === 'USD' ? 'USD' : 'PEN';

        page.querySelectorAll('[data-cart-total-currency]').forEach((button) => {
            const active = button.dataset.cartTotalCurrency === page.dataset.displayCurrency;
            button.classList.toggle('bg-orange-600', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('text-neutral-600', !active);
        });

        const totalEl = page.querySelector('[data-cart-grand-total]');
        if (!totalEl) return;

        const isPen = page.dataset.displayCurrency !== 'USD';
        const amount = Number(totalEl.dataset[isPen ? 'pen' : 'usd'] || 0);
        totalEl.textContent = formatMoney(amount, isPen ? 'S/' : '$');
    }

    function computeCartTotals(page) {
        let totalPen = 0;
        let totalUsd = 0;

        page.querySelectorAll('[data-cart-line]').forEach((row) => {
            const qty = Number(row.querySelector('[data-line-qty]')?.textContent || 0);
            const unit = Number(row.dataset.unitPrice || 0);
            const amount = qty * unit;
            if (String(row.dataset.currency || 'PEN').toUpperCase() === 'USD') {
                totalUsd += amount;
            } else {
                totalPen += amount;
            }
        });

        const sellRate = Number(page.dataset.sellRate || 0);
        const hasRate = sellRate > 0;
        const grandPen = hasRate ? totalPen + totalUsd * sellRate : totalPen;
        const grandUsd = hasRate ? totalUsd + (totalPen > 0 ? totalPen / sellRate : 0) : totalUsd;

        return {
            totalPen: Math.round(totalPen * 100) / 100,
            totalUsd: Math.round(totalUsd * 100) / 100,
            grandPen: Math.round(grandPen * 100) / 100,
            grandUsd: Math.round(grandUsd * 100) / 100,
            hasRate,
        };
    }

    function renderCartTotals(page) {
        const totals = computeCartTotals(page);
        const penRow = page.querySelector('[data-subtotal-pen]');
        const usdRow = page.querySelector('[data-subtotal-usd]');
        const penAmount = page.querySelector('[data-subtotal-pen-amount]');
        const usdAmount = page.querySelector('[data-subtotal-usd-amount]');
        const totalEl = page.querySelector('[data-cart-grand-total]');

        if (penRow) penRow.classList.toggle('hidden', totals.totalPen <= 0);
        if (usdRow) usdRow.classList.toggle('hidden', totals.totalUsd <= 0);
        if (penAmount) penAmount.textContent = formatMoney(totals.totalPen, 'S/');
        if (usdAmount) usdAmount.textContent = formatMoney(totals.totalUsd, '$');

        if (totalEl) {
            totalEl.dataset.pen = String(totals.grandPen.toFixed(2));
            totalEl.dataset.usd = String(totals.grandUsd.toFixed(2));
        }

        setCartDisplayCurrency(page, cartDisplayCurrency(page));
    }

    function showError(root, message) {
        const alpineData = getAlpineData(root);
        if (alpineData && typeof alpineData.setCartError === 'function') {
            alpineData.setCartError(message);
            return;
        }

        const errorEl = root?.querySelector?.('[data-cart-error]') || document.querySelector('[data-cart-error]');
        if (!errorEl) return;
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
    }

    function clearError(root) {
        const alpineData = getAlpineData(root);
        if (alpineData && typeof alpineData.setCartError === 'function') {
            alpineData.setCartError('');
            return;
        }

        const errorEl = root?.querySelector?.('[data-cart-error]');
        errorEl?.classList.add('hidden');
        if (errorEl) errorEl.textContent = '';
    }

    async function cartRequest(url, method, body) {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        };

        const xsrf = xsrfToken();
        if (xsrf) {
            headers['X-XSRF-TOKEN'] = xsrf;
        }

        const options = {
            method,
            headers,
            credentials: 'same-origin',
            redirect: 'manual',
        };

        if (body) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        const response = await fetch(url, options);

        if (response.type === 'opaqueredirect' || response.status === 0 || (response.status >= 300 && response.status < 400)) {
            throw new Error('La sesión expiró. Recarga la página e inténtalo de nuevo.');
        }

        const contentType = response.headers.get('content-type') || '';
        const isJson = contentType.includes('application/json');
        const data = isJson ? await response.json().catch(() => ({})) : {};

        if (!response.ok || !isJson) {
            const message =
                data.message ||
                Object.values(data.errors || {}).flat()[0] ||
                (response.status === 419
                    ? 'La sesión expiró. Recarga la página e inténtalo de nuevo.'
                    : 'No se pudo actualizar el carrito.');
            throw new Error(message);
        }

        return data;
    }

    function updateCartPage(data, variantId) {
        const page = document.querySelector('[data-cart-page]');
        if (!page) return;

        const line = page.querySelector(`[data-cart-line][data-variant-id="${variantId}"]`);
        const quantity = Number(data.line_quantity || 0);

        if (line) {
            if (quantity <= 0) {
                line.remove();
            } else {
                const qtyEl = line.querySelector('[data-line-qty]');
                const totalEl = line.querySelector('[data-line-total]');
                const unitPrice = Number(line.dataset.unitPrice || 0);
                const maxStock = Number(line.dataset.maxStock || 0);
                const incrementBtn = line.querySelector('[data-cart-action="increment"]');

                if (qtyEl) qtyEl.textContent = String(quantity);
                if (totalEl) {
                    totalEl.textContent = formatMoney(
                        unitPrice * quantity,
                        line.dataset.currencySymbol || 'S/',
                    );
                }
                if (incrementBtn) incrementBtn.disabled = quantity >= maxStock;
            }
        }

        const remaining = page.querySelectorAll('[data-cart-line]');
        const summaryText = page.querySelector('[data-cart-summary-text]');
        const content = page.querySelector('[data-cart-content]');
        const empty = page.querySelector('[data-cart-empty]');

        renderCartTotals(page);

        if (summaryText) {
            const count = Number(data.item_count || 0);
            summaryText.textContent =
                count > 0
                    ? `${count} ${count === 1 ? 'producto' : 'productos'} en el carrito`
                    : 'Tu carrito está vacío';
        }

        if (remaining.length === 0) {
            content?.classList.add('hidden');
            empty?.classList.remove('hidden');
        } else {
            content?.classList.remove('hidden');
            empty?.classList.add('hidden');
        }
    }

    function getAlpineData(root) {
        try {
            const alpineRoot = root?.closest?.('[x-data]');
            if (!alpineRoot || !window.Alpine?.$data) return null;
            return window.Alpine.$data(alpineRoot);
        } catch (e) {
            return null;
        }
    }

    function resolveVariantId(root) {
        let variantId = Number(root.dataset.variantId || 0);
        if (variantId > 0) return variantId;

        const alpineData = getAlpineData(root);
        variantId = Number(alpineData?.selectedId || 0);
        if (variantId > 0) {
            root.dataset.variantId = String(variantId);
        }

        return variantId;
    }

    function readCartQuantity(root, variantId) {
        const alpineData = getAlpineData(root);
        if (alpineData) {
            if (Number(alpineData.selectedId) === Number(variantId) && alpineData.cartQty != null) {
                return Number(alpineData.cartQty || 0);
            }

            const variant = alpineData.variants?.find((item) => Number(item.id) === Number(variantId));
            if (variant) {
                return Number(variant.cart_quantity || 0);
            }
        }

        const qtyValue = root.querySelector('[data-cart-qty-value], [data-line-qty]');
        return qtyValue ? Number(qtyValue.textContent || 0) : 0;
    }

    function writeCartQuantity(root, variantId, quantity) {
        const alpineData = getAlpineData(root);
        if (alpineData && typeof alpineData.setCartQuantity === 'function') {
            alpineData.setCartQuantity(variantId, quantity);
            return;
        }

        if (alpineData?.variants?.length) {
            const variant = alpineData.variants.find((item) => Number(item.id) === Number(variantId));
            if (variant) {
                variant.cart_quantity = Number(quantity) || 0;
            }
            if (Number(alpineData.selectedId) === Number(variantId)) {
                alpineData.cartQty = Number(quantity) || 0;
            }
        }

        const qtyValue = root.querySelector('[data-cart-qty-value], [data-line-qty]');
        if (qtyValue) {
            qtyValue.textContent = String(Number(quantity) || 0);
        }
    }

    function setBusy(root, button, busy) {
        button.dataset.busy = busy ? '1' : '0';
        button.classList.toggle('opacity-60', busy);

        const alpineData = getAlpineData(root);
        if (alpineData && typeof alpineData.setCartBusy === 'function') {
            alpineData.setCartBusy(busy);
        }
    }

    async function handleAction(button) {
        const action = button.dataset.cartAction;
        const root =
            button.closest('[data-product-cart]') ||
            button.closest('[data-cart-line]') ||
            button.closest('[data-cart-page]');

        if (!root || button.dataset.busy === '1') return;

        const urls = {
            store: root.dataset.storeUrl,
            increment: root.dataset.incrementUrl,
            decrement: root.dataset.decrementUrl,
        };

        const url = urls[action];
        if (!url) return;

        const variantId = resolveVariantId(root);
        if (!variantId) {
            showError(root, 'Selecciona un color.');
            return;
        }

        setBusy(root, button, true);

        const previousQty = readCartQuantity(root, variantId);
        clearError(root);

        let optimisticQty = previousQty;
        if (action === 'store' || action === 'increment') {
            optimisticQty = previousQty + 1;
        } else if (action === 'decrement') {
            optimisticQty = Math.max(0, previousQty - 1);
        }

        if (root.hasAttribute('data-product-cart') || root.hasAttribute('data-cart-line')) {
            writeCartQuantity(root, variantId, optimisticQty);
        }

        try {
            const body = {
                product_variant_id: variantId,
                ...(action === 'store' ? { quantity: 1 } : {}),
            };
            const data = await cartRequest(url, 'POST', body);

            updateBadge(Number(data.item_count || 0));

            const lineQty = Number(data.line_quantity ?? optimisticQty);
            if (root.hasAttribute('data-product-cart') || root.hasAttribute('data-cart-line')) {
                writeCartQuantity(root, variantId, lineQty);
            }

            if (document.querySelector('[data-cart-page]')) {
                updateCartPage(data, Number(data.product_variant_id || variantId));
            }
        } catch (error) {
            if (root.hasAttribute('data-product-cart') || root.hasAttribute('data-cart-line')) {
                writeCartQuantity(root, variantId, previousQty);
            }

            showError(root, error.message || 'No se pudo actualizar el carrito.');
        } finally {
            setBusy(root, button, false);
        }
    }

    document.addEventListener('click', (event) => {
        const currencyButton = event.target.closest('[data-cart-total-currency]');
        if (currencyButton) {
            const page = currencyButton.closest('[data-cart-page]');
            if (page) {
                event.preventDefault();
                setCartDisplayCurrency(page, currencyButton.dataset.cartTotalCurrency);
            }
            return;
        }

        const button = event.target.closest('[data-cart-action]');
        if (!button) return;

        event.preventDefault();
        handleAction(button);
    });

    const cartPage = document.querySelector('[data-cart-page]');
    if (cartPage) {
        cartPage.dataset.displayCurrency = 'PEN';
        setCartDisplayCurrency(cartPage, 'PEN');
    }
})();
