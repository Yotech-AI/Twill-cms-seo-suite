/**
 * Thin fetch client for the Task 5 analyze endpoint
 * (POST {admin}/seo/analyze — see AnalyzeController + AnalyzeRequest).
 *
 * CSRF sourcing: the brief's original plan was a
 * `document.querySelector('meta[name="csrf-token"]')` lookup, but the
 * installed area17/twill vendor layout was read directly for this task
 * (views/layouts/main.blade.php + views/partials/head.blade.php) and
 * renders NO such meta tag anywhere — Twill instead relies on a hidden
 * `_token` input inside real <form> submissions. A DOM lookup alone would
 * therefore silently send an empty token on every request. Instead the
 * token is sourced from `config.csrf`, which the analysis-panel Blade
 * partial populates directly via `csrf_token()` (a value that is always
 * correct, since it is rendered server-side at the same moment as the rest
 * of the panel's config). The meta-tag lookup is kept as a harmless
 * secondary source only, in case a host's custom layout — or a future
 * Twill version — adds one.
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

export class AnalyzeError extends Error {
    constructor(message, status) {
        super(message);
        this.name = 'AnalyzeError';
        this.status = status;
    }
}

/**
 * Twill renders a ValidationException on an admin JSON request as a FLAT
 * error bag ({"type": ["Unknown content type."]}), not Laravel's default
 * {message, errors} envelope — verified in Task 5 (see AnalyzeEndpointTest's
 * "rejects a type the model registry does not know with 422", which asserts
 * exactly this with `assertJsonValidationErrors('type', null)`). Both shapes
 * are parsed defensively, since which one a given failure takes is a server-
 * side implementation detail the client cannot rely on.
 */
function extractErrorMessage(data, status) {
    if (!data || typeof data !== 'object') {
        return `Analysis request failed (${status}).`;
    }

    if (typeof data.message === 'string' && data.message !== '') {
        return data.message;
    }

    // Laravel's {message, errors} envelope, or Twill's flat error bag —
    // either way, the first message of the first field wins.
    const errors = data.errors && typeof data.errors === 'object' ? data.errors : data;

    for (const key of Object.keys(errors)) {
        const value = errors[key];
        if (Array.isArray(value) && typeof value[0] === 'string') {
            return value[0];
        }
    }

    return `Analysis request failed (${status}).`;
}

/**
 * @param {{endpoint: string, csrf?: string}} config
 * @returns {{analyze(payload: object, signal?: AbortSignal): Promise<{report: object, meta: object}>}}
 */
export function createApi(config) {
    const csrf = readCsrfToken(config);

    async function analyze(payload, signal) {
        const response = await fetch(config.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            signal,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        // Parsed defensively: a 500 from an unrelated proxy/webserver layer
        // may not be JSON at all, and that must not throw somewhere the
        // caller can't turn into a quiet inline message.
        let data = null;
        try {
            data = await response.json();
        } catch {
            data = null;
        }

        if (!response.ok) {
            throw new AnalyzeError(extractErrorMessage(data, response.status), response.status);
        }

        return data;
    }

    return { analyze };
}
