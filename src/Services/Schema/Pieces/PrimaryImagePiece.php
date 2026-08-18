<?php

namespace TwillSeo\Services\Schema\Pieces;

use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Schema\SchemaIds;

/**
 * The ImageObject node WebPagePiece::primaryImageOfPage and ArticlePiece::
 * image both reference by @id — a single shared node rather than each of
 * those pieces embedding its own copy of the same image data.
 */
final class PrimaryImagePiece implements GraphPiece
{
    public function pieces(SchemaContext $context): array
    {
        $image = $context->pageSeo->ogImage;

        if ($image === null) {
            return [];
        }

        return [[
            '@type' => 'ImageObject',
            '@id' => SchemaIds::primaryImage($context),
            'url' => $image['url'],
            'width' => $image['width'],
            'height' => $image['height'],
        ]];
    }
}
