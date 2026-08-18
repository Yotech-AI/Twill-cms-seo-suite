<?php

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Rating;
use TwillSeo\Analysis\Assessment\ResultCategory;

it('derives rating, category and score participation from the score', function (int $score, Rating $rating, ResultCategory $category, bool $counts) {
    $result = new AssessmentResult('textLength', $score, 'twill-seo::analysis.text_length.good', [], 'text');

    expect($result->rating)->toBe($rating)
        ->and($result->category)->toBe($category)
        ->and($result->countsTowardScore)->toBe($counts);
})->with([
    'feedback is excluded from the score' => [0, Rating::Feedback, ResultCategory::Feedback, false],
    'error is excluded from the score' => [-1, Rating::Error, ResultCategory::Errors, false],
    'a penalty still counts' => [-10, Rating::Bad, ResultCategory::Problems, true],
    'a keyphrase veto still counts' => [-999, Rating::Bad, ResultCategory::Problems, true],
    'a low positive counts' => [1, Rating::Bad, ResultCategory::Problems, true],
    'an ok score counts' => [6, Rating::Ok, ResultCategory::Improvements, true],
    'a good score counts' => [9, Rating::Good, ResultCategory::Good, true],
]);

it('serialises to the frozen result shape', function () {
    $result = new AssessmentResult(
        'textLength',
        9,
        'twill-seo::analysis.text_length.good',
        ['words' => 420],
        'The text is 420 words long.',
    );

    expect($result->jsonSerialize())->toBe([
        'id' => 'textLength',
        'score' => 9,
        'rating' => 'good',
        'category' => 'good',
        'text' => 'The text is 420 words long.',
        'messageKey' => 'twill-seo::analysis.text_length.good',
        'params' => ['words' => 420],
    ]);
});

it('encodes to JSON with the same keys in the same order', function () {
    $result = new AssessmentResult('images', 3, 'twill-seo::analysis.images.none', ['count' => 0], 'No images.');

    expect(json_encode($result))->toBe(
        '{"id":"images","score":3,"rating":"bad","category":"problems","text":"No images.","messageKey":"twill-seo::analysis.images.none","params":{"count":0}}'
    );
});
