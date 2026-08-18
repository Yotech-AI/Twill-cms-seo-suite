<?php

namespace TwillSeo;

use TwillSeo\Services\Meta\PageSeo;
use TwillSeo\Services\Meta\SeoResolver;
use TwillSeo\Services\Schema\SchemaBuilder;

/**
 * The host-facing entry point (see Facades\TwillSeo — a facade class only,
 * no alias, per the brief). Registered as a container singleton so
 * "current()" and "graph()" mean the same thing to a controller that calls
 * for()/page() and to the Head component that renders moments later in the
 * same request.
 */
final class SeoManager
{
    private ?PageSeo $current = null;

    /**
     * Deliberately NOT reset when for()/page() re-establishes "current" —
     * one SchemaBuilder per request, for the lifetime of this singleton.
     * The alternative (a fresh builder every for()/page() call) would
     * silently drop whatever a host already pushed onto graph() the moment
     * the Head component's own `:model="..."` constructor path calls for()
     * again internally (see View\Components\Head), which is the common,
     * expected way this class gets used — losing a host's pushed pieces
     * there would be a real, surprising bug. The tradeoff only matters if a
     * single request renders more than one distinct <x-twill-seo::head />
     * for genuinely different pages, which this package does not attempt to
     * support (one rendered head per response is the assumption throughout).
     */
    private ?SchemaBuilder $graphBuilder = null;

    public function __construct(private readonly SeoResolver $resolver) {}

    public function for(object $model, ?string $locale = null): PageSeo
    {
        return $this->current = $this->resolver->forModel($model, $locale);
    }

    /**
     * @param  list<array{0: string, 1: ?string}>  $breadcrumbs
     */
    public function page(
        ?string $title = null,
        ?string $description = null,
        ?string $url = null,
        ?string $canonical = null,
        bool $noindex = false,
        bool $nofollow = false,
        ?int $shareMediaId = null,
        array $breadcrumbs = [],
        string $schemaType = 'WebPage',
    ): PageSeo {
        return $this->current = $this->resolver->forPage(
            $title,
            $description,
            $url,
            $canonical,
            $noindex,
            $nofollow,
            $shareMediaId,
            $breadcrumbs,
            $schemaType,
        );
    }

    public function current(): ?PageSeo
    {
        return $this->current;
    }

    public function graph(): SchemaBuilder
    {
        return $this->graphBuilder ??= new SchemaBuilder;
    }
}
