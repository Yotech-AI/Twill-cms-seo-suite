/**
 * Bridges the standalone Vue panel to Twill's own Vue 2 / Vuex admin form —
 * a plain module with zero Vue imports, so it can be reasoned about (and, if
 * v1 ever grows a JS test harness, tested) independently of the framework,
 * and so a throw inside it can never come from a framework internal outside
 * our control.
 *
 * Three acquisition tiers, in priority order (see the Task 6 brief's
 * "Operating modes"):
 *   1. live-store — a real window.vm.$store reference, read directly.
 *   2. live-dom   — the store is unreachable, but Twill's own rendered
 *                   <input>/<textarea> elements are found in the DOM.
 *   3. saved      — neither is available; the panel asks the server to
 *                   analyze whatever is already saved to the database.
 *
 * Every exported entry point is defensive: a surprise anywhere downgrades
 * gracefully rather than throwing, because the panel must never be the
 * reason a content editor's page breaks.
 *
 * Field/mutation shapes below are taken from reading the installed
 * area17/twill vendor source directly (not assumed), specifically:
 *   - frontend/js/store/mutations/{form,language}.js for the mutation type
 *     STRING VALUES (the store modules are registered unnamespaced, so
 *     these are the literal `mutation.type` values a subscriber sees);
 *   - frontend/js/store/modules/form.js's UPDATE_FORM_FIELD mutation and
 *     views/partials/form/utils/_translatable_input_store.blade.php (the
 *     server-side bootstrap for the very same array) for the field value
 *     shape: a translated field's `value` is a locale-keyed object
 *     ({en: '...', nl: '...'}), from the very first page load onward; an
 *     untranslated field's `value` is a plain scalar;
 *   - frontend/js/components/LocaleField.vue for the DOM shape: only the
 *     CURRENTLY ACTIVE locale's element is mounted at all (v-if, not
 *     v-show), named "{field}[{locale}]" with a matching data-lang
 *     attribute; an untranslated field renders plainly as name="{field}".
 */

/** The five fields the panel ever needs; `slug` is genuinely optional — see read()'s doc. */
const FIELD_NAMES = ['title', 'seo_title', 'seo_description', 'seo_keyphrase', 'slug'];

// Two independent "the field data changed" mutations, and two independent
// "the locale changed" mutations — LangSwitcher.vue's toolbar commits
// updateLanguage, while LocaleField.vue's own per-field control commits
// switchLanguage. Both are watched; either should trigger an immediate
// (non-debounced) re-analysis in the consuming panel.
const FIELD_MUTATION_TYPES = new Set(['updateFormField', 'replaceFormField', 'addFormField', 'removeFormField']);
const LANGUAGE_MUTATION_TYPES = new Set(['updateLanguage', 'switchLanguage']);

// In the overwhelmingly common case window.vm is already set by the time our
// script runs (Twill's own bundle executes earlier in document order — see
// the brief's research notes) so this poll resolves on its first attempt;
// the 10x200ms budget exists purely as a safety net for unusual load orders.
const STORE_POLL_ATTEMPTS = 10;
const STORE_POLL_INTERVAL_MS = 200;

// Debounces the MutationObserver used for DOM-fallback locale/content-swap
// detection, so one block being duplicated (many DOM mutations at once)
// collapses into a single listener refresh + callback.
const DOM_OBSERVER_DEBOUNCE_MS = 150;

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Never throws: window.vm, .$store and .state can all be absent, or throw on
 * access, depending on what stage of page load we caught it at. Also refuses
 * a store that exists but was registered without the form/language modules
 * (e.g. a listing or dashboard page's own minimal store — see store/index.js,
 * which only ever registers mediaLibrary + notification) rather than
 * reporting a misleading "live-store" mode for a store we cannot read
 * anything useful from.
 */
function readWindowStore() {
    try {
        const store = window.vm && window.vm.$store;
        if (store && store.state && typeof store.state === 'object' && 'form' in store.state && 'language' in store.state) {
            return store;
        }
    } catch {
        // Fall through to null below.
    }
    return null;
}

async function pollForStore() {
    for (let attempt = 0; attempt < STORE_POLL_ATTEMPTS; attempt++) {
        const store = readWindowStore();
        if (store) return store;
        await sleep(STORE_POLL_INTERVAL_MS);
    }
    return readWindowStore();
}

