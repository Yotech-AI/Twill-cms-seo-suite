<?php

namespace TwillSeo\Analysis\Score;

use TwillSeo\Analysis\Assessment\AssessmentResult;

/**
 * Averages the SEO results against the 9-point maximum every assessment can
 * award, then bands the percentage.
 */
final class SeoScoreAggregator implements ScoreAggregator
{
    private const MAX_SCORE_PER_RESULT = 9;

    private const BAD_UPPER_BOUND = 40;

    private const OK_UPPER_BOUND = 70;

    /**
     * @param  list<AssessmentResult>  $results
     */
    public function aggregate(array $results): OverallScore
    {
        $counted = array_values(array_filter($results, fn (AssessmentResult $r) => $r->countsTowardScore));

        if ($counted === []) {
            return OverallScore::notAvailable();
        }

        $total = array_sum(array_map(fn (AssessmentResult $r) => $r->score, $counted));
        $raw = (int) round($total * 100 / (count($counted) * self::MAX_SCORE_PER_RESULT));

        // Floored at 1, not 0: a paper carrying a -999 veto has to read red.
        // Score 0 means "nothing was assessed" and must stay unreachable here.
        $score = max(1, min(100, $raw));

        return new OverallScore($score, match (true) {
            $score <= self::BAD_UPPER_BOUND => OverallRating::Bad,
            $score <= self::OK_UPPER_BOUND => OverallRating::Ok,
            default => OverallRating::Good,
        });
    }
}
