<?php

namespace TwillSeo\Analysis\Score;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Rating;

/**
 * Readability is not averaged: every result that is less than good adds a
 * penalty, and the total penalty picks one of three fixed scores. A single
 * badly failing check should visibly move the needle, which an average over a
 * dozen checks would not do.
 */
final class ReadabilityPenaltyAggregator implements ScoreAggregator
{
    private const PENALTY_BAD = 3;

    private const PENALTY_OK = 2;

    private const GOOD_PENALTY_LIMIT = 4;

    private const OK_PENALTY_LIMIT = 6;

    /**
     * @param  list<AssessmentResult>  $results
     */
    public function aggregate(array $results): OverallScore
    {
        $counted = array_values(array_filter($results, fn (AssessmentResult $r) => $r->countsTowardScore));

        // One counted result means only the "there is no content" assessment
        // ran; scoring that 90 would show green for an empty paper.
        if (count($counted) <= 1) {
            return OverallScore::notAvailable();
        }

        $penalty = 0;

        foreach ($counted as $result) {
            $penalty += match ($result->rating) {
                Rating::Bad => self::PENALTY_BAD,
                Rating::Ok => self::PENALTY_OK,
                // Good adds nothing; Feedback and Error are never counted.
                default => 0,
            };
        }

        return match (true) {
            $penalty <= self::GOOD_PENALTY_LIMIT => new OverallScore(90, OverallRating::Good),
            $penalty <= self::OK_PENALTY_LIMIT => new OverallScore(60, OverallRating::Ok),
            default => new OverallScore(30, OverallRating::Bad),
        };
    }
}
