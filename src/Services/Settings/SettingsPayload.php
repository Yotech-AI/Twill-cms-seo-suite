<?php

namespace TwillSeo\Services\Settings;

use A17\Twill\Models\Media;
use Illuminate\Support\Str;
use TwillSeo\Services\ModelRegistry;
use TwillSeo\Support\TwillMedia;

/**
 * The JSON contract shared by GET/PUT /settings and the settings page's own
 * Blade-embedded bootstrap (see resources/views/settings.blade.php — it
 * chose to embed this directly rather than have the Vue app fetch it on
 * mount, since a settings row is already the authoritative current state at
 * render time; nothing here is per-keystroke or otherwise time-sensitive the
 * way the analysis panel's `initial` snapshot is). Reads through SeoSettings'
 * own merged (DB-row-over-config) accessors — never the raw SeoSetting
 * columns directly — so this payload always reflects exactly what a page
 * render would resolve to.
 */
final class SettingsPayload
{
    public function __construct(
        private readonly SeoSettings $settings,
        private readonly ModelRegistry $registry,
    ) {}

    /**
     * @return array{sections: array<string,mixed>, registry: list<array{key: string, label: string}>, media: array<string,mixed>}
     */
    public function build(): array
    {
        return [
            'sections' => [
                'general' => $this->general(),
                'content_types' => $this->contentTypes(),
                'features' => $this->features(),
                'advanced' => $this->advanced(),
            ],
            'registry' => $this->registryRows(),
            'media' => $this->mediaSummaries(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function general(): array
    {
        return [
            'site_name' => $this->settings->siteName(),
            'tagline' => $this->settings->tagline(),
            'separator' => $this->settings->separator(),
            'entity_type' => $this->settings->entityType(),
            'entity_name' => $this->settings->entityName(),
            'logo_media_id' => $this->settings->logoMediaId(),
            'default_share_media_id' => $this->settings->defaultShareMediaId(),
            'social_profiles' => $this->settings->socialProfiles(),
        ];
    }

    /**
     * One row per registry key — the ContentTypes matrix always renders
     * every managed content type, whether or not it has ever been saved.
     *
     * @return array<string,mixed>
     */
    private function contentTypes(): array
    {
        $rows = [];

        foreach (array_keys($this->registry->all()) as $key) {
            $rows[$key] = [
                'title_template' => $this->settings->titleTemplate($key),
                'description_template' => $this->settings->descriptionTemplate($key),
                'schema_type' => $this->settings->schemaType($key),
                'sitemap' => $this->settings->sitemapEnabled($key),
            ];
        }

        return $rows;
    }

    /**
     * @return array<string,bool>
     */
    private function features(): array
    {
        $keys = ['analysis', 'sitemap', 'schema', 'og', 'twitter', 'hreflang'];

        return array_combine($keys, array_map(fn (string $key) => $this->settings->feature($key), $keys));
    }

    /**
     * @return array<string,mixed>
     */
    private function advanced(): array
    {
        return [
            'robots_default_directives' => $this->settings->robotsDefaults(),
            'search_action_enabled' => $this->settings->searchActionEnabled(),
            'search_url_template' => $this->settings->searchUrlTemplate(),
            'uninstall_remove_data' => $this->settings->uninstallRemoveData(),
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function registryRows(): array
    {
        $rows = [];

        foreach (array_keys($this->registry->all()) as $key) {
            $rows[] = ['key' => $key, 'label' => Str::of($key)->replace(['-', '_'], ' ')->title()->toString()];
        }

        return $rows;
    }

    /**
     * @return array{logo: ?array{id: int, name: string, thumbnail: string}, default_share: ?array{id: int, name: string, thumbnail: string}}
     */
    private function mediaSummaries(): array
    {
        return [
            'logo' => $this->mediaSummary($this->settings->logoMediaId()),
            'default_share' => $this->mediaSummary($this->settings->defaultShareMediaId()),
        ];
    }

    /**
     * @return ?array{id: int, name: string, thumbnail: string}
     */
    private function mediaSummary(?int $mediaId): ?array
    {
        if ($mediaId === null) {
            return null;
        }

        $media = Media::query()->find($mediaId);

        if ($media === null) {
            return null;
        }

        $image = TwillMedia::fromMediaId($mediaId);

        return [
            'id' => $media->id,
            'name' => (string) $media->filename,
            'thumbnail' => $image['url'] ?? '',
        ];
    }
}
