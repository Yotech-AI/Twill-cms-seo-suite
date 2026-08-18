<?php

namespace TwillSeo\Services\Schema\Pieces;

use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Schema\SchemaIds;

/**
 * The base per-page node — always contributed regardless of schema type;
 * ArticlePiece adds a SECOND, complementary node alongside this one for
 * Article-ish types (cross-referencing it via isPartOf/mainEntityOfPage),
 * it never replaces it.
 */
final class WebPagePiece implements GraphPiece
{
    public function pieces(SchemaContext $context): array
    {
        $seo = $context->pageSeo;

        $node = [
            '@type' => 'WebPage',
            '@id' => SchemaIds::webpage($context),
            'url' => SchemaIds::pageUrl($context),
            'name' => $seo->title,
            'isPartOf' => ['@id' => SchemaIds::website($context)],
            'inLanguage' => $context->locale,
        ];

        if ($seo->publishedTime !== null) {
            $node['datePublished'] = $seo->publishedTime->format(DATE_ATOM);
        }

        if ($seo->modifiedTime !== null) {
            $node['dateModified'] = $seo->modifiedTime->format(DATE_ATOM);
        }

        if ($seo->ogImage !== null) {
            $node['primaryImageOfPage'] = ['@id' => SchemaIds::primaryImage($context)];
        }

        if ($seo->breadcrumbs !== []) {
            $node['breadcrumb'] = ['@id' => SchemaIds::breadcrumb($context)];
        }

        return [$node];
    }
}
