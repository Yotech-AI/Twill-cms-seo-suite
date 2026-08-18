<?php

namespace TwillSeo\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\ParagraphLengths;

/**
 * Whether any paragraph is a wall of text.
 *
 * Only the longest one is reported: an author fixes the worst paragraph first,
 * and naming every offender at once turns one piece of advice into a list to
 * ignore.
 */
final class ParagraphTooLongAssessment implements Assessment
{
    private const MAXIMUM_WORDS = 150;

    private const SLIGHTLY_LONG_WORDS = 200;

    public function identifier(): string
    {
        return 'paragraphTooLong';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText();
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $lengths = $context->research(ParagraphLengths::class);

        // A text of nothing but headings has no paragraph to be too long,
        // which is a pass rather than a missing answer.
        $longest = $lengths === [] ? 0 : max($lengths);
        $params = ['words' => $longest, 'max' => self::MAXIMUM_WORDS];

        return match (true) {
            $longest <= self::MAXIMUM_WORDS => $context->result($this, 9, 'good', $params),
            $longest <= self::SLIGHTLY_LONG_WORDS => $context->result($this, 6, 'slightly_long', $params),
            default => $context->result($this, 3, 'too_long', $params),
        };
    }
}
