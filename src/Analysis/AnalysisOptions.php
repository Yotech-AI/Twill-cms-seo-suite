<?php

namespace TwillSeo\Analysis;

/**
 * What to run for one paper. The section switches exist so a caller that only
 * needs one number — a bulk re-score, a list view — does not pay for the rest.
 */
final readonly class AnalysisOptions
{
    public function __construct(
        public bool $cornerstone = false,
        public bool $seo = true,
        public bool $readability = true,
        public bool $insights = true,
    ) {}
}
