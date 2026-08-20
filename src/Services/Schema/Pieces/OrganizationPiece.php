<?php

namespace TwillSeo\Services\Schema\Pieces;

use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Schema\SchemaIds;

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

        // A file-library logo (the settings screen's picker) carries no
        // pixel dimensions — width/height are optional on ImageObject.
        $logo = $context->settings->logo();

        if ($logo !== null) {
            $node['logo'] = ['@type' => 'ImageObject', 'url' => $logo['url']]
                + array_intersect_key($logo, ['width' => true, 'height' => true]);
        }

        $sameAs = $context->settings->socialProfiles();

        if ($sameAs !== []) {
            $node['sameAs'] = $sameAs;
        }

        return [$node];
    }
}
