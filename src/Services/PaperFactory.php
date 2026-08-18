<?php

namespace TwillSeo\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Contracts\SeoContentResolver;
use TwillSeo\Models\SeoEntryTranslation;
use TwillSeo\Services\Resolvers\UrlResolver;
use TwillSeo\Support\TranslatedAttribute;

/**
 * Builds a complete Paper from a saved model — the DB alone is always enough,
 * so panel numbers, cached scores (ScoreCache) and listing dots all agree.
 * The `$overrides` a live-typing endpoint call supplies are optional
 * per-attribute replacements layered on top of that saved baseline, never a
 * second, independent source of truth.
 */
final class PaperFactory
{
    public function __construct(
        private readonly ModelRegistry $registry,
        private readonly SeoContentResolver $resolver,
        private readonly UrlResolver $urlResolver,
    ) {}

    /**
     * @param  array{title?: ?string, seo_title?: ?string, seo_description?: ?string, keyphrase?: ?string, slug?: ?string, content_override?: ?string, title_width?: ?int}  $overrides
     */
    public function fromModel(object $model, string $locale, array $overrides = []): PaperBuild
    {
        $key = $this->registry->keyFor($model);
        $config = $key !== null ? $this->registry->get($key) : [];

        $seo = method_exists($model, 'seo') ? $model->seo($locale) : null;

        [$text, $contentSource] = $this->resolveContent($model, $locale, $overrides, $config);

        $paper = Paper::builder()
            ->text($text)
            ->keyword($overrides['keyphrase'] ?? $seo?->focus_keyphrase ?? '')
            ->title($this->resolveTitle($model, $config, $seo, $overrides, $locale))
            ->titleWidth($this->resolveTitleWidth($overrides))
            ->description($overrides['seo_description'] ?? $seo?->seo_description ?? '')
            ->slug($this->resolveSlug($model, $overrides, $locale))
            ->permalink($this->resolvePermalink($model, $locale))
            ->locale($locale)
            ->date($this->resolveDate($model))
            ->customData($this->customData($model, $key))
            ->build();

        return new PaperBuild($paper, $contentSource);
    }

    /**
     * @param  array<string,mixed>  $overrides
     * @param  array<string,mixed>  $config
     * @return array{0: string, 1: string}
     */
    private function resolveContent(object $model, string $locale, array $overrides, array $config): array
    {
        $override = $overrides['content_override'] ?? null;

        if ($override !== null) {
            return [$override, 'override'];
        }

        $resolved = $this->resolverFor($config)->resolve($model, $locale);

        return [$resolved->html, $resolved->source];
    }

    /**
     * The registry's own `content` class-string overrides the default
     * resolver for that model type — resolved through the container so it
     * can have its own dependencies, exactly like RenderedBlocksResolver
     * does. Falls back to the container-bound default (the constructor's
     * $resolver) for anything not configured, misconfigured with something
     * that is not a class name, or naming a class that does not actually
     * implement the contract.
     *
     * @param  array<string,mixed>  $config
     */
    private function resolverFor(array $config): SeoContentResolver
    {
        $class = $config['content'] ?? null;

        if (is_string($class) && $class !== '' && is_a($class, SeoContentResolver::class, true)) {
            return app($class);
        }

        return $this->resolver;
    }

    /**
     * SEO title fallback (no TitleTemplate exists until Task 7): the seo_title
     * override wins over the stored seo_title translation, which wins over
     * whatever the page's own title resolves to (itself overridable, for a
     * live-typing call that has not touched the SEO title field yet). Kept in
     * this one method so Task 7 can swap the tail for a real TitleTemplate
     * without touching the override layering above it.
     *
     * @param  array<string,mixed>  $config
     * @param  array<string,mixed>  $overrides
     */
    private function resolveTitle(object $model, array $config, ?SeoEntryTranslation $seo, array $overrides, string $locale): string
    {
        $titleAttribute = $config['title_attribute'] ?? 'title';

        $fallback = $overrides['title'] ?? TranslatedAttribute::get($model, $titleAttribute, $locale) ?? '';

        return $overrides['seo_title'] ?? $seo?->seo_title ?? $fallback;
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function resolveTitleWidth(array $overrides): ?int
    {
        $width = $overrides['title_width'] ?? null;

        return $width === null ? null : (int) $width;
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function resolveSlug(object $model, array $overrides, string $locale): string
    {
        if (isset($overrides['slug'])) {
            return $overrides['slug'];
        }

        return method_exists($model, 'getSlug') ? $model->getSlug($locale) : '';
    }

    /**
     * Permalink resolution now lives in UrlResolver (Task 7), which every
     * other consumer of "this page's URL" — canonical link, og:url, hreflang
     * alternates — goes through too, so the analysis engine and the
     * rendered head can never quietly disagree about a model's URL. The `??
     * ''` preserves this method's own pre-Task-7 contract (a definite
     * string, never null) unchanged for Paper::permalink.
     */
    private function resolvePermalink(object $model, string $locale): string
    {
        return $this->urlResolver->resolve($model, $locale) ?? '';
    }

    private function resolveDate(object $model): ?DateTimeImmutable
    {
        $value = $model->published_at ?? $model->created_at ?? null;

        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        try {
            return new DateTimeImmutable((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{model_type: string, model_id: int|string|null, registry_key: ?string}
     */
    private function customData(object $model, ?string $key): array
    {
        return [
            // getMorphClass(), not get_class(): this must match exactly what
            // HandleSeo stored as seoable_type, since KeyphraseUsage looks up
            // the paper's own SeoEntry by this same value.
            'model_type' => method_exists($model, 'getMorphClass') ? $model->getMorphClass() : get_class($model),
            'model_id' => method_exists($model, 'getKey') ? $model->getKey() : null,
            'registry_key' => $key,
        ];
    }
}
