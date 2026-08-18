<?php

namespace TwillSeo\Contracts;

use TwillSeo\Services\Schema\SchemaContext;

/**
 * One contributor to the JSON-LD graph SchemaBuilder assembles: a built-in
 * (Organization/Person, WebSite, WebPage, Article, BreadcrumbList,
 * PrimaryImage — see src/Services/Schema/Pieces), a class named in
 * config('twill-seo.schema.pieces'), or one pushed onto SeoManager::graph()
 * by a host at request time.
 *
 * Every piece is independent: no piece may assume another has already run,
 * or in what order. Nodes reference each other only through the id-style
 * key SchemaIds computes (see that class), never by nesting one node's full
 * body inside another.
 */
interface GraphPiece
{
    /**
     * Return an empty list to contribute nothing for this page (e.g.
     * ArticlePiece when the page's schema type is not Article-ish) — never
     * throw for "does not apply here".
     *
     * @return list<array<string,mixed>>
     */
    public function pieces(SchemaContext $context): array;
}
