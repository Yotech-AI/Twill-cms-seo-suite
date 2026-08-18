<?php

namespace TwillSeo\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use TwillSeo\Services\ModelRegistry;
use TwillSeo\Services\Settings\SeoSettings;
use TwillSeo\Services\Sitemap\SitemapBuilder;
use TwillSeo\Services\Sitemap\SitemapCache;

/**
 * GET /sitemap.xml and GET /sitemap-{type}-{page}.xml — registered
 * unconditionally by TwillSeoServiceProvider::registerPublicRoutes() (see
 * its own doc comment for why route registration itself never reads the
 * DB-backed settings row). All three 404 conditions the brief calls for live
 * here, at request time, where a DB read is safe:
 *   - the sitemap feature itself is off (index AND every type page);
 *   - {type} is not a known registry key, or that type's own sitemap is
 *     disabled (checked in that order — see registry->has() below — so a
 *     stray DB content_types row for an unknown key can never let an
 *     unregistered type respond);
 *   - {page} is out of [1, pageCount] — the route's `[0-9]+` constraint lets
 *     "0" through on purpose (a page number can never be negative or
 *     non-numeric, but zero and "past the last page" are semantically
 *     invalid, not a routing concern).
 */
class SitemapController extends Controller
{
    public function __construct(
        private readonly SeoSettings $settings,
        private readonly ModelRegistry $registry,
        private readonly SitemapBuilder $builder,
        private readonly SitemapCache $cache,
    ) {}

    public function index(): Response
    {
        if (! $this->settings->feature('sitemap')) {
            abort(404);
        }

        return $this->xml($this->cache->rememberIndex(fn (): string => $this->builder->renderIndex()));
    }

    public function show(string $type, string $page): Response
    {
        if (! $this->settings->feature('sitemap')) {
            abort(404);
        }

        if (! $this->registry->has($type) || ! $this->settings->sitemapEnabled($type)) {
            abort(404);
        }

        $pageNumber = (int) $page;
        $pageCount = $this->builder->pageCount($type);

        if ($pageNumber < 1 || $pageNumber > $pageCount) {
            abort(404);
        }

        return $this->xml($this->cache->rememberPage(
            $type,
            $pageNumber,
            fn (): string => $this->builder->render($type, $pageNumber),
        ));
    }

    private function xml(string $document): Response
    {
        return response($document, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
