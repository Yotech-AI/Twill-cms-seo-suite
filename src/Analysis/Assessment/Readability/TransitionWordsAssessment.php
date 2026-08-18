<?php

namespace TwillSeo\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\Sentences;
use TwillSeo\Analysis\Research\TransitionWordStatistics;
use TwillSeo\Analysis\Research\WordCount;

/**
 * Whether the text signposts how its sentences follow from one another.
 *
 * Short texts are exempt outright rather than judged leniently: a product
 * blurb of three sentences does not need "furthermore", and telling its author
 * otherwise would make the copy worse.
 */
final class TransitionWordsAssessment implements Assessment
{
    /** Below this a text is too short for signposting to be missing. */
    private const JUDGED_FROM_WORDS = 200;

    private const MINIMUM_PERCENTAGE = 20;

    private const GOOD_PERCENTAGE = 30;

    public function identifier(): string
    {
        return 'transitionWords';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText() && $context->language->transitionWords() !== null;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        if ($context->research(WordCount::class) < self::JUDGED_FROM_WORDS) {
            return $context->result($this, 9, 'short_text', ['percentage' => 0.0]);
        }

        $sentences = count($context->research(Sentences::class));
        $withTransition = $context->research(TransitionWordStatistics::class);

        $percentage = $sentences === 0 ? 0.0 : $withTransition * 100 / $sentences;
        $params = ['percentage' => round($percentage, 1)];

        return match (true) {
            $percentage < self::MINIMUM_PERCENTAGE => $context->result($this, 3, 'few', $params),
            $percentage < self::GOOD_PERCENTAGE => $context->result($this, 6, 'some', $params),
            default => $context->result($this, 9, 'good', $params),
        };
    }
}
