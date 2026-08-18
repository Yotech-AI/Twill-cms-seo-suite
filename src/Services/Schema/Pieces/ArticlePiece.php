<?php

namespace TwillSeo\Services\Schema\Pieces;

use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Schema\SchemaIds;

/**
 * An extra node alongside WebPagePiece's, contributed only for an
 * Article-ish schema type. Reuses PageSeo::ogType ('article' vs 'website')
 * as its gate rather than re-deriving the Article-ish pattern match itself —
 * SeoResolver already made that exact decision once (see its isArticleType()),
 * and re-implementing the same string check here would risk the two quietly
 * drifting apart.
 */
final class ArticlePiece implements GraphPiece
{
    private const HEADLINE_MAX_LENGTH = 110;

    public function pieces(SchemaContext $context): array
    {
        $seo = $context->pageSeo;

        if ($seo->ogType !== 'article') {
            return [];
        }

        $node = [
            '@type' => $seo->schemaType,
            '@id' => SchemaIds::article($context),
            'headline' => mb_substr($seo->title, 0, self::HEADLINE_MAX_LENGTH),
            'isPartOf' => ['@id' => SchemaIds::webpage($context)],
            'mainEntityOfPage' => ['@id' => SchemaIds::webpage($context)],
            'publisher' => ['@id' => SchemaIds::entity($context)],
        ];

        if ($seo->publishedTime !== null) {
            $node['datePublished'] = $seo->publishedTime->format(DATE_ATOM);
        }

        if ($seo->modifiedTime !== null) {
            $node['dateModified'] = $seo->modifiedTime->format(DATE_ATOM);
        }

        if ($seo->ogImage !== null) {
            $node['image'] = ['@id' => SchemaIds::primaryImage($context)];
        }

        return [$node];
    }
}
