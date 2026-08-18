<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\KeyphraseContentWords;

/**
 * How long the keyphrase is, in words that carry meaning.
 *
 * A missing keyphrase scores -999 rather than a bad grade. It is a veto: half
 * the SEO analysis is about a phrase that is not there, so the section has to
 * read red however well the rest of the page is written.
 */
final class KeyphraseLengthAssessment implements Assessment
{
    /** Longer than this and it stops being what a reader would type. */
    private const RECOMMENDED_MAXIMUM = 4;

    /** Past this it is a sentence, not a keyphrase. */
    private const ACCEPTABLE_MAXIMUM = 8;

    private const MISSING_KEYPHRASE_VETO = -999;

    public function identifier(): string
    {
        return 'keyphraseLength';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return true;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $count = count($context->research(KeyphraseContentWords::class));

        $params = [
            'count' => $count,
            'recommendedMax' => self::RECOMMENDED_MAXIMUM,
            'acceptableMax' => self::ACCEPTABLE_MAXIMUM,
        ];

        return match (true) {
            $count === 0 => $context->result($this, self::MISSING_KEYPHRASE_VETO, 'missing', $params),
            $count <= self::RECOMMENDED_MAXIMUM => $context->result($this, 9, 'good', $params),
            $count <= self::ACCEPTABLE_MAXIMUM => $context->result($this, 6, 'slightly_long', $params),
            default => $context->result($this, 3, 'too_long', $params),
        };
    }
}
