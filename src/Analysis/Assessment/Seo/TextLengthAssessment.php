<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Config\TextLengthThresholds;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\WordCount;

/**
 * Thin content is the one problem no other assessment can compensate for,
 * which is why the short branches score negative: they are penalties that pull
 * the whole SEO score down rather than just failing one check.
 */
final class TextLengthAssessment implements Assessment
{
    public function __construct(private readonly TextLengthThresholds $thresholds) {}

    public function identifier(): string
    {
        return 'textLength';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return true;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $words = $context->research(WordCount::class);
        $tier = $this->thresholds->evaluate($words);

        return $context->result($this, $tier->score, $tier->branch, [
            'words' => $words,
            'recommended' => $this->thresholds->recommended,
        ]);
    }
}
