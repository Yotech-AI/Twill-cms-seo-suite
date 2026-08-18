<?php

namespace TwillSeo\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * The one readability check that only speaks up when there is nothing to check.
 *
 * It exists so an empty paper gets an honest "there is nothing here" instead of
 * a page of green bullets from assessments that all trivially pass on no text.
 * Applicable only below the threshold, so on a real text it produces no result
 * at all — which is also what leaves the readability aggregator with a single
 * counted result on an empty paper, the signal it uses to report grey.
 */
final class TextPresenceAssessment implements Assessment
{
    /** Characters, not words: below this there is nothing to judge either way. */
    private const MINIMUM_CHARACTERS = 50;

    public function identifier(): string
    {
        return 'textPresence';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return mb_strlen(trim($context->content->plainText)) < self::MINIMUM_CHARACTERS;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        return $context->result($this, 3, 'too_little');
    }
}
