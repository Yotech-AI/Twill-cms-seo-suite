<?php

namespace TwillSeo\Services\Schema;

use TwillSeo\Services\Meta\PageSeo;
use TwillSeo\Services\Settings\SeoSettings;

/**
 * Everything a GraphPiece needs to build its node(s) for the current
 * request — assembled once by the Head component right before it calls
 * SeoManager::graph()->build(), never constructed by a piece itself.
 */
final readonly class SchemaContext
{
    public function __construct(
        public PageSeo $pageSeo,
        public SeoSettings $settings,
        public string $siteUrl,
        public string $locale,
    ) {}
}
