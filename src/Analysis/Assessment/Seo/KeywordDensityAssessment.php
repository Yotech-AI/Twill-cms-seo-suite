<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\KeyphraseContentWords;
use TwillSeo\Analysis\Research\KeywordCount;
use TwillSeo\Analysis\Research\WordCount;

/**
 * How much of the text is the keyphrase.
 *
 * The two ends of this scale are not symmetrical, and the scores say so. Too
 * little is a missed opportunity and scores a plain fail; too much is keyword
 * stuffing, which search engines actively punish, so it scores a penalty that
 * drags the whole SEO total down with it.
 */
final class KeywordDensityAssessment implements Assessment
{
    /** Below this the keyphrase reads as incidental rather than as the subject. */
    private const MINIMUM_DENSITY = 0.5;

    private const MAXIMUM_DENSITY = 3.0;

    /** Past this it is not merely overdone, it is stuffing. */
    private const OVERUSE_DENSITY = 4.0;

    /** Telling an author to use the keyphrase once would be advice to ignore it. */
    private const MINIMUM_RECOMMENDATION = 2;

    public function identifier(): string
    {
        return 'keywordDensity';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText() && $context->paper->hasKeyword();
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $matches = $context->research(KeywordCount::class);
        $words = $context->research(WordCount::class);

        // Multiplied before dividing: (12 / 400) * 100 lands a hair above 3.0
        // in binary floating point, which would score a perfect text as
        // overused.
        $density = $words > 0 ? $matches * 100 / $words : 0.0;

        $params = [
            'count' => $matches,
            'density' => round($density, 1),
            'recommendedMax' => self::recommendedMaximum($words, count($context->research(KeyphraseContentWords::class))),
        ];

        return match (true) {
            $matches === 0 => $context->result($this, 4, 'none', $params),
            $density < self::MINIMUM_DENSITY => $context->result($this, 4, 'under', $params),
            $density <= self::MAXIMUM_DENSITY => $context->result($this, 9, 'good', $params),
            $density <= self::OVERUSE_DENSITY => $context->result($this, -10, 'over', $params),
            default => $context->result($this, -50, 'way_over', $params),
        };
    }

    /**
     * How many uses the feedback tells the author to aim for: the maximum
     * density, scaled down as the keyphrase gets longer.
     *
     * A long keyphrase takes up more of the text per use, so the same density
     * is reached with fewer of them — advising the same count for a one word
     * and a five word phrase would advise stuffing for the second.
     */
    private static function recommendedMaximum(int $words, int $keyphraseLength): int
    {
        $scale = 100 * (0.7 + $keyphraseLength / 3);

        return max(self::MINIMUM_RECOMMENDATION, (int) floor(self::MAXIMUM_DENSITY * $words / $scale));
    }
}
