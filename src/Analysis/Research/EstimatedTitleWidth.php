<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Html\TextNormalizer;
use TwillSeo\Analysis\Support\PixelWidthEstimator;

/**
 * @implements Research<TitleWidthResult>
 */
final class EstimatedTitleWidth implements Research
{
    public function run(AnalysisContext $context): TitleWidthResult
    {
        $measured = $context->paper->titleWidth;

        if ($measured !== null) {
            return new TitleWidthResult(max(0, $measured), false);
        }

        // Collapsed first: a browser renders "  " as no title at all, and the
        // assessment has to see zero rather than the width of two spaces.
        return new TitleWidthResult(
            PixelWidthEstimator::estimate(TextNormalizer::collapseWhitespace($context->paper->title)),
            true,
        );
    }
}
