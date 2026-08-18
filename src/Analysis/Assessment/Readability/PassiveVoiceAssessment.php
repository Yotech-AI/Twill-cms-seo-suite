<?php

namespace TwillSeo\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\PassiveVoiceStatistics;
use TwillSeo\Analysis\Research\Sentences;

/**
 * How much of the text hides who is doing what.
 *
 * A share, and a generous one: the passive is the right choice when the actor
 * is unknown or beside the point, so a tenth of the sentences passes without
 * comment. It is a text made mostly of it that leaves a reader guessing.
 */
final class PassiveVoiceAssessment implements Assessment
{
    private const GOOD_PERCENTAGE = 10;

    private const ACCEPTABLE_PERCENTAGE = 15;

    public function identifier(): string
    {
        return 'passiveVoice';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText() && $context->language->passiveVoice() !== null;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $sentences = count($context->research(Sentences::class));
        $passive = $context->research(PassiveVoiceStatistics::class);

        $percentage = $sentences === 0 ? 0.0 : $passive * 100 / $sentences;
        $params = ['percentage' => round($percentage, 1), 'count' => $passive];

        return match (true) {
            $percentage <= self::GOOD_PERCENTAGE => $context->result($this, 9, 'good', $params),
            $percentage <= self::ACCEPTABLE_PERCENTAGE => $context->result($this, 6, 'some', $params),
            default => $context->result($this, 3, 'too_many', $params),
        };
    }
}
