/**
 * Select → Bootstrap 6 Combobox adapter
 * ---------------------------------------------------------------------------
 * Bootstrap 6's Combobox uses different markup than a plain <select>, so this
 * adapter builds that markup from the select's own <option> children and keeps
 * the two in sync.
 *
 * The adapter enhances an existing <select> in place. The original <select> is
 * kept in the DOM (hidden) so form submission, `required` validation and any
 * existing `.value` reads keep working.
 *
 * Usage
 *   <select data-combobox name="customer_id">…</select>
 *   SelectCombobox.init(el);              // or refreshAll() after changing options
 */

import { Combobox } from 'bootstrap';

const COMBOBOX_DUPLICATE_WARNING =
    "Bootstrap doesn't allow more than one instance per element.";

/** Run `fn` with only the known-false-positive Bootstrap warning muted. */
function withSuppressedComboboxWarning(fn) {
    const original = console.error;

    console.error = (...args) => {
        if (typeof args[0] === 'string' && args[0].includes(COMBOBOX_DUPLICATE_WARNING)) {
            return;
        }

        original(...args);
    };

    try {
        return fn();
    } finally {
        console.error = original;
    }
}

const TOGGLE_CLASS = 'combobox';
const VALUE_CLASS = 'combobox-value';
const SEARCH_CLASS = 'combobox-search-input';
const ITEM_CLASS = 'menu-item';

/** selector for selects that should be upgraded */
const TARGET = '[data-combobox]';

export class SelectCombobox {
    /**
     * @param {HTMLSelectElement} select
     * @param {{search?: boolean, placeholder?: string}} [options]
     */
    constructor(select, options = {}) {
        this.select = select;
        // Optional remote source for async loading.
        // Combobox has no built-in async loading, so results are fetched here
        // and merged into the menu.
        this.load = options.load ?? select.dataset.comboboxLoad ?? null;
        this._requestId = 0;
        this.search = options.search ?? true;
        this.placeholder = options.placeholder ?? select.dataset.comboboxPlaceholder ?? 'Select…';
        this.noResultsText = options.noResults ?? select.dataset.comboboxNoResults ?? 'No results';

        this._build();
        this._syncFromSelect();

        // Reflect programmatic changes to the underlying select.
        this._observer = new MutationObserver(() => this._syncFromSelect());
        this._observer.observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled'] });

        // Selection changes come from the menu, so mirror them back.
        select.addEventListener('change', () => this._syncFromCombobox());
    }

    /** Rebuild the list from the select's current options. */
    refresh() {
        this._syncFromSelect();
    }

    destroy() {
        this._observer.disconnect();
        this.instance.dispose();
        this._toggle.remove();
        this._menu.remove();
        this.select.classList.remove('d-none');
        delete this.select.dataset.comboboxBound;
        this._hiddenInput?.remove();
    }

    // -- internals ------------------------------------------------------------

