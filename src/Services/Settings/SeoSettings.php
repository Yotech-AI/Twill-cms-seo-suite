<?php

namespace TwillSeo\Services\Settings;

use A17\Twill\Services\FileLibrary\FileService;
use Illuminate\Support\Facades\Schema;
use Throwable;
use TwillSeo\Models\SeoSetting;
use TwillSeo\Services\ModelRegistry;
use TwillSeo\Support\TwillMedia;

/**
 * Merged accessor over TwillSeo\Models\SeoSetting::current() (the single
 * admin-editable settings row) and config('twill-seo.*') (install-time
 * defaults a host sets in code). The DB row wins for every editorial value
 * it stores; the `models` registry itself is deliberately NOT one of those —
 * it stays code-only (ModelRegistry, untouched by this class).
 *
 * Per-request memo of the row: the four JSON columns are read over and over
 * by a single head render (title, description, robots, og, schema graph),
 * and this class is bound as a container singleton (one instance per
 * request) — see TwillSeoServiceProvider. refresh() clears the memo; the
 * Task 10 settings UI calls it after writing a change so the very same
 * request can render with the new values instead of stale ones.
 *
 * Must never query when the table is absent: a fresh install (before
 * `migrate` has run) or any route-registration-time code path can construct
 * this class before twill_seo_settings exists. Schema::hasTable() is itself
 * wrapped in try/catch — on some setups (no DB configured at all yet) even
 * that call can throw — so any failure here degrades to "no row", never an
 * exception the caller has to know to guard against.
 */
final class SeoSettings
{
    private SeoSetting|false|null $rowMemo = null;

    public function __construct(private readonly ModelRegistry $registry) {}

    public function refresh(): void
    {
        $this->rowMemo = null;
    }

    /**
     * No config('twill-seo.general.site_name') key exists — Laravel already
     * owns "the site's name" via config('app.name'), so that is the config
     * layer here rather than a duplicate setting.
     */
    public function siteName(): string
    {
        $value = $this->stringOrNull($this->general(), 'site_name');

        return $value ?? (string) config('app.name', '');
    }

    /**
     * An explicit empty string in the row is a valid, harmless override (no
     * tagline) — array_key_exists, not an empty-check, decides precedence
     * here so an admin can deliberately clear it.
     */
    public function tagline(): string
    {
        $general = $this->general();

        if (array_key_exists('tagline', $general) && is_string($general['tagline'])) {
            return $general['tagline'];
        }

        return (string) config('twill-seo.general.tagline', '');
    }

    public function separator(): string
    {
        $value = $this->stringOrNull($this->general(), 'separator');

        return $value ?? (string) config('twill-seo.title.separator', '-');
    }

    /**
     * @return 'organization'|'person'
     */
    public function entityType(): string
    {
        $value = $this->stringOrNull($this->general(), 'entity_type');
        $configured = (string) config('twill-seo.general.entity_type', 'organization');

        return ($value ?? $configured) === 'person' ? 'person' : 'organization';
    }

    /**
     * Unlike tagline(), a blank entity name would produce an invalid
     * Organization/Person JSON-LD node (schema.org expects a real `name`),
     * so this one falls through on emptiness rather than merely absence,
     * all the way down to the site name as a last resort — never blank.
     */
    public function entityName(): string
    {
        $value = $this->stringOrNull($this->general(), 'entity_name');

        if ($value !== null) {
            return $value;
        }

        $configured = config('twill-seo.general.entity_name');

        if (is_string($configured) && trim($configured) !== '') {
            return $configured;
        }

        return $this->siteName();
    }

    /**
     * The entity logo as {url, width?, height?} — the shape the schema
     * pieces embed as an ImageObject. Precedence: the file role (the
     * settings screen's native picker — the file library, because a logo is
     * usually an SVG), then the legacy media role / JSON id via
     * logoMediaId(). A file has no pixel dimensions in Twill's files table
     * (and an SVG often has none at all), so the file branch returns url
     * only; schema.org's width/height are optional.
     *
     * @return ?array{url: string, width?: int, height?: int}
     */
    public function logo(): ?array
    {
        $fileUrl = $this->logoFileUrl();

        if ($fileUrl !== null) {
            return ['url' => $fileUrl];
        }

        return TwillMedia::fromMediaId($this->logoMediaId());
    }

