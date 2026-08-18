<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\EstimatedTitleWidth;

/**
 * Titles are truncated by pixel width, not by character count: "Illinois
 * Wineries" and "lilliput mill" have the same length and nothing like the same
 * width. The width comes from the browser when there is one and from an
 * estimate when there is not, and the result says which it was.
 */
final class TitleWidthAssessment implements Assessment
{
    /** Roughly where a desktop search result cuts a title off. */
    private const MAXIMUM_WIDTH_PX = 600;

    public function identifier(): string
    {
        return 'titleWidth';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return true;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $width = $context->research(EstimatedTitleWidth::class);

        $params = [
            'width' => $width->px,
            'max' => self::MAXIMUM_WIDTH_PX,
            'estimated' => $width->estimated,
        ];

        return match (true) {
            $width->px === 0 => $context->result($this, 1, 'missing', $params),
            $width->px <= self::MAXIMUM_WIDTH_PX => $context->result($this, 9, 'good', $params),
            default => $context->result($this, 3, 'too_wide', $params),
        };
    }
}
