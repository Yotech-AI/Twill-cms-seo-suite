<?php

namespace TwillSeo\Services\Schema\Pieces;

use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Schema\SchemaIds;

/**
 * Always contributes exactly one node — the site itself, not any one page —
 * with an optional SearchAction (sitelinks searchbox) when settings turns it
 * on and gives it a URL template.
 */
final class WebSitePiece implements GraphPiece
{
    public function pieces(SchemaContext $context): array
    {
        $node = [
            '@type' => 'WebSite',
            '@id' => SchemaIds::website($context),
            'url' => rtrim($context->siteUrl, '/'),
            'name' => $context->settings->siteName(),
            'publisher' => ['@id' => SchemaIds::entity($context)],
        ];

        $urlTemplate = $context->settings->searchActionEnabled() ? $context->settings->searchUrlTemplate() : null;

        if ($urlTemplate !== null) {
            $node['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $urlTemplate,
                ],
                // Fixed schema.org literal naming the {search_term_string}
                // placeholder inside the template above as required input.
                'query-input' => 'required name=search_term_string',
            ];
        }

        return [$node];
    }
}