/**
 * A translated field's value is a locale-keyed plain object; an untranslated
 * field's value is a plain scalar (see this file's header for where that's
 * verified). Every field this bridge reads is a simple Input, so "value is a
 * plain object" is an unambiguous discriminator either way.
 */
function resolveStoreFieldValue(fields, name, locale) {
    const field = Array.isArray(fields) ? fields.find((f) => f && f.name === name) : null;
    if (!field) return undefined;

    const { value } = field;
    if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
        return locale ? value[locale] : undefined;
    }
    return value;
}

/**
 * Cheap, non-cryptographic 32-bit hash (FNV-1a) over a stable string built
 * from {name, value} entries. This exists purely to notice that SOMETHING
 * under blocks[ changed since mount (content-drift detection) — never to
 * identify what changed, and the block content itself is never sent
 * anywhere. Collisions are an acceptable risk for a "should I show a hint
 * banner" signal.
 */
function hashEntries(entries) {
    let hash = 0x811c9dc5;
    const text = entries
        .map(({ name, value }) => `${name}=${typeof value === 'string' ? value : JSON.stringify(value)}`)
        .join('');

    for (let i = 0; i < text.length; i++) {
        hash ^= text.charCodeAt(i);
        hash = Math.imul(hash, 0x01000193);
    }

    return (hash >>> 0).toString(16);
}

function blocksHashFromStoreFields(fields) {
    const entries = (Array.isArray(fields) ? fields : [])
        .filter((f) => f && typeof f.name === 'string' && f.name.startsWith('blocks['))
        .map((f) => ({ name: f.name, value: f.value }));

    return hashEntries(entries);
}

function blocksHashFromDom() {
    const nodes = Array.from(document.querySelectorAll('[name^="blocks["]'));
    const entries = nodes.map((el) => ({ name: el.getAttribute('name') || '', value: 'value' in el ? el.value : '' }));

    return hashEntries(entries);
}

/**
 * The active-locale element for a translated field name, the plain element
 * for an untranslated one, or null. LocaleField.vue keeps only the CURRENTLY
 * ACTIVE locale's element mounted in the DOM at all (v-if, not v-show)
 * unless the surrounding form opts into keeping every locale's copy in the
 * DOM — so a bracket-named match is unambiguous in the common case; if more
 * than one somehow exists, the first is used.
 */
function queryFieldElement(name) {
    const bracketed = document.querySelector(
        `input[name^="${name}["], textarea[name^="${name}["], select[name^="${name}["]`
    );
    if (bracketed) return bracketed;

    return document.querySelector(`input[name="${name}"], textarea[name="${name}"], select[name="${name}"]`);
}

function readDomFields() {
    const fields = {};
    let locale = null;

    FIELD_NAMES.forEach((name) => {
        const el = queryFieldElement(name);
        if (!el) return;

        fields[name] = el.value;

        // data-lang is set by LocaleField.vue on every per-locale element it
        // renders; whichever field we happen to read it from first wins —
        // they all agree, since only one locale's set of fields is ever
        // mounted at a time.
        if (locale === null && el.dataset && el.dataset.lang) {
            locale = el.dataset.lang;
        }
    });

    return { fields, locale };
}

function hasAnyDomField() {
    try {
        return FIELD_NAMES.some((name) => queryFieldElement(name) !== null);
    } catch {
        return false;
    }
}

/**
 * Attaches `input` listeners to whatever field elements currently exist, plus
 * a MutationObserver that re-attaches them whenever the DOM changes (a locale
 * switch or a block being added/removed/duplicated swaps nodes in and out
 * rather than toggling visibility — a MutationObserver on childList/subtree
 * is the only framework-agnostic way to notice that without a store
 * reference). Returns a dispose() function.
 */
function attachDomSubscription(callback) {
    let fieldCleanupFns = [];

    function safeInvoke() {
        try {
            callback();
        } catch {
            // A throwing consumer must never take the observer down with it.
        }
    }

    function refreshFieldListeners() {
        fieldCleanupFns.forEach((fn) => fn());
        fieldCleanupFns = [];

        FIELD_NAMES.forEach((name) => {
            const el = queryFieldElement(name);
            if (!el) return;

            el.addEventListener('input', safeInvoke);
            fieldCleanupFns.push(() => el.removeEventListener('input', safeInvoke));
        });
    }

    refreshFieldListeners();

    let debounceTimer = null;
    let observer = null;

    try {
        observer = new MutationObserver(() => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                refreshFieldListeners();
                safeInvoke();
            }, DOM_OBSERVER_DEBOUNCE_MS);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    } catch {
        // document.body always exists by the time this module runs in
        // practice, but a throw here must still never break the panel.
        observer = null;
    }

    return function dispose() {
        clearTimeout(debounceTimer);
        if (observer) {
            try {
                observer.disconnect();
            } catch {
                /* no-op */
            }
        }
        fieldCleanupFns.forEach((fn) => fn());
        fieldCleanupFns = [];
    };
}

