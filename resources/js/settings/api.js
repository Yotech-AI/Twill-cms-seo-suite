/**
 * Thin fetch client for the settings admin's two endpoints (PUT
 * {admin}/seo/settings, GET {admin}/seo/media — see SettingsController and
 * MediaSearchController). CSRF sourced from config.csrf exactly like the
 * analysis panel's own api.js — see that file's own doc comment for why: the
 * installed Twill admin layout renders no <meta name="csrf-token"> tag at
 * all, so the Blade-rendered config value is the only reliable source.
 */

function readCsrfToken(config) {
    if (config && typeof config.csrf === 'string' && config.csrf !== '') {
        return config.csrf;
    }

    try {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') || '' : '';
    } catch {
        return '';
    }
}

export class SettingsApiError extends Error {
    constructor(message, status, errors) {
        super(message);
        this.name = 'SettingsApiError';
        this.status = status;
        this.errors = errors || null;
    }
}

/**
 * Twill renders a ValidationException on an admin JSON request as a FLAT
 * error bag ({"general.entity_type": [...]}), not Laravel's default
 * {message, errors} envelope — same convention the analysis panel's api.js
 * already documents and defends against. Both shapes are parsed here.
 */
function extractError(data, status) {
    if (!data || typeof data !== 'object') {
        return new SettingsApiError(`Request failed (${status}).`, status);
    }

    const errors = data.errors && typeof data.errors === 'object' ? data.errors : data;
    const message = typeof data.message === 'string' && data.message !== '' ? data.message : null;

    for (const key of Object.keys(errors)) {
        const value = errors[key];
        if (Array.isArray(value) && typeof value[0] === 'string') {
            return new SettingsApiError(message || value[0], status, errors);
        }
    }

    return new SettingsApiError(message || `Request failed (${status}).`, status, null);
}

/**
 * @param {{endpoints: {update: string, media: string}, csrf?: string}} config
 */
export function createSettingsApi(config) {
    const csrf = readCsrfToken(config);

    async function request(url, options) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            ...options,
        });

        let data = null;
        try {
            data = await response.json();
        } catch {
            data = null;
        }

        if (!response.ok) {
            throw extractError(data, response.status);
        }

        return data;
    }

    /**
     * PUT with exactly one section — see SettingsUpdateRequest: any subset of
     * {general, content_types, features, advanced} is accepted, and every
     * section present replaces that section wholesale.
     */
    function saveSection(section, payload) {
        return request(config.endpoints.update, {
            method: 'PUT',
            body: JSON.stringify({ [section]: payload }),
        });
    }

    function searchMedia(q, page) {
        const params = new URLSearchParams();
        if (q) params.set('q', q);
        if (page) params.set('page', String(page));

        const query = params.toString();

        return request(config.endpoints.media + (query ? `?${query}` : ''), { method: 'GET' });
    }

    return { saveSection, searchMedia };
}
