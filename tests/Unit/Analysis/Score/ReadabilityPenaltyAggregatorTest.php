<?php

namespace TwillSeo\Tests\Unit\Analysis\Score;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Score\OverallRating;
use TwillSeo\Analysis\Score\ReadabilityPenaltyAggregator;

/**
 * @param  list<int>  $scores
 * @return list<AssessmentResult>
 */
function readabilityResultsWithScores(array $scores): array
{
    return array_map(
        fn (int $score, int $index) => new AssessmentResult('r'.$index, $score, 'key', [], 'text'),
        $scores,
        array_keys($scores),
    );
}

it('reports no score at all with one counted result or fewer', function (array $scores) {
    $overall = (new ReadabilityPenaltyAggregator)->aggregate(readabilityResultsWithScores($scores));

    expect($overall->score)->toBe(0)
        ->and($overall->rating)->toBe(OverallRating::NotAvailable);
})->with([
    'no results' => [[]],
    'a single good result' => [[9]],
    'a single bad result' => [[3]],
    // A lone counted result is the "there is no content" assessment; the rest
    // never ran, so a green 90 here would be a lie.
    'feedback padding does not make it two' => [[9, 0, -1]],
]);

it('bands the total penalty into a readability score', function (array $scores, int $expectedScore, OverallRating $expectedRating) {
    $overall = (new ReadabilityPenaltyAggregator)->aggregate(readabilityResultsWithScores($scores));

    expect($overall->score)->toBe($expectedScore)
        ->and($overall->rating)->toBe($expectedRating);
})->with([
    'no penalty at all' => [[9, 9], 90, OverallRating::Good],
    'two ok results score four' => [[6, 6], 90, OverallRating::Good],
    'good upper bound of four' => [[6, 6, 9, 9], 90, OverallRating::Good],
    'ok lower bound of five' => [[3, 6, 9], 60, OverallRating::Ok],
    'ok upper bound of six' => [[3, 3, 9], 60, OverallRating::Ok],
    'bad lower bound of seven' => [[3, 6, 6], 30, OverallRating::Bad],
    'all bad' => [[3, 3, 3, 3], 30, OverallRating::Bad],
    'feedback and errors add no penalty' => [[9, 9, 0, -1], 90, OverallRating::Good],
]);
