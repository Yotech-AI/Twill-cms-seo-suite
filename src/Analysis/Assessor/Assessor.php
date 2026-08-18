<?php

namespace TwillSeo\Analysis\Assessor;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Score\ScoreAggregator;

/**
 * Runs one set of assessments over a paper and aggregates the outcome.
 *
 * Order is registration order throughout: the panel lists results in the order
 * they were registered, so shuffling the constructor array visibly reorders
 * the UI.
 */
final readonly class Assessor
{
    /**
     * @param  list<Assessment>  $assessments
     */
    public function __construct(
        private array $assessments,
        private ScoreAggregator $aggregator,
    ) {}

    public function run(AnalysisContext $context): AssessorResult
    {
        $results = [];

        foreach ($this->assessments as $assessment) {
            // An assessment with nothing to say is left out entirely rather
            // than scored, so it neither helps nor hurts the total.
            if ($assessment->isApplicable($context)) {
                $results[] = $assessment->assess($context);
            }
        }

        return new AssessorResult($results, $this->aggregator->aggregate($results));
    }
}
