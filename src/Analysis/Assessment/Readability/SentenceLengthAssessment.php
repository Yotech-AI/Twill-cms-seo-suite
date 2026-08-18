<?php

namespace TwillSeo\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Config\SentenceLengthThresholds;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\SentenceLengthStatistics;

/**
 * What share of the sentences run past the length a reader can hold in one go.
 *
 * A share rather than a count, and never a verdict on any single sentence: a
 * long sentence among short ones is a rhetorical device, and a text made of
 * nothing but long ones is hard work. The limit itself belongs to the language,
 * because languages differ in how much they say per word.
 */
final class SentenceLengthAssessment implements Assessment
{
    public function __construct(private readonly SentenceLengthThresholds $thresholds) {}

    public function identifier(): string
    {
        return 'sentenceLength';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText();
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $limit = $context->language->sentenceLengthLimit();
        $lengths = $context->research(SentenceLengthStatistics::class);

        $tooLong = count(array_filter($lengths, fn (int $words) => $words > $limit));
        $percentage = $lengths === [] ? 0.0 : $tooLong * 100 / count($lengths);

        $params = ['percentage' => round($percentage, 1), 'limit' => $limit];

        return match (true) {
            $this->thresholds->isGood($percentage) => $context->result($this, 9, 'good', $params),
            $this->thresholds->isAcceptable($percentage) => $context->result($this, 6, 'some_long', $params),
            default => $context->result($this, 3, 'too_many_long', $params),
        };
    }
}
