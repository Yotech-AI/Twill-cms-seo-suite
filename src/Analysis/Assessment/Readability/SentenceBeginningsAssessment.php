<?php

namespace TwillSeo\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\SentenceBeginnings;

/**
 * Whether several sentences in a row open the same way.
 *
 * Consecutive is the point: the same subject opening five scattered sentences
 * across a page is a topic, while three in a row is a rhythm the reader starts
 * hearing instead of the argument.
 */
final class SentenceBeginningsAssessment implements Assessment
{
    /** Two in a row is a coincidence; three is a pattern. */
    private const MAXIMUM_RUN = 2;

    public function identifier(): string
    {
        return 'sentenceBeginnings';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText() && $context->language->firstWordExceptions() !== null;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        [$word, $run] = self::longestRun($context->research(SentenceBeginnings::class));

        return $run > self::MAXIMUM_RUN
            ? $context->result($this, 3, 'repeated', ['word' => $word, 'count' => $run])
            : $context->result($this, 9, 'varied');
    }

    /**
     * @param  list<string>  $beginnings
     * @return array{0:string,1:int} the word and how many sentences in a row began with it
     */
    private static function longestRun(array $beginnings): array
    {
        $longestWord = '';
        $longest = 0;
        $current = 0;

        foreach ($beginnings as $index => $word) {
            $current = $index > 0 && $beginnings[$index - 1] === $word ? $current + 1 : 1;

            if ($current > $longest) {
                $longest = $current;
                $longestWord = $word;
            }
        }

        return [$longestWord, $longest];
    }
}
