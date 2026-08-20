<?php

namespace TwillSeo\Twill\Capsules\SeoSettings\Repositories;

use A17\Twill\Repositories\Behaviors\HandleMedias;
use A17\Twill\Repositories\ModuleRepository;
use Throwable;
use TwillSeo\Services\ModelRegistry;
use TwillSeo\Services\Settings\SeoSettings;
use TwillSeo\Services\Sitemap\SitemapCache;
use TwillSeo\Twill\Capsules\SeoSettings\Models\SeoSetting;

/**
 * Maps the singleton form's flat field names onto the settings row's four
 * JSON columns — the same stash-and-strip shape HandleSeo uses on host
 * repositories, so no flat field ever reaches fill() as a phantom column.
 *
 * Field naming contract with the controller's getForm():
 *   general_*                    -> general JSON keys
 *   feature_{key}                -> features JSON keys
 *   ct_{registryKey}_{setting}   -> content_types.{registryKey} JSON keys
 *   advanced_*                   -> advanced JSON keys
 * The logo / default share image are real media roles (HandleMedias).
 */
class SeoSettingRepository extends ModuleRepository
{
    use HandleMedias;

    private const GENERAL_STRINGS = ['site_name', 'tagline', 'separator', 'entity_type', 'entity_name'];

    private const FEATURES = ['analysis', 'sitemap', 'schema', 'og', 'twitter', 'hreflang'];

    private const CONTENT_TYPE_SETTINGS = ['title_template', 'description_template', 'schema_type', 'sitemap'];

    /** @var array<string,mixed> */
    private array $stashedSettings = [];

    public function __construct(SeoSetting $model)
    {
        $this->model = $model;
    }

    public function prepareFieldsBeforeCreate($fields): array
    {
        return parent::prepareFieldsBeforeCreate($this->stashSettingsFields($fields));
    }

    public function prepareFieldsBeforeSave($object, $fields): array
    {
        return parent::prepareFieldsBeforeSave($object, $this->stashSettingsFields($fields));
    }

