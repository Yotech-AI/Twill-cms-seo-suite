<?php

namespace TwillSeo\Services\Schema\Pieces;

use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Schema\SchemaIds;

/**
 * The site's schema.org identity when settings entityType() is 'person' —
 * see OrganizationPiece for the (default) alternative. Only one of the two
 * ever contributes a node for a given install.
 */
final class PersonPiece implements GraphPiece
{
    public function pieces(SchemaContext $context): array
    {
        if ($context->settings->entityType() !== 'person') {
            return [];
        }

        $node = [
            '@type' => 'Person',
            '@id' => SchemaIds::entity($context),
            'name' => $context->settings->entityName(),
            'url' => rtrim($context->siteUrl, '/'),
        ];

        // The settings logo doubles as the Person's image (the same
        // "avatar/portrait used to represent this entity" concept
        // OrganizationPiece uses it for) rather than inventing a second
        // settings field just for the Person case. A file-library logo
        // carries no pixel dimensions — width/height are optional here.
        $logo = $context->settings->logo();

        if ($logo !== null) {
            $node['image'] = ['@type' => 'ImageObject', 'url' => $logo['url']]
                + array_intersect_key($logo, ['width' => true, 'height' => true]);
        }

        $sameAs = $context->settings->socialProfiles();

        if ($sameAs !== []) {
            $node['sameAs'] = $sameAs;
        }

        return [$node];
    }
}
