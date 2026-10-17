/**
 * Confirmation and notice dialogs built on Bootstrap 6's <dialog>.
 * ---------------------------------------------------------------------------
 * Replaces SweetAlert2, which existed only to reimplement what the platform now
 * ships: a modal with a backdrop, focus trapping, Escape handling and a
 * top-layer stacking context.
 *
 * Every call site in this project wants the same small shape — a message, an
 * optional title, an icon, and confirm/cancel — so it maps onto one reusable
 * <dialog> rather than dozens of bespoke templates.
 *
 *   const { isConfirmed } = await confirmDialog({ ... })
 *
 * Colour comes from the project's theme tokens, never a literal. `icon` picks
 * the tone, and the tone names the Bootstrap theme class on the confirm button
 * (`theme-danger`, `theme-warning`, …), so a destructive confirm is red
 * because the palette says danger is red, not because a hex was hardcoded.
 */

import { Dialog } from 'bootstrap';

/** Tone → icon glyph. */
const ICONS = {
    warning: 'alert-triangle',
    error: 'x-circle',
    success: 'circle-check',
    info: 'info',
    question: 'help-circle',
};

/** Tone → Bootstrap theme suffix used on the icon and the confirm button. */
const TONES = {
    warning: 'warning',
    error: 'danger',
    success: 'success',
    info: 'info',
    question: 'primary',
};

/** Bootstrap dialog size modifier → the class it expects. */
const SIZES = {
    sm: 'dialog-sm',
    md: 'dialog-md',
    lg: 'dialog-lg',
};

/**
 * Only one dialog may be open at a time: a native <dialog> in the top layer
 * makes anything behind it inert, so a second one would be unclickable. Calls
 * arriving while another is open queue behind it.
 */
let chain = Promise.resolve();

/**
 * Show a confirmation dialog and resolve once it closes.
 *
 * @param {object} [options]
 * @param {string} [options.title]
 * @param {string} [options.text]        Body message. Ignored when `html` is set.
 * @param {string} [options.html]        Body HTML, when text is not enough.
 * @param {'warning'|'error'|'success'|'info'|'question'} [options.icon='warning']
 * @param {boolean} [options.showCancelButton=false]
 * @param {boolean} [options.showConfirmButton=true] A notice sets this false.
 * @param {string} [options.confirmButtonText='OK']
 * @param {'primary'|'secondary'|'success'|'warning'|'danger'|'info'} [options.confirmTone]
 *        Overrides the confirm button's theme. Defaults to the tone `icon`
 *        implies — a destructive confirm passes 'danger' to get a red button
 *        while keeping a warning icon.
 * @param {string} [options.cancelButtonText='Cancel']
 * @param {number} [options.timer]       Auto-close delay in ms.
 * @param {'sm'|'md'|'lg'} [options.size='sm']
 * @returns {Promise<{isConfirmed: boolean}>}
 */
export function confirmDialog(options = {}) {
    const next = chain.then(
        () => openDialog(options),
        () => openDialog(options)
    );

    // Keep the chain alive even when a caller's dialog rejects.
    chain = next.catch(() => {});

    return next;
}

function openDialog(options = {}) {
    const {
        title,
        text,
        html,
        icon = 'warning',
        showCancelButton = false,
        showConfirmButton = true,
        confirmButtonText = 'OK',
        confirmTone,
        cancelButtonText = 'Cancel',
        timer,
        size = 'sm',
    } = options;

    return new Promise((resolve) => {
        const dialog = document.createElement('dialog');
        dialog.className = `dialog ${SIZES[size] ?? SIZES.sm}`;

        const tone = TONES[icon] ?? 'secondary';
        const iconName = ICONS[icon];

        // The confirm button takes its theme from the icon unless the caller
        // names one, so a destructive confirm can keep a warning glyph and
        // still read as red.
        const confirmTheme = confirmTone ?? tone;

        const footer =
            showCancelButton || showConfirmButton
                ? `<div class="dialog-footer">
                    ${
                        showCancelButton
                            ? `<button type="button" class="btn btn-outline" data-bs-dismiss="dialog">${escapeHtml(cancelButtonText)}</button>`
                            : ''
                    }
                    ${
                        showConfirmButton
                            ? `<button type="button" class="btn btn-solid theme-${confirmTheme}" data-confirm>${escapeHtml(confirmButtonText)}</button>`
                            : ''
                    }
                </div>`
                : '';

        // Built with innerHTML because the body may contain markup; every
        // interpolated value is caller-authored, never user input.
        dialog.innerHTML = `
            <div class="dialog-header">
                ${
                    title || iconName
                        ? `<div class="d-flex align-items-center gap-2">
                            ${iconName ? `<i class="dialog-icon dialog-icon-${tone}" data-lucide="${iconName}"></i>` : ''}
                            ${title ? `<h3 class="dialog-title mb-0">${escapeHtml(title)}</h3>` : ''}
                        </div>`
                        : ''
                }
                <button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="Close"></button>
            </div>
            <div class="dialog-body">
                ${html ?? `<p class="mb-0">${escapeHtml(text ?? '')}</p>`}
            </div>
            ${footer}
        `;

        document.body.append(dialog);

        const instance = Dialog.getOrCreateInstance(dialog);
        let settled = false;

        const finish = (isConfirmed) => {
            if (settled) return;
            settled = true;
            clearTimeout(closeTimer);

            // Attach the cleanup listener and hide BEFORE resolving. A native
            // <dialog> stays in the top layer while open, so a caller that
            // awaits the promise and opens another dialog immediately would
            // otherwise be blocked by the still-open one.
            // The hidden event can fire before the listener attaches when there
            // is no exit transition to wait on, so also sweep on a timer as a
            // backstop.
            dialog.addEventListener('hidden.bs.dialog', cleanup, { once: true });
            setTimeout(cleanup, 500);

            instance.hide();

            resolve({ isConfirmed });
        };

        const closeTimer = timer ? setTimeout(() => finish(false), timer) : null;

        function cleanup() {
            if (!dialog.isConnected) return;
            // close() is a no-op once already closed, so this is safe to repeat.
            dialog.close();
            dialog.remove();
        }

        dialog.querySelector('[data-confirm]')?.addEventListener('click', () => finish(true));

        // Every dismiss control has to be bound, not just the first: the header
        // close button and the footer's Cancel both carry data-bs-dismiss, and
        // querySelector returns only the header one. Leaving Cancel unbound
        // meant it only ever closed by way of Bootstrap's data-api.
        for (const control of dialog.querySelectorAll('[data-bs-dismiss]')) {
            control.addEventListener('click', () => finish(false));
        }

        // Escape or backdrop dismiss both count as a cancellation.
        dialog.addEventListener('close', () => finish(false), { once: true });

        instance.show();

        // Focus the confirming action so Enter does the expected thing. A
        // notice has no confirm button, so focus falls back to the dialog.
        queueMicrotask(() => {
            const target = dialog.querySelector('[data-confirm]') ?? dialog.querySelector('.btn-close');
            target?.focus();
        });
    });
}

function escapeHtml(value) {
    return String(value).replace(
        /[&<>"']/g,
        (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]
    );
}

window.confirmDialog = confirmDialog;
