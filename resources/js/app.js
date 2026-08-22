import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Evita doble envío en formularios de admin y tienda.
 *
 * - data-submit-lock="off"   → no aplica
 * - data-submit-lock="async" → se mantiene bloqueado aunque otro handler haga preventDefault
 *                              (p. ej. checkout Culqi). Desbloquear manualmente si falla.
 */
function submittersOf(form) {
    return Array.from(
        form.querySelectorAll('button:not([type]), button[type="submit"], input[type="submit"]'),
    );
}

function lockForm(form, submitter) {
    form.dataset.submitLocked = '1';
    form.setAttribute('aria-busy', 'true');

    const buttons = submittersOf(form);
    if (submitter instanceof HTMLElement && form.contains(submitter) && !buttons.includes(submitter)) {
        buttons.push(submitter);
    }

    buttons.forEach((btn) => {
        if (btn.disabled) {
            btn.dataset.wasDisabled = '1';
            return;
        }
        btn.disabled = true;
        btn.classList.add('is-submit-locked');
        btn.setAttribute('aria-busy', 'true');
    });
}

function unlockForm(form) {
    delete form.dataset.submitLocked;
    form.removeAttribute('aria-busy');

    submittersOf(form).forEach((btn) => {
        if (btn.dataset.wasDisabled === '1') {
            delete btn.dataset.wasDisabled;
            return;
        }
        btn.disabled = false;
        btn.classList.remove('is-submit-locked');
        btn.removeAttribute('aria-busy');
    });
}

window.unlockSubmitLock = unlockForm;

document.addEventListener(
    'submit',
    (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.dataset.submitLock === 'off') {
            return;
        }

        if (form.dataset.submitLocked === '1') {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        lockForm(form, event.submitter instanceof HTMLElement ? event.submitter : null);
    },
    true,
);

document.addEventListener(
    'submit',
    (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.dataset.submitLock === 'off' || form.dataset.submitLock === 'async') {
            return;
        }

        if (event.defaultPrevented && form.dataset.submitLocked === '1') {
            unlockForm(form);
        }
    },
    false,
);

window.addEventListener('pageshow', (event) => {
    if (!event.persisted) {
        return;
    }

    document.querySelectorAll('form[data-submit-locked="1"]').forEach((form) => {
        if (form instanceof HTMLFormElement) {
            unlockForm(form);
        }
    });
});

Alpine.start();
