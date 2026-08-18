<?php

namespace TwillSeo\Analysis\Assessor;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Score\OverallScore;

final readonly class AssessorResult
{
    /**
     * @param  list<AssessmentResult>  $results  in registration order, which is the order the
     *                                           panel shows them in
     */
    public function __construct(
        public array $results,
        public OverallScore $overall,
    ) {}
}
