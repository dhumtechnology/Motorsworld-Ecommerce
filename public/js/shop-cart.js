(function () {
    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function xsrfToken() {
        const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function updateBadge(itemCount) {
        document.querySelectorAll('[data-cart-icon]').forEach((link) => {
            const mark = link.querySelector('[data-cart-icon-mark]') || link;
            let badge = link.querySelector('[data-cart-badge]');

            if (itemCount <= 0) {
                badge?.remove();
                return;
            }

            if (!badge) {
                badge = document.createElement('span');
                badge.setAttribute('data-cart-badge', '');
                badge.className =
                    'absolute -left-3 -top-2 min-w-[16px] h-4 px-1 rounded-full bg-orange-600 text-white text-[9px] font-black leading-4 text-center';
                mark.appendChild(badge);
            }

            badge.textContent = itemCount > 99 ? '99+' : String(itemCount);
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function moneyText(amount, symbol) {
        return `${escapeHtml(symbol || 'S/')} ${Number(amount || 0).toLocaleString('es-PE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;
    }

    function catalogUrl() {
        return document.querySelector('[data-cart-drawer]')?.dataset?.catalogUrl || '/catalogo';
    }

    function drawerLineHtml(item) {
        const quantity = Number(item.quantity || 0);
        const maxQuantity = Number(item.max_quantity || 0);
        const incrementDisabled = quantity >= maxQuantity ? 'disabled' : '';
        const image = item.image
            ? `<img src="${escapeHtml(item.image)}" alt="" class="h-full w-full object-cover" loading="lazy">`
            : '';
        const color = item.color
            ? `<p class="mt-1 text-[11px] text-neutral-500">${escapeHtml(item.color)}</p>`
            : '';
        const sale = item.is_on_sale
            ? `<p class="text-[11px] text-neutral-400 line-through">${moneyText(
                  item.list_line_total,
                  item.currency_symbol,
              )}</p>`
            : '';

        return `
            <li
                class="flex gap-3 py-4 first:pt-0 last:pb-0"
                data-cart-line
                data-product-id="${escapeHtml(item.product_id)}"
                data-variant-id="${escapeHtml(item.product_variant_id)}"
                data-max-stock="${escapeHtml(maxQuantity)}"
                data-increment-url="${escapeHtml(item.increment_url)}"
                data-decrement-url="${escapeHtml(item.decrement_url)}"
                data-remove-url="${escapeHtml(item.remove_url)}"
            >
                <a href="${escapeHtml(item.url)}" data-cart-drawer-close class="h-20 w-20 shrink-0 overflow-hidden rounded bg-neutral-100">
                    ${image}
                </a>
                <div class="min-w-0 flex-1">
                    <a href="${escapeHtml(item.url)}" data-cart-drawer-close class="line-clamp-2 text-sm font-bold uppercase leading-snug text-neutral-900 hover:text-orange-600 transition-colors">
                        ${escapeHtml(item.name)}
                    </a>
                    ${color}
                    <div class="relative z-10 mt-2 flex items-center gap-2">
                        <div class="flex h-9 w-32 select-none items-center overflow-hidden rounded-sm border border-neutral-700 bg-white">
                            <button
                                type="button"
                                data-cart-action="decrement"
                                class="flex h-full w-10 cursor-pointer items-center justify-center bg-white text-lg font-black text-[#f15a24] hover:bg-neutral-100 focus:outline-none"
                                aria-label="Quitar una unidad"
                            >−</button>
                            <div class="flex h-full w-12 items-center justify-center bg-[#f15a24] text-sm font-black text-white">
                                <span data-line-qty>${quantity}</span>
                            </div>
                            <button
                                type="button"
                                data-cart-action="increment"
                                ${incrementDisabled}
                                class="flex h-full w-10 cursor-pointer items-center justify-center bg-white text-lg font-black text-[#f15a24] hover:bg-neutral-100 focus:outline-none disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Añadir una unidad"
                            >+</button>
                        </div>
                        <button
                            type="button"
                            data-cart-action="remove"
                            data-remove-url="${escapeHtml(item.remove_url)}"
                            class="inline-flex h-9 w-9 items-center justify-center rounded text-neutral-400 hover:bg-red-50 hover:text-red-600 focus:outline-none"
                            aria-label="Eliminar del carrito"
                            title="Eliminar"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-sm font-black text-neutral-900" data-line-total>
                        ${moneyText(item.line_total, item.currency_symbol)}
                    </p>
                    ${sale}
                </div>
            </li>
        `;
    }

    function updateCartDrawer(data) {
        const panel = document.querySelector('[data-cart-drawer-panel]');
        if (!panel) return;

        const items = Array.isArray(data.items) ? data.items : [];
        const itemCount = Number(data.item_count || 0);
        const summary = panel.querySelector('[data-cart-drawer-summary]');
        const body = panel.querySelector('[data-cart-drawer-body]');
        const totalWrap = panel.querySelector('[data-cart-drawer-total]');
        const totalAmount = panel.querySelector('[data-cart-drawer-total-amount]');
        const payBtn = panel.querySelector('[data-cart-drawer-pay]');

        if (summary) {
            summary.textContent =
                itemCount > 0
                    ? `${itemCount} ${itemCount === 1 ? 'producto' : 'productos'}`
                    : 'Vacío';
        }

        if (body) {
            if (items.length === 0) {
                body.innerHTML = `
                    <div class="flex h-full min-h-[12rem] flex-col items-center justify-center px-4 text-center">
                        <p class="text-sm text-neutral-600">Aún no has agregado productos.</p>
                        <a
                            href="${escapeHtml(catalogUrl())}"
                            data-cart-drawer-close
                            class="mt-5 inline-flex rounded bg-orange-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-orange-500 transition-colors"
                        >
                            Ir al catálogo
                        </a>
                    </div>
                `;
            } else {
                body.innerHTML = `<ul class="flex flex-col divide-y divide-neutral-200">${items
                    .map((item) => drawerLineHtml(item))
                    .join('')}</ul>`;
            }
        }

        const hasItems = items.length > 0;
        const chargeAmount = data.charge_amount;

        if (totalWrap) {
            totalWrap.classList.toggle('hidden', !hasItems || chargeAmount == null);
        }
        if (totalAmount && hasItems && chargeAmount != null) {
            totalAmount.textContent = moneyText(chargeAmount, data.charge_currency_symbol);
        }

        if (payBtn) {
            payBtn.classList.toggle('pointer-events-none', !hasItems);
            payBtn.classList.toggle('bg-neutral-300', !hasItems);
            payBtn.classList.toggle('text-neutral-500', !hasItems);
            payBtn.classList.toggle('bg-orange-600', hasItems);
            payBtn.classList.toggle('text-white', hasItems);
            payBtn.classList.toggle('hover:bg-orange-500', hasItems);
            if (hasItems) {
                payBtn.removeAttribute('aria-disabled');
                payBtn.removeAttribute('tabindex');
                payBtn.setAttribute('data-cart-drawer-close', '');
            } else {
                payBtn.setAttribute('aria-disabled', 'true');
                payBtn.setAttribute('tabindex', '-1');
                payBtn.removeAttribute('data-cart-drawer-close');
            }
        }
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

        const errorEl =
            root?.querySelector?.('[data-cart-error]') ||
            root?.closest?.('[data-cart-drawer-panel]')?.querySelector('[data-cart-error]') ||
            document.querySelector('[data-cart-error]');
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

        const errorEl =
            root?.querySelector?.('[data-cart-error]') ||
            root?.closest?.('[data-cart-drawer-panel]')?.querySelector('[data-cart-error]');
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
            const productRoot = root?.closest?.('[data-product-cart]');
            if (!productRoot || !window.Alpine?.$data) {
                return null;
            }

            const alpineRoot = productRoot.closest('[x-data]') || productRoot;
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
            remove: button.dataset.removeUrl || root.dataset.removeUrl,
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
        } else if (action === 'remove') {
            optimisticQty = 0;
        }

        if (root.hasAttribute('data-product-cart') || root.hasAttribute('data-cart-line')) {
            writeCartQuantity(root, variantId, optimisticQty);
        }

        try {
            const body = {
                product_variant_id: variantId,
                ...(action === 'store' ? { quantity: 1 } : {}),
            };
            const method = action === 'remove' ? 'DELETE' : 'POST';
            const data = await cartRequest(url, method, body);

            updateBadge(Number(data.item_count || 0));
            updateCartDrawer(data);

            const lineQty = Number(data.line_quantity ?? optimisticQty);
            const resolvedVariantId = Number(data.product_variant_id || variantId);
            if (root.hasAttribute('data-product-cart') || root.hasAttribute('data-cart-line')) {
                writeCartQuantity(root, variantId, lineQty);
            }

            document.querySelectorAll('[data-product-cart]').forEach((productRoot) => {
                if (productRoot === root) return;
                writeCartQuantity(productRoot, resolvedVariantId, lineQty);
            });

            if (document.querySelector('[data-cart-page]')) {
                updateCartPage(data, resolvedVariantId);
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

    document.addEventListener(
        'click',
        (event) => {
            const button = event.target.closest('[data-cart-action]');
            if (button) {
                event.preventDefault();
                handleAction(button);
                return;
            }

            const closeTarget = event.target.closest('[data-cart-drawer-close]');
            if (closeTarget && closeTarget.closest('[data-cart-drawer-panel]')) {
                window.dispatchEvent(new CustomEvent('close-cart-drawer'));
            }

            const currencyButton = event.target.closest('[data-cart-total-currency]');
            if (currencyButton) {
                const page = currencyButton.closest('[data-cart-page]');
                if (page) {
                    event.preventDefault();
                    setCartDisplayCurrency(page, currencyButton.dataset.cartTotalCurrency);
                }
            }
        },
        true,
    );

    const cartPage = document.querySelector('[data-cart-page]');
    if (cartPage) {
        cartPage.dataset.displayCurrency = 'PEN';
        setCartDisplayCurrency(cartPage, 'PEN');
    }
})();
