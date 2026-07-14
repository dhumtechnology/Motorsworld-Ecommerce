(function () {
    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

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

    function formatMoney(amount) {
        return 'S/ ' + Number(amount).toLocaleString('es-PE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    async function cartRequest(url, method, body) {
        const options = {
            method,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
        };

        if (body) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        const response = await fetch(url, options);
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const message =
                data.message ||
                Object.values(data.errors || {}).flat()[0] ||
                'No se pudo actualizar el carrito.';
            throw new Error(message);
        }

        return data;
    }

    function setProductMode(root, quantity) {
        const addBlock = root.querySelector('[data-cart-add]');
        const qtyBlock = root.querySelector('[data-cart-qty]');
        const qtyValue = root.querySelector('[data-cart-qty-value]');
        const incrementBtn = root.querySelector('[data-cart-action="increment"]');
        const maxStock = Number(root.dataset.maxStock || 0);

        if (quantity <= 0) {
            addBlock?.classList.remove('hidden');
            qtyBlock?.classList.add('hidden');
            return;
        }

        addBlock?.classList.add('hidden');
        qtyBlock?.classList.remove('hidden');

        if (qtyValue) {
            qtyValue.textContent = String(quantity);
        }

        if (incrementBtn) {
            incrementBtn.disabled = quantity >= maxStock;
        }
    }

    function updateCartPage(data, productId) {
        const page = document.querySelector('[data-cart-page]');
        if (!page) return;

        const line = page.querySelector(`[data-cart-line][data-product-id="${productId}"]`);
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
                if (totalEl) totalEl.textContent = formatMoney(unitPrice * quantity);
                if (incrementBtn) incrementBtn.disabled = quantity >= maxStock;
            }
        }

        const remaining = page.querySelectorAll('[data-cart-line]');
        const summaryText = page.querySelector('[data-cart-summary-text]');
        const totalEl = page.querySelector('[data-cart-grand-total]');
        const content = page.querySelector('[data-cart-content]');
        const empty = page.querySelector('[data-cart-empty]');

        let grandTotal = 0;
        remaining.forEach((row) => {
            const qty = Number(row.querySelector('[data-line-qty]')?.textContent || 0);
            const unit = Number(row.dataset.unitPrice || 0);
            grandTotal += qty * unit;
        });

        if (totalEl) {
            totalEl.textContent = formatMoney(grandTotal);
        }

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

        button.dataset.busy = '1';
        button.classList.add('opacity-60');

        const qtyValue = root.querySelector('[data-cart-qty-value], [data-line-qty]');
        const previousQty = qtyValue ? Number(qtyValue.textContent || 0) : 0;

        // Optimistic UI
        if (action === 'increment' && qtyValue) {
            qtyValue.textContent = String(previousQty + 1);
        } else if (action === 'decrement' && qtyValue && previousQty > 0) {
            qtyValue.textContent = String(previousQty - 1);
        } else if (action === 'store' && root.hasAttribute('data-product-cart')) {
            setProductMode(root, 1);
        }

        try {
            const body = action === 'store' ? { quantity: 1 } : null;
            const data = await cartRequest(url, 'POST', body);

            updateBadge(Number(data.item_count || 0));

            if (root.hasAttribute('data-product-cart')) {
                setProductMode(root, Number(data.line_quantity || 0));
            }

            if (document.querySelector('[data-cart-page]')) {
                updateCartPage(data, Number(data.product_id || root.dataset.productId));
            }
        } catch (error) {
            if (root.hasAttribute('data-product-cart')) {
                setProductMode(root, previousQty);
            } else if (qtyValue) {
                qtyValue.textContent = String(previousQty);
            }

            const errorEl = document.querySelector('[data-cart-error]');
            if (errorEl) {
                errorEl.textContent = error.message;
                errorEl.classList.remove('hidden');
            }
        } finally {
            button.dataset.busy = '0';
            button.classList.remove('opacity-60');
        }
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-cart-action]');
        if (!button) return;

        event.preventDefault();
        handleAction(button);
    });
})();