    _build() {
        const { select } = this;

        // The select stays in the DOM but stops rendering: form submission and
        // `.value` reads continue to work, which is what the rest of the page
        // depends on.
        select.classList.add('d-none');
        select.dataset.comboboxBound = 'true';

        // Combobox locates its parts with `SelectorEngine.next(toggle, '.menu')`,
        // so .menu must be an immediate sibling of the toggle — NOT nested
        // inside a wrapper. Nesting makes Menu's `menu` config undefined and
        // the whole control silently fails to open.
        this._toggle = document.createElement('div');
        this.toggleElement = this._toggle;
        this._toggle.className = `${TOGGLE_CLASS} w-100`;
        this._toggle.dataset.bsToggle = 'combobox';
        this._toggle.dataset.bsSearch = this.search ? 'true' : 'false';

        this._button = document.createElement('button');
        this._button.type = 'button';
        this._button.className = `${VALUE_CLASS} form-control text-start d-flex align-items-center justify-content-between gap-2`;
        this._button.textContent = this.placeholder;
        this._toggle.append(this._button);

        this._menu = document.createElement('ul');
        this._menu.className = 'menu';
        this._menu.setAttribute('role', 'listbox');

        // Keep the pair together but adjacent to each other.
        const anchor = select.nextSibling;
        select.parentNode.insertBefore(this._toggle, anchor);
        this._toggle.parentNode.insertBefore(this._menu, this._toggle.nextSibling);

        if (this.search) {
            this._search = document.createElement('input');
            this._search.type = 'text';
            this._search.className = `${SEARCH_CLASS} form-control`;
            this._search.placeholder = this.placeholder;
            this._search.setAttribute('aria-label', this.placeholder);
            this._menu.append(this._search);

            // Must carry .combobox-no-results and no data-bs-value, otherwise
            // Combobox's own selector treats it as a selectable item.
            this._noResults = document.createElement('li');
            this._noResults.className = 'menu-item disabled combobox-no-results d-none';
            this._noResults.setAttribute('role', 'presentation');
            this._noResults.textContent = this.noResultsText;
            this._menu.append(this._noResults);
        }

        // Capture phase is required: Combobox calls stopPropagation() on the
        // item click, so a bubble-phase listener on the menu never runs.
        // Deferring to a microtask lets Combobox finish marking `.selected`
        // before the underlying <select> is updated.
        this._menu.addEventListener(
            'click',
            (event) => {
                queueMicrotask(() => this._syncFromCombobox(event));
            },
            true
        );

        // Combobox binds ArrowDown and Escape on the search input but not
        // Enter, so typing a query and pressing Enter silently does nothing.
        // Commit the first visible match, matching the control's behaviour.
        if (this._search) {
            this._search.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') return;

                const first = this._menu.querySelector(
                    `.${ITEM_CLASS}[data-bs-value]:not(.disabled)`
                );
                if (!first) return;

                event.preventDefault();
                this._commit(first);
            });
        }

        // With a remote source the client-side filter is bypassed: the server
        // owns the result set.
        if (this.load && this._search) {
            this._search.addEventListener('input', () => {
                this._remoteSearch(this._search.value);
            });
        }

        // Bootstrap 6.0.0-alpha.1 has a bug here: Combobox's constructor calls
        // _createMenuInstance(), which does `new Menu(this._toggle)`. That binds
        // a second component key (bs.menu) to the element already holding
        // bs.combobox, so Data.set() logs
        //   "Bootstrap doesn't allow more than one instance per element."
        // The control itself works — Combobox only uses the Menu instance
        // internally, and nothing else binds to the toggle. Silence just this
        // one message rather than patch Bootstrap's internals.
        withSuppressedComboboxWarning(() => {
            this.instance = Combobox.getOrCreateInstance(this._toggle);
        });

        // Multiple is only meaningful when the select already supports it.
    }

    /** select -> menu */
    _syncFromSelect() {
        const { select } = this;

        this._button.disabled = select.disabled;

        // Preserve the item the user had highlighted while typing.
        const active = this._menu.querySelector('.menu-item.active');
        const activeValue = active?.dataset.bsValue;

        const options = [...select.options].filter((o) => o.value !== '');

        // Remove only real items: the search input and the "no results" row
        // are also .menu-item children and must survive a refresh.
        this._menu
            .querySelectorAll(`.${ITEM_CLASS}[data-bs-value]`)
            .forEach((el) => el.remove());

        // Keep generated items before the "no results" row so the row stays
        // last in tab order.
        for (const option of options) {
            const item = document.createElement('li');
            item.className = ITEM_CLASS;
            item.setAttribute('role', 'option');
            item.dataset.bsValue = option.value;
            if (option.disabled) item.classList.add('disabled');
            item.textContent = option.textContent.trim();
            this._menu.insertBefore(item, this._noResults ?? null);
        }

        // Reflect the current selection on the toggle.
        this._updateLabel();

        this._toggle.dataset.multiple = select.multiple ? 'true' : 'false';
        this.instance._config.multiple = select.multiple;

        if (activeValue) {
            this._menu
                .querySelector(`.${ITEM_CLASS}[data-bs-value="${CSS.escape(activeValue)}"]`)
                ?.classList.add('active');
        }
    }

    /** menu -> select */
    _syncFromCombobox(event) {
        // Resolve the item from the click target where possible: Combobox
        // dismisses the menu in the same tick, so its own state may already be
        // gone by the time this runs.
        const target = event?.target?.closest?.(`.${ITEM_CLASS}[data-bs-value]`);
        // Combobox marks a chosen item `.selected`; `.active` only means the
        // keyboard-highlighted row.
        const chosen = target ?? this._menu.querySelector(`.${ITEM_CLASS}.selected`);
        if (!chosen) return;

        this._commit(chosen);
    }

    /** Write a menu item's value into the underlying <select>. */
    _commit(item) {
        const value = item.dataset.bsValue;

        // Remote results are not present in the <select>, so assignment would
        // silently do nothing. Add a matching option first: the label text has
        // to survive because it is what gets displayed and submitted.
        if (![...this.select.options].some((o) => o.value === value)) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = item.textContent;
            this.select.append(option);
        }

        if (this.select.multiple) {
            for (const option of this.select.options) {
                option.selected = option.value === value ? !option.selected : false;
            }
        } else {
            this.select.value = value;
        }

        // Combobox updates its own button label on click but not when the
        // selection is committed from the keyboard, so mirror it here.
        this._updateLabel();

        // Let the rest of the page (change listeners, validation) react.
        this.select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /**
     * Fetch matches for `query` and replace the menu contents.
     *
     * `load(query, callback)` provides an async interface for remote sources.
     * Stale responses are discarded via _requestId, because out-of-order
     * replies would otherwise overwrite newer results.
     */
    _remoteSearch(query) {
        if (!this.load) return;

        const id = ++this._requestId;

        this.load(query, (results) => {
            if (id !== this._requestId) return;
            this._renderResults(results ?? []);
        });
    }

    /** Public loader for remote sources. */
    loadOptions(query = '') {
        if (!this.load) {
            this._menu.querySelectorAll(`.${ITEM_CLASS}[data-bs-value]`).forEach((el) => el.remove());
            return;
        }

        this._remoteSearch(query);
    }

    /** Clear the value and repopulate — equivalent to clear(true) + load(''). */
    clearAndReload() {
        this.select.value = '';
        this._updateLabel();
        this.loadOptions('');
    }

    /** Build menu items from `{value, text}` pairs. */
    _renderResults(results) {
        this._menu.querySelectorAll(`.${ITEM_CLASS}[data-bs-value]`).forEach((el) => el.remove());

        for (const row of results) {
            const item = document.createElement('li');
            item.className = ITEM_CLASS;
            item.setAttribute('role', 'option');
            item.dataset.bsValue = row.value;
            if (row.disabled) item.classList.add('disabled');
            item.textContent = (row.text ?? '').trim();
            this._menu.insertBefore(item, this._noResults ?? null);
        }

        this._noResults?.classList.toggle('d-none', results.length > 0);
    }

    /** Show the current selection on the toggle button. */
    _updateLabel() {
        const { select } = this;

        if (select.multiple) {
            const chosen = [...select.selectedOptions];
            this._button.textContent = chosen.length
                ? chosen.map((o) => o.textContent.trim()).join(', ')
                : this.placeholder;
            return;
        }

        const selected = select.selectedOptions[0];
        this._button.textContent = selected ? selected.textContent.trim() : this.placeholder;
    }

    /** Current value, matching the underlying select. */
    get value() {
        return this.select.value;
    }

    set value(v) {
        this.select.value = v;
        this._syncFromSelect();
    }
}

