<?php

namespace TwillSeo\Tests\Unit\Analysis\Score;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Score\OverallRating;
use TwillSeo\Analysis\Score\SeoScoreAggregator;

/**
 * @param  list<int>  $scores
 * @return list<AssessmentResult>
 */
function seoResultsWithScores(array $scores): array
{
    return array_map(
        fn (int $score, int $index) => new AssessmentResult('a'.$index, $score, 'key', [], 'text'),
        $scores,
        array_keys($scores),
    );
}

/**
 * $count counted scores adding up to exactly $total, spread as evenly as the
 * remainder allows. Used to hit an exact aggregate percentage: the band edges
 * are only meaningful if the score really lands on 40 rather than near it.
 *
 * @return list<int>
 */
function scoresSummingTo(int $total, int $count): array
{
    $scores = array_fill(0, $count, intdiv($total, $count));

    for ($i = 0; $i < $total % $count; $i++) {
        $scores[$i]++;
    }

    return $scores;
}

it('reports no score at all when nothing counts', function (array $scores) {
    $overall = (new SeoScoreAggregator)->aggregate(seoResultsWithScores($scores));

    expect($overall->score)->toBe(0)
        ->and($overall->rating)->toBe(OverallRating::NotAvailable);
})->with([
    'no results' => [[]],
    'only feedback' => [[0, 0]],
    'only errors' => [[-1]],
    'feedback and errors' => [[0, -1, 0]],
]);

it('averages the counted scores against the nine point maximum', function (array $scores, int $expected) {
    expect((new SeoScoreAggregator)->aggregate(seoResultsWithScores($scores))->score)->toBe($expected);
})->with([
    'a single perfect result' => [[9], 100],
    'all perfect' => [[9, 9, 9], 100],
    'one ok result rounds up from 66.67' => [[6], 67],
    'mixed results round to the nearest point' => [[3, 9], 67],
    'feedback and errors never dilute the average' => [[9, 0, -1], 100],
    'the floor keeps a vetoed paper red rather than grey' => [[-999], 1],
    'penalties floor at one instead of going negative' => [[-20, -10, 9], 1],
]);

it('bands the aggregate score into an overall rating', function (int $total, int $count, int $expectedScore, OverallRating $expectedRating) {
    $overall = (new SeoScoreAggregator)->aggregate(seoResultsWithScores(scoresSummingTo($total, $count)));

    expect($overall->score)->toBe($expectedScore)
        ->and($overall->rating)->toBe($expectedRating);
})->with([
    'bad upper bound' => [72, 20, 40, OverallRating::Bad],
    'ok lower bound' => [74, 20, 41, OverallRating::Ok],
    'ok upper bound' => [126, 20, 70, OverallRating::Ok],
    'good lower bound' => [128, 20, 71, OverallRating::Good],
    'the lowest reachable score is still bad' => [20, 20, 11, OverallRating::Bad],
    'a perfect paper is good' => [180, 20, 100, OverallRating::Good],
]);