    /**
     * Role-only lookup, deliberately ignoring the pivot's locale (unlike
     * HasFiles::fileObject(), which filters on the current app locale): the
     * logo is one site-wide asset, and the row it was attached under simply
     * reflects whichever admin language was active at save time.
     */
    private function logoFileUrl(): ?string
    {
        $row = $this->row();

        if ($row === null) {
            return null;
        }

        try {
            $file = $row->files()->wherePivot('role', SeoSetting::LOGO_ROLE)->first();

            if ($file === null) {
                return null;
            }

            return (string) FileService::getUrl($file->uuid);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * The native settings singleton attaches these as real Twill media
     * roles; the JSON ids remain as a read-fallback for installs whose data
     * predates the role migration (and for config-level defaults).
     */
    public function logoMediaId(): ?int
    {
        return $this->mediaRoleId(SeoSetting::LOGO_ROLE)
            ?? $this->intOrNull($this->general(), 'logo_media_id')
            ?? $this->intFromConfig('twill-seo.general.logo_media_id');
    }

    public function defaultShareMediaId(): ?int
    {
        return $this->mediaRoleId(SeoSetting::DEFAULT_SHARE_ROLE)
            ?? $this->intOrNull($this->general(), 'default_share_media_id')
            ?? $this->intFromConfig('twill-seo.general.default_share_media_id');
    }

    private function mediaRoleId(string $role): ?int
    {
        $row = $this->row();

        if ($row === null) {
            return null;
        }

        try {
            $media = $row->medias()->wherePivot('role', $role)->first();
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return $media?->id !== null ? (int) $media->id : null;
    }

    /**
     * @return list<string>
     */
    public function socialProfiles(): array
    {
        $general = $this->general();

        if (array_key_exists('social_profiles', $general) && is_array($general['social_profiles'])) {
            return array_values(array_map(strval(...), $general['social_profiles']));
        }

        return array_values((array) config('twill-seo.general.social_profiles', []));
    }

    /**
     * Features json over config('twill-seo.features.*'). An explicitly
     * stored false is a real, deliberate "off" — array_key_exists, not a
     * truthiness check.
     */
    public function feature(string $key): bool
    {
        $features = $this->features();

        if (array_key_exists($key, $features)) {
            return (bool) $features[$key];
        }

        return (bool) config("twill-seo.features.{$key}", false);
    }

    /**
     * Always returns something renderable (never ''): a blank title template
     * would break every page's <title> tag, so — unlike tagline() — this
     * falls through on emptiness, not merely absence.
     */
    public function titleTemplate(string $registryKey): string
    {
        $template = $this->stringOrNull($this->contentType($registryKey), 'title_template');

        return $template ?? (string) config('twill-seo.title.default_template', '{title} {sep} {site_name}');
    }

    /**
     * Null (not a config-backed default) when nothing is configured — see
     * SeoResolver's own description cascade: "seo_description -> rendered
     * description_template -> absent (no meta description tag at all)".
     * There is no site-wide fallback template the way title has one.
     */
    public function descriptionTemplate(string $registryKey): ?string
    {
        return $this->stringOrNull($this->contentType($registryKey), 'description_template');
    }

    /**
     * content_types json over the registry's own per-type default
     * (ModelRegistry::DEFAULTS['schema_type'] = 'WebPage' when a type does
     * not configure its own) — the registry stays code-only, but its
     * per-type default is still the layer beneath the settings row here.
     */
    public function schemaType(string $registryKey): string
    {
        $override = $this->stringOrNull($this->contentType($registryKey), 'schema_type');

        if ($override !== null) {
            return $override;
        }

        return $this->registry->has($registryKey)
            ? (string) $this->registry->get($registryKey)['schema_type']
            : 'WebPage';
    }

    public function sitemapEnabled(string $registryKey): bool
    {
        $contentType = $this->contentType($registryKey);

        if (array_key_exists('sitemap', $contentType)) {
            return (bool) $contentType['sitemap'];
        }

        return $this->registry->has($registryKey)
            ? (bool) $this->registry->get($registryKey)['sitemap']
            : true;
    }

    /**
     * @return list<string>
     */
    public function robotsDefaults(): array
    {
        $advanced = $this->advanced();

        if (array_key_exists('robots_default_directives', $advanced) && is_array($advanced['robots_default_directives'])) {
            return array_values(array_map(strval(...), $advanced['robots_default_directives']));
        }

        return array_values((array) config('twill-seo.robots.default_directives', []));
    }

    public function searchActionEnabled(): bool
    {
        $advanced = $this->advanced();

        if (array_key_exists('search_action_enabled', $advanced)) {
            return (bool) $advanced['search_action_enabled'];
        }

        return (bool) config('twill-seo.schema.search_action_enabled', false);
    }

    public function searchUrlTemplate(): ?string
    {
        return $this->stringOrNull($this->advanced(), 'search_url_template')
            ?? $this->configStringOrNull('twill-seo.schema.search_url_template');
    }

    public function uninstallRemoveData(): bool
    {
        return (bool) ($this->advanced()['uninstall_remove_data'] ?? false);
    }

    private function row(): ?SeoSetting
    {
        if ($this->rowMemo === false) {
            return null;
        }

        if ($this->rowMemo instanceof SeoSetting) {
            return $this->rowMemo;
        }

        try {
            if (! Schema::hasTable((new SeoSetting)->getTable())) {
                $this->rowMemo = false;

                return null;
            }

            $this->rowMemo = SeoSetting::current();
        } catch (Throwable $e) {
            report($e);
            $this->rowMemo = false;

            return null;
        }

        return $this->rowMemo;
    }

    /**
     * @return array<string,mixed>
     */
    private function general(): array
    {
        return (array) ($this->row()?->general ?? []);
    }

    /**
     * @return array<string,mixed>
     */
    private function features(): array
    {
        return (array) ($this->row()?->features ?? []);
    }

    /**
     * @return array<string,mixed>
     */
    private function advanced(): array
    {
        return (array) ($this->row()?->advanced ?? []);
    }

    /**
     * The content_types blob keyed one level deeper, by registry key —
     * ['articles' => ['title_template' => ..., 'schema_type' => ...], ...].
     *
     * @return array<string,mixed>
     */
    private function contentType(string $registryKey): array
    {
        $all = (array) ($this->row()?->content_types ?? []);

        return (array) ($all[$registryKey] ?? []);
    }

    /**
     * @param  array<string,mixed>  $source
     */
    private function stringOrNull(array $source, string $key): ?string
    {
        $value = $source[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    private function configStringOrNull(string $key): ?string
    {
        $value = config($key);

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * @param  array<string,mixed>  $source
     */
    private function intOrNull(array $source, string $key): ?int
    {
        $value = $source[$key] ?? null;

        return $value !== null && $value !== '' ? (int) $value : null;
    }

    private function intFromConfig(string $key): ?int
    {
        $value = config($key);

        return $value !== null && $value !== '' ? (int) $value : null;
    }
}
