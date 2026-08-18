<?php

namespace TwillSeo\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use TwillSeo\SeoManager;
use TwillSeo\Services\Meta\PageSeo;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Settings\SeoSettings;

/**
 * `<x-twill-seo::head :model="$item" />` — the single component a host layout
 * needs for the full SEO head (title, meta description, robots, canonical,
 * hreflang, OG, Twitter, JSON-LD @graph). Resolved via
 * Blade::componentNamespace('TwillSeo\View\Components', 'twill-seo')
 * (TwillSeoServiceProvider::boot()).
 *
 * With a $model, this establishes it as SeoManager's "current" page itself
 * (so a bare `:model="$item"` works with no host controller code at all);
 * without one, it falls back to whatever a host already set up via
 * TwillSeo::page(...) earlier in the same request (e.g. a search results
 * controller) — and renders nothing but a comment when neither exists.
 */
class Head extends Component
{
    public readonly ?PageSeo $seo;

    private readonly string $locale;

    public function __construct(
        ?object $model = null,
        ?string $locale = null,
        ?string $title = null,
        ?string $description = null,
        ?string $type = null,
    ) {
        $manager = app(SeoManager::class);

        $base = $model !== null ? $manager->for($model, $locale) : $manager->current();

        $this->seo = $base?->withOverrides($title, $description, $type);
        $this->locale = $locale ?? app()->getLocale();
    }

    public function render(): View
    {
        $settings = app(SeoSettings::class);

        return view('twill-seo::head', [
            'seo' => $this->seo,
            'siteName' => $settings->siteName(),
            'showOg' => $settings->feature('og'),
            'graph' => $this->buildGraph($settings),
        ]);
    }

    /**
     * Building the graph is skipped entirely (not merely hidden by the view)
     * when there is no page context or the schema feature is off — schema
     * pieces run real logic (registry breadcrumb callbacks, media
     * resolution), so "gated by its feature toggle" means never running that
     * work at all, not just discarding the result.
     *
     * @return ?array{'@context': string, '@graph': list<array<string,mixed>>}
     */
    private function buildGraph(SeoSettings $settings): ?array
    {
        if ($this->seo === null || ! $settings->feature('schema')) {
            return null;
        }

        $context = new SchemaContext($this->seo, $settings, (string) config('app.url'), $this->locale);
        $graph = app(SeoManager::class)->graph()->build($context);

        return $graph['@graph'] !== [] ? $graph : null;
    }
}