/**
 * Creates one independent bridge instance. A factory rather than module-level
 * singleton state deliberately: main.js's mount registry is designed to stay
 * open to more than one mount on the same page (this task's "panel" and a
 * later task's "settings"), and each mount needs its own acquisition/
 * subscription lifecycle rather than sharing (and clobbering) one global's.
 */
export function createTwillFormBridge() {
    let currentMode = 'saved';
    let store = null;
    let storeUnsubscribe = null;
    let domDispose = null;
    let externalCallback = null;
    let disposed = false;

    /** Resolves once acquisition has settled on a mode. Never rejects. */
    async function acquire() {
        if (disposed) return;

        store = await pollForStore();

        if (disposed) return;

        if (store) {
            currentMode = 'live-store';
            return;
        }

        currentMode = hasAnyDomField() ? 'live-dom' : 'saved';
    }

    function mode() {
        return currentMode;
    }

    /**
     * @returns {{locale: string|null, fields: {title?: string, seo_title?: string, seo_description?: string, seo_keyphrase?: string, slug?: string}, blocksHash: string|null}}
     * `fields` only ever contains keys that were actually readable — a
     * missing key is a deliberate "say nothing about this" signal to the
     * caller, not an empty string. `slug` in particular may never appear:
     * Twill has no single universal convention for how (or whether) a slug
     * field is exposed as a plain named input, so it is read opportunistically
     * and simply omitted when not found, exactly like every other field.
     */
    function read() {
        try {
            if (currentMode === 'live-store' && store) {
                return readFromStore();
            }
            if (currentMode === 'live-dom') {
                return readFromDom();
            }
        } catch {
            // Any surprise here degrades to the same empty shape saved mode
            // already returns — read() must never throw.
        }
        return { locale: null, fields: {}, blocksHash: null };
    }

    function readFromStore() {
        const formFields = store?.state?.form?.fields;
        const locale = store?.state?.language?.active?.value ?? null;

        const fields = {};
        FIELD_NAMES.forEach((name) => {
            const value = resolveStoreFieldValue(formFields, name, locale);
            if (value !== undefined) fields[name] = value;
        });

        return { locale, fields, blocksHash: blocksHashFromStoreFields(formFields) };
    }

    function readFromDom() {
        const { fields, locale } = readDomFields();
        return { locale, fields, blocksHash: blocksHashFromDom() };
    }

    /**
     * Registers a callback fired whenever something relevant changes. Only
     * ever one active subscriber per bridge instance (a second call replaces
     * the first, tearing down its DOM listeners if any) — the panel only
     * ever needs one.
     */
    function subscribe(callback) {
        if (disposed) return;

        externalCallback = typeof callback === 'function' ? callback : null;
        if (!externalCallback) return;

        if (currentMode === 'live-store' && store) {
            try {
                storeUnsubscribe = store.subscribe((mutation) => {
                    const type = mutation && mutation.type;
                    if (FIELD_MUTATION_TYPES.has(type) || LANGUAGE_MUTATION_TYPES.has(type)) {
                        safeInvokeExternal();
                    }
                });
            } catch {
                storeUnsubscribe = null;
            }
            return;
        }

        if (currentMode === 'live-dom') {
            domDispose = attachDomSubscription(safeInvokeExternal);
        }

        // 'saved' mode: nothing live to watch — the panel's always-present
        // Re-analyze button is the only refresh trigger, by design.
    }

    function safeInvokeExternal() {
        if (!externalCallback) return;
        try {
            externalCallback();
        } catch {
            // A throwing consumer must never take the bridge (or the page) down with it.
        }
    }

    function dispose() {
        disposed = true;

        if (storeUnsubscribe) {
            try {
                storeUnsubscribe();
            } catch {
                /* no-op */
            }
            storeUnsubscribe = null;
        }

        if (domDispose) {
            domDispose();
            domDispose = null;
        }

        externalCallback = null;
    }

    return { acquire, mode, read, subscribe, dispose };
}
