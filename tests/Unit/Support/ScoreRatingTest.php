<?php

namespace TwillSeo\Tests\Unit\Support;

use TwillSeo\Support\ScoreRating;

/**
 * Pins the fix for a real bug: score 0 is a reserved engine sentinel for
 * "not available" (OverallScore::notAvailable() is the only place a 0 is
 * ever constructed — see SeoScoreAggregator's 1-floor and
 * ReadabilityPenaltyAggregator's <=1-counted-result branch), never a real
 * bad-but-scored verdict. It must read identically to null (grey), not fall
 * into the red band the way any other single-digit score would.
 */
it('colors a score', function (?int $score, string $expectedColor) {
    expect(ScoreRating::color($score))->toBe($expectedColor);
})->with([
    'never analyzed (null) is grey' => [null, ScoreRating::COLOR_GREY],
    'not available (0) is grey, not red' => [0, ScoreRating::COLOR_GREY],
    'the lowest real score is red' => [1, ScoreRating::COLOR_RED],
    'the bad upper bound is still red' => [40, ScoreRating::COLOR_RED],
    'just past the bad bound turns orange' => [41, ScoreRating::COLOR_ORANGE],
    'the ok upper bound is still orange' => [70, ScoreRating::COLOR_ORANGE],
    'just past the ok bound turns green' => [71, ScoreRating::COLOR_GREEN],
    'a perfect score is green' => [100, ScoreRating::COLOR_GREEN],
]);

it('labels a score', function (?int $score, string $expectedLabel) {
    expect(ScoreRating::label($score))->toBe($expectedLabel);
})->with([
    'never analyzed (null) reads "Not analyzed"' => [null, 'Not analyzed'],
    // Worded differently from null on purpose: 0 means the analysis DID run
    // and explicitly had too little to judge, not that it never ran.
    'not available (0) reads "Not available", not "Not analyzed"' => [0, 'Not available'],
    'a real score reads "N/100"' => [1, '1/100'],
    'a perfect score reads "100/100"' => [100, '100/100'],
]);

it('renders the dot markup for a not-available (0) score as grey, matching the null dot exactly except for the title', function () {
    $zeroDot = ScoreRating::dot(0);
    $nullDot = ScoreRating::dot(null);

    expect($zeroDot)->toBe(
        '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#b0b0b0" title="Not available"></span>'
    )->and($nullDot)->toBe(
        '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#b0b0b0" title="Not analyzed"></span>'
    )
        // Same background color either way — only the tooltip text tells
        // "never ran" and "ran, nothing to judge" apart.
        ->and($zeroDot)->toContain('background:#b0b0b0')
        ->and($nullDot)->toContain('background:#b0b0b0');
});

it('renders the dot markup for a real low score as red, not confusable with the 0/null grey dots', function () {
    expect(ScoreRating::dot(1))->toBe(
        '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#dc3232" title="1/100"></span>'
    );
});
