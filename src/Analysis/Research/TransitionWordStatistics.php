<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * How many sentences carry a transition word or phrase.
 *
 * A count rather than a share: the assessment divides by the sentence count it
 * already has, and a count is the number the feedback can also report.
 *
 * @implements Research<int>
 */
final class TransitionWordStatistics implements Research
{
    public function run(AnalysisContext $context): int
    {
        $transitions = $context->language->transitionWords();

        if ($transitions === null) {
            return 0;
        }

        return count(array_filter(
            $context->research(Sentences::class),
            fn (string $sentence) => $transitions->occursIn($sentence),
        ));
    }
}
