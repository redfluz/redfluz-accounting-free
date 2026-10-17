import './bootstrap';

import { confirmDialog } from './confirm-dialog';
import { createIcons, icons } from 'lucide';
import { Datepicker } from 'bootstrap';

/**
 * Render every Lucide icon that is still an unconverted <i data-lucide>.
 *
 * createIcons() throws a warning and leaves the element untouched when the name
 * is not a real icon, which shows up as a silently blank button. Report the
 * unknown names once so a typo is obvious instead of invisible.
 */
const renderIcons = (root = document) => {
    try {
        createIcons({ icons, root });
    } catch (error) {
        console.error('[lucide] failed to render icons', error);
    }

    root.querySelectorAll('i[data-lucide]').forEach((node) => {
        const name = node.getAttribute('data-lucide');

        console.error(
            `[lucide] "${name}" is not a valid icon name; the element renders empty.`
        );
    });
};

window.lucide = {
    createIcons: (opts = {}) => renderIcons(opts.root ?? document),
};

// Initialize icons when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    renderIcons();

    // Initialize all Bootstrap 6 datepickers with YYYY-MM-DD format
    document.querySelectorAll('[data-bs-toggle="datepicker"]').forEach((element) => {
        if (!element.dataset.datepickerInitialized) {
            Datepicker.getOrCreateInstance(element, {
                dateFormat: (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                },
            });
            element.dataset.datepickerInitialized = 'true';
        }
    });

    // Markup injected after load (file previews, password toggle, dynamic
    // rows) never passes through createIcons(), so those icons would render as
    // empty elements. Convert them as they arrive.
    //
    // Rendering replaces each <i data-lucide> with an <svg>, which is itself an
    // added node, so the observer is disconnected while it works to avoid
    // re-entering on its own output.
    const observer = new MutationObserver((mutations) => {
        const added = mutations.some((mutation) => mutation.addedNodes.length > 0);

        if (!added) {
            return;
        }

        observer.disconnect();
        renderIcons();

        // Re-initialize datepickers for dynamically added elements
        document.querySelectorAll('[data-bs-toggle="datepicker"]:not([data-datepicker-initialized])').forEach((element) => {
            Datepicker.getOrCreateInstance(element, {
                dateFormat: (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                },
            });
            element.dataset.datepickerInitialized = 'true';
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });

    observer.observe(document.body, { childList: true, subtree: true });

    document.querySelectorAll('.purchase-document-tabs').forEach((tabs) => {
        const form = tabs.closest('form');
        if (!form) return;

        const workspace = document.createElement('div');
        workspace.className = 'purchase-tab-workspace';
        workspace.style.order = '2';
        tabs.insertAdjacentElement('afterend', workspace);

        const panels = new Map();
        tabs.querySelectorAll('[data-purchase-tab]').forEach((tab) => {
            const name = tab.dataset.purchaseTab;
            if (!name || panels.has(name)) return;

            const panel = document.createElement('div');
            panel.dataset.purchaseWorkspacePanel = name;
            form.querySelectorAll(`[data-purchase-panel="${name}"]`).forEach((section) => panel.append(section));
            workspace.append(panel);
            panels.set(name, panel);
        });

        const activate = (name) => {
            tabs.querySelectorAll('[data-purchase-tab]').forEach((tab) => {
                tab.classList.toggle('active', tab.dataset.purchaseTab === name);
            });
            panels.forEach((panel, panelName) => panel.classList.toggle('d-none', panelName !== name));
        };

        tabs.querySelectorAll('[data-purchase-tab]').forEach((tab) => {
            tab.addEventListener('click', () => activate(tab.dataset.purchaseTab));
        });
        activate(tabs.querySelector('[data-purchase-tab].active')?.dataset.purchaseTab ?? 'general');
    });
});

import {
    Chart,
    ArcElement,
    LineElement,
    BarElement,
    PointElement,
    LineController,
    BarController,
    DoughnutController,
    PieController,
    CategoryScale,
    LinearScale,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';

Chart.register(
    ArcElement,
    LineElement,
    BarElement,
    PointElement,
    LineController,
    BarController,
    DoughnutController,
    PieController,
    CategoryScale,
    LinearScale,
    Title,
    Tooltip,
    Legend,
    Filler
);

window.Chart = Chart;

import { Fancybox } from "@fancyapps/ui";
import "@fancyapps/ui/dist/fancybox/fancybox.css";

Fancybox.bind("[data-fancybox]", {});

import { initCombobox, initComboboxes, refreshComboboxes } from './select-combobox';

// Upgrade every select marked with data-combobox. Scoped to the form-partial
// helpers; selects repopulated later are bound by refreshComboboxes().
document.addEventListener('DOMContentLoaded', () => {
    // Bootstrap's Combobox also scans [data-bs-toggle="combobox"] on
    // DOMContentLoaded. Deferring to the next tick lets it finish first, so
    // getOrCreateInstance finds an existing instance instead of warning
    // "more than one instance per element".
    setTimeout(() => {
        initComboboxes();

        // Markup injected after load (dynamic line rows, chart loading) never
        // passes through initComboboxes(), so bind new selects as they arrive.
        const observer = new MutationObserver((mutations) => {
            if (!mutations.some((m) => m.addedNodes.length > 0)) return;
            observer.disconnect();
            initComboboxes();
            observer.observe(document.body, { childList: true, subtree: true });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
});

Object.assign(window, {
    initCombobox,
    initComboboxes,
    refreshComboboxes,
    confirmDialog,
});

/**
 * Confirmation dialogs for destructive actions.
 *
 * Replaces every native window.confirm() with a Bootstrap <dialog>. Any form or
 * button carrying data-confirm-message is intercepted on submit, so views
 * declare the message inline instead of writing their own dialog boilerplate.
 */
document.addEventListener('submit', function (event) {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const message = form.dataset.confirmMessage
        || (event.submitter instanceof HTMLElement ? event.submitter.dataset.confirmMessage : null);

    if (!message) {
        return;
    }

    event.preventDefault();

    confirmDialog({
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: form.dataset.confirmButton || 'OK',
    }).then(function (result) {
        if (!result.isConfirmed) {
            return;
        }

        // Drop the guard so the resubmission is not intercepted a second time.
        delete form.dataset.confirmMessage;
        if (event.submitter instanceof HTMLElement) {
            delete event.submitter.dataset.confirmMessage;
        }

        form.submit();
    });
});
