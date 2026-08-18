<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * The meta description has to fill the space a search result gives it without
 * overflowing it. Too short wastes the slot; too long gets truncated
 * mid-sentence.
 */
final class MetaDescriptionLengthAssessment implements Assessment
{
    /** Roughly where Google truncates a description on desktop. */
    private const MAXIMUM_LENGTH = 156;

    /** Below this the description is not using the space it has. */
    private const MINIMUM_LENGTH = 121;

    public function identifier(): string
    {
        return 'metaDescriptionLength';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return true;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        // Characters, not bytes: a description of 130 accented characters is
        // 130 characters to a search engine and 260 bytes to PHP.
        $length = mb_strlen($context->paper->description);
        $params = ['length' => $length, 'max' => self::MAXIMUM_LENGTH];

        return match (true) {
            $length === 0 => $context->result($this, 1, 'missing', $params),
            $length < self::MINIMUM_LENGTH => $context->result($this, 6, 'too_short', $params),
            $length <= self::MAXIMUM_LENGTH => $context->result($this, 9, 'good', $params),
            default => $context->result($this, 6, 'too_long', $params),
        };
    }
}