/** Registry for combobox instances. */
const REGISTRY = new WeakMap();

/** Upgrade one select. Safe to call repeatedly. */
export function initCombobox(select, options) {
    if (!(select instanceof HTMLSelectElement)) return null;

    // The MutationObserver in app.js can call this again on the same element.
    // Bootstrap allows one instance per element, so bail out if the toggle
    // this select owns is still connected.
    const owned = select._comboboxToggle;
    if (owned?.isConnected) return REGISTRY.get(select) ?? null;

    const existing = REGISTRY.get(select);
    if (existing) {
        existing.refresh();
        return existing;
    }

    const instance = new SelectCombobox(select, options);
    REGISTRY.set(select, instance);
    select._comboboxToggle = instance.toggleElement;

    // Expose the combobox API on the element for easy access.
    select.autoComplete = {
        refresh: () => instance.refresh(),
        load: (query) => instance.loadOptions(query),
        setValue: (value) => {
            select.value = value;
            instance.refresh();
        },
        clear: () => {
            select.value = '';
            instance.refresh();
        },
        clearOptions: () => instance.clearAndReload(),
        destroy: () => {
            REGISTRY.delete(select);
            instance.destroy();
        },
    };

    return instance;
}

/** Upgrade every [data-combobox] under root. */
export function initComboboxes(root = document) {
    return [...root.querySelectorAll(TARGET)].map((el) => initCombobox(el));
}

/** Refresh every bound combobox — call after options change. */
export function refreshComboboxes(root = document) {
    for (const el of root.querySelectorAll(TARGET)) {
        REGISTRY.get(el)?.refresh();
    }
}

window.SelectCombobox = { initCombobox, initComboboxes, refreshComboboxes, SelectCombobox };