    public function afterSave($object, $fields): void
    {
        parent::afterSave($object, $fields);

        if ($this->stashedSettings !== []) {
            $this->writeSettings($object, $this->stashedSettings);
            $this->stashedSettings = [];
        }

        // The same request must render with the new values, and cached
        // sitemap pages reflect settings (feature toggles, templates) —
        // both wrapped so a cache hiccup can never fail the save.
        try {
            app(SeoSettings::class)->refresh();
            app(SitemapCache::class)->flushAll();
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function getFormFields($object): array
    {
        $fields = parent::getFormFields($object);

        $general = (array) ($object->general ?? []);

        foreach (self::GENERAL_STRINGS as $key) {
            $fields["general_{$key}"] = $general[$key] ?? null;
        }

        $fields['general_social_profiles'] = implode("\n", array_map(
            strval(...),
            (array) ($general['social_profiles'] ?? []),
        ));

        $features = (array) ($object->features ?? []);

        foreach (self::FEATURES as $key) {
            // Absent key: show the config default the accessor would apply,
            // so the form reflects effective reality rather than blank.
            $fields["feature_{$key}"] = array_key_exists($key, $features)
                ? (bool) $features[$key]
                : (bool) config("twill-seo.features.{$key}", false);
        }

        $contentTypes = (array) ($object->content_types ?? []);

        foreach (app(ModelRegistry::class)->all() as $registryKey => $registryEntry) {
            $stored = (array) ($contentTypes[$registryKey] ?? []);

            $fields["ct_{$registryKey}_title_template"] = $stored['title_template'] ?? null;
            $fields["ct_{$registryKey}_description_template"] = $stored['description_template'] ?? null;
            $fields["ct_{$registryKey}_schema_type"] = $stored['schema_type'] ?? $registryEntry['schema_type'];
            $fields["ct_{$registryKey}_sitemap"] = array_key_exists('sitemap', $stored)
                ? (bool) $stored['sitemap']
                : (bool) $registryEntry['sitemap'];
        }

        $advanced = (array) ($object->advanced ?? []);

        $fields['advanced_robots_default_directives'] = implode(', ', array_map(
            strval(...),
            array_key_exists('robots_default_directives', $advanced) && is_array($advanced['robots_default_directives'])
                ? $advanced['robots_default_directives']
                : (array) config('twill-seo.robots.default_directives', []),
        ));
        $fields['advanced_search_action_enabled'] = (bool) ($advanced['search_action_enabled'] ?? false);
        $fields['advanced_search_url_template'] = $advanced['search_url_template'] ?? null;
        $fields['advanced_uninstall_remove_data'] = (bool) ($advanced['uninstall_remove_data'] ?? false);

        return $fields;
    }

    /**
     * @param  array<string,mixed>  $fields
     * @return array<string,mixed>
     */
    private function stashSettingsFields(array $fields): array
    {
        foreach (array_keys($fields) as $name) {
            if (preg_match('/^(general_|feature_|ct_|advanced_)/', (string) $name) === 1) {
                $this->stashedSettings[$name] = $fields[$name];
                unset($fields[$name]);
            }
        }

        return $fields;
    }

    /**
     * @param  array<string,mixed>  $stashed
     */
    private function writeSettings(SeoSetting $row, array $stashed): void
    {
        $general = (array) ($row->general ?? []);

        foreach (self::GENERAL_STRINGS as $key) {
            if (array_key_exists("general_{$key}", $stashed)) {
                $general[$key] = $this->trimToNull($stashed["general_{$key}"]);
            }
        }

        if (array_key_exists('general_social_profiles', $stashed)) {
            $general['social_profiles'] = array_values(array_filter(array_map(
                trim(...),
                preg_split('/\R+/', (string) $stashed['general_social_profiles']) ?: [],
            ), fn (string $url): bool => $url !== ''));
        }

        $features = (array) ($row->features ?? []);

        foreach (self::FEATURES as $key) {
            if (array_key_exists("feature_{$key}", $stashed)) {
                $features[$key] = (bool) $stashed["feature_{$key}"];
            }
        }

        $contentTypes = (array) ($row->content_types ?? []);

        foreach (app(ModelRegistry::class)->all() as $registryKey => $registryEntry) {
            $stored = (array) ($contentTypes[$registryKey] ?? []);

            foreach (self::CONTENT_TYPE_SETTINGS as $setting) {
                $fieldName = "ct_{$registryKey}_{$setting}";

                if (! array_key_exists($fieldName, $stashed)) {
                    continue;
                }

                if ($setting === 'sitemap') {
                    $stored['sitemap'] = (bool) $stashed[$fieldName];

                    continue;
                }

                // Empty template/type falls back to config/registry — store
                // nothing so the accessor's precedence keeps working.
                $value = $this->trimToNull($stashed[$fieldName]);

                if ($value === null) {
                    unset($stored[$setting]);
                } else {
                    $stored[$setting] = $value;
                }
            }

            if ($stored === []) {
                unset($contentTypes[$registryKey]);
            } else {
                $contentTypes[$registryKey] = $stored;
            }
        }

        $advanced = (array) ($row->advanced ?? []);

        if (array_key_exists('advanced_robots_default_directives', $stashed)) {
            $advanced['robots_default_directives'] = array_values(array_filter(array_map(
                trim(...),
                explode(',', (string) $stashed['advanced_robots_default_directives']),
            ), fn (string $directive): bool => $directive !== ''));
        }

        if (array_key_exists('advanced_search_action_enabled', $stashed)) {
            $advanced['search_action_enabled'] = (bool) $stashed['advanced_search_action_enabled'];
        }

        if (array_key_exists('advanced_search_url_template', $stashed)) {
            $advanced['search_url_template'] = $this->trimToNull($stashed['advanced_search_url_template']);
        }

        if (array_key_exists('advanced_uninstall_remove_data', $stashed)) {
            $advanced['uninstall_remove_data'] = (bool) $stashed['advanced_uninstall_remove_data'];
        }

        $row->forceFill([
            'general' => $general,
            'features' => $features,
            'content_types' => $contentTypes,
            'advanced' => $advanced,
        ])->save();
    }

    private function trimToNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return $value === null ? null : (string) $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
