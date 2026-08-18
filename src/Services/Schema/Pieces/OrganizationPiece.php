<?php

namespace TwillSeo\Services\Schema\Pieces;

use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Schema\SchemaIds;
use TwillSeo\Support\TwillMedia;

/**
 * The site's schema.org identity when settings entityType() is
 * 'organization' (the default) — see PersonPiece for the alternative. Only
 * one of the two ever contributes a node for a given install.
 */
final class OrganizationPiece implements GraphPiece
{
    public function pieces(SchemaContext $context): array
    {
        if ($context->settings->entityType() !== 'organization') {
            return [];
        }

        $node = [
            '@type' => 'Organization',
            '@id' => SchemaIds::entity($context),
            'name' => $context->settings->entityName(),
            'url' => rtrim($context->siteUrl, '/'),
        ];

        $logo = TwillMedia::fromMediaId($context->settings->logoMediaId());

        if ($logo !== null) {
            $node['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logo['url'],
                'width' => $logo['width'],
                'height' => $logo['height'],
            ];
        }

        $sameAs = $context->settings->socialProfiles();

        if ($sameAs !== []) {
            $node['sameAs'] = $sameAs;
        }

        return [$node];
    }
}
