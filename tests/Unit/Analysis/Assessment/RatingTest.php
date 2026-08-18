<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment;

use TwillSeo\Analysis\Assessment\Rating;
use TwillSeo\Analysis\Assessment\ResultCategory;

it('maps a score to a rating', function (int $score, Rating $expected) {
    expect(Rating::fromScore($score))->toBe($expected);
})->with([
    'error sentinel' => [-1, Rating::Error],
    'feedback sentinel' => [0, Rating::Feedback],
    'keyphrase veto' => [-999, Rating::Bad],
    'heavy penalty' => [-50, Rating::Bad],
    'penalty' => [-10, Rating::Bad],
    'lowest positive' => [1, Rating::Bad],
    'bad upper bound' => [4, Rating::Bad],
    'ok lower bound' => [5, Rating::Ok],
    'ok upper bound' => [7, Rating::Ok],
    'good lower bound' => [8, Rating::Good],
    'good' => [9, Rating::Good],
]);

it('maps a rating to a result category', function (Rating $rating, ResultCategory $expected) {
    expect(ResultCategory::fromRating($rating))->toBe($expected);
})->with([
    [Rating::Bad, ResultCategory::Problems],
    [Rating::Ok, ResultCategory::Improvements],
    [Rating::Good, ResultCategory::Good],
    [Rating::Feedback, ResultCategory::Feedback],
    [Rating::Error, ResultCategory::Errors],
]);
