<?php

namespace TwillSeo\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use TwillSeo\Services\ModelRegistry;

/**
 * Validates a PUT /seo/settings call. Every one of the four top-level
 * sections is optional (`sometimes`) — the endpoint accepts any subset, and
 * SettingsController only touches the sections actually present in
 * validated(). Route middleware (twill_auth) already gates who may call this
 * at all, so authorize() has nothing further to check (same convention as
 * AnalyzeRequest).
 *
 * Each PRESENT section is validated as a whole replacement for that section
 * of the stored row, not a partial patch of its own sub-keys — the settings
 * UI always submits a section's complete local state (seeded from GET), so
 * there is no partial-key-merge case to support.
 */
class SettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ModelRegistry is method-injected (Laravel resolves rules() through the
     * container, same as authorize()/messages()) rather than pulled from
     * app() inline, so this stays a normal, testable constructor-style
     * dependency.
     *
     * @return array<string,mixed>
     */
    public function rules(ModelRegistry $registry): array
    {
        $knownContentTypes = array_keys($registry->all());

        return [
            'general' => ['sometimes', 'array'],
            'general.site_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'general.tagline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'general.separator' => ['sometimes', 'nullable', 'string', 'max:10'],
            'general.entity_type' => ['sometimes', 'string', 'in:organization,person'],
            'general.entity_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'general.logo_media_id' => ['sometimes', 'nullable', 'integer'],
            'general.default_share_media_id' => ['sometimes', 'nullable', 'integer'],
            'general.social_profiles' => ['sometimes', 'array'],
            'general.social_profiles.*' => ['string', 'url', 'max:2048'],

            // Rejected here (422, all-or-nothing) rather than silently
            // filtered in the controller: SettingsPayload::contentTypes()
            // only ever iterates registry keys, so an unregistered key
            // saved into the JSON column would be permanently invisible and
            // unremovable through this app — an orphaned blob only a raw DB
            // edit could clean up. A clear validation error naming the bad
            // key is friendlier to a future API caller than data quietly
            // vanishing, and there is nothing left for the controller to
            // additionally guard once this rejects every request carrying
            // an unknown key before update() ever runs.
            'content_types' => [
                'sometimes', 'array',
                function (string $attribute, mixed $value, Closure $fail) use ($knownContentTypes): void {
                    $unknown = array_diff(array_keys((array) $value), $knownContentTypes);

                    if ($unknown !== []) {
                        $fail('Unknown content type(s): '.implode(', ', $unknown).'.');
                    }
                },
            ],
            'content_types.*' => ['array'],
            'content_types.*.title_template' => ['sometimes', 'nullable', 'string', 'max:255'],
            'content_types.*.description_template' => ['sometimes', 'nullable', 'string', 'max:500'],
            'content_types.*.schema_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'content_types.*.sitemap' => ['sometimes', 'boolean'],

            'features' => ['sometimes', 'array'],
            'features.analysis' => ['sometimes', 'boolean'],
            'features.sitemap' => ['sometimes', 'boolean'],
            'features.schema' => ['sometimes', 'boolean'],
            'features.og' => ['sometimes', 'boolean'],
            'features.twitter' => ['sometimes', 'boolean'],
            'features.hreflang' => ['sometimes', 'boolean'],

            'advanced' => ['sometimes', 'array'],
            'advanced.robots_default_directives' => ['sometimes', 'array'],
            'advanced.robots_default_directives.*' => ['string', 'max:100'],
            'advanced.search_action_enabled' => ['sometimes', 'boolean'],
            'advanced.search_url_template' => [
                'sometimes', 'nullable', 'string', 'max:500',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && $value !== '' && ! str_contains($value, '{search_term_string}')) {
                        $fail('The search URL template must contain the {search_term_string} placeholder.');
                    }
                },
            ],
            'advanced.uninstall_remove_data' => ['sometimes', 'boolean'],
        ];
    }
}
