<?php

namespace TwillSeo\Services\Schema\Pieces;

use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Schema\SchemaIds;
use TwillSeo\Support\TwillMedia;

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

        // "logo" for a Person node too (schema.org allows it on Person, and
        // it is the same "avatar/portrait used to represent this entity"
        // concept OrganizationPiece uses it for) rather than inventing a
        // second settings field just for the Person case.
        $logo = TwillMedia::fromMediaId($context->settings->logoMediaId());

        if ($logo !== null) {
            $node['image'] = [
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
