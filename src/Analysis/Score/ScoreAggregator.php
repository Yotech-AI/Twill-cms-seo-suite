<?php

namespace TwillSeo\Analysis\Score;

use TwillSeo\Analysis\Assessment\AssessmentResult;

interface ScoreAggregator
{
    /**
     * @param  list<AssessmentResult>  $results
     */
    public function aggregate(array $results): OverallScore;
}
