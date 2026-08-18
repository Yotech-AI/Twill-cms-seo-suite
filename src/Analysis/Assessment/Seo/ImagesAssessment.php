<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * Whether the text is illustrated at all. Only presence is judged here — how
 * well the images are described is the alt text assessment's business.
 */
final class ImagesAssessment implements Assessment
{
    public function identifier(): string
    {
        return 'images';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText();
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $count = count($context->content->images);

        return $count === 0
            ? $context->result($this, 3, 'none', ['count' => 0])
            : $context->result($this, 9, 'good', ['count' => $count]);
    }
}
