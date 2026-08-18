<?php

namespace TwillSeo\Services;

use TwillSeo\Analysis\Paper\Paper;

/**
 * What PaperFactory::fromModel() returns: the Paper plus where its text came
 * from. `override` when a live `content_override` field bypassed the content
 * resolver entirely; otherwise whatever ResolvedContent::$source the
 * resolver reported (rendered_blocks | content_fields | mixed | empty).
 */
final readonly class PaperBuild
{
    public function __construct(
        public Paper $paper,
        public string $contentSource,
    ) {}
}
