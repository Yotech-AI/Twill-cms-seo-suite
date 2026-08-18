<?php

namespace TwillSeo\Services\Schema\Pieces;

use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Schema\SchemaIds;

/**
 * Purely a renderer: PageSeo::breadcrumbs is already the final, resolved
 * list (registry callback result, or SeoResolver's own Home -> current-page
 * default — see SeoResolver::resolveBreadcrumbs()) by the time this piece
 * ever sees it, so there is no further fallback decision to make here.
 */
final class BreadcrumbListPiece implements GraphPiece
{
    public function pieces(SchemaContext $context): array
    {
        $breadcrumbs = $context->pageSeo->breadcrumbs;

        if ($breadcrumbs === []) {
            return [];
        }

        $items = [];

        foreach (array_values($breadcrumbs) as $position => $crumb) {
            [$title, $url] = $crumb;

            $item = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $title,
            ];

            // No "item" key at all on the last crumb (the current page) —
            // schema.org's own convention for "this is where you are", not
            // an omitted-but-implied self-link.
            if ($url !== null) {
                $item['item'] = $url;
            }

            $items[] = $item;
        }

        return [[
            '@type' => 'BreadcrumbList',
            '@id' => SchemaIds::breadcrumb($context),
            'itemListElement' => $items,
        ]];
    }
}
