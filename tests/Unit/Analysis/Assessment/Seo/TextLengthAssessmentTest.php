<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Config\TextLengthThresholds;
use TwillSeo\Analysis\Assessment\Seo\TextLengthAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function assessTextOfWords(int $words, ?TextLengthThresholds $thresholds = null): AssessmentResult
{
    $assessment = new TextLengthAssessment($thresholds ?? TextLengthThresholds::default());
    $text = $words === 0 ? '' : '<p>'.implode(' ', array_fill(0, $words, 'word')).'</p>';

    return $assessment->assess(AnalysisFactory::context(Paper::builder()->text($text)->build()));
}

it('scores the text by word count', function (int $words, int $score, string $branch) {
    $result = assessTextOfWords($words);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.text_length.{$branch}")
        ->and($result->messageParams)->toBe(['words' => $words, 'recommended' => 300]);
})->with([
    'nothing at all' => [0, -20, 'far_too_short'],
    'far too short upper bound' => [99, -20, 'far_too_short'],
    'very short lower bound' => [100, -10, 'very_short'],
    'very short upper bound' => [199, -10, 'very_short'],
    'short lower bound' => [200, 3, 'short'],
    'short upper bound' => [249, 3, 'short'],
    'slightly short lower bound' => [250, 6, 'slightly_short'],
    'slightly short upper bound' => [299, 6, 'slightly_short'],
    'good lower bound' => [300, 9, 'good'],
    'well past good' => [1000, 9, 'good'],
]);

it('holds cornerstone content to a higher bar', function (int $words, int $score, string $branch) {
    $result = assessTextOfWords($words, TextLengthThresholds::cornerstone());

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.text_length.{$branch}")
        ->and($result->messageParams)->toBe(['words' => $words, 'recommended' => 900]);
})->with([
    'far too short upper bound' => [399, -20, 'far_too_short'],
    'slightly short lower bound' => [400, 6, 'slightly_short'],
    'slightly short upper bound' => [899, 6, 'slightly_short'],
    'good lower bound' => [900, 9, 'good'],
    // A length that passes the default bar comfortably still fails here.
    'a normal good length is only slightly short' => [500, 6, 'slightly_short'],
]);

it('always applies, text or not', function () {
    $assessment = new TextLengthAssessment(TextLengthThresholds::default());

    expect($assessment->identifier())->toBe('textLength')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    expect(assessTextOfWords(120)->text)
        ->toBe('The text is 120 words long, far below the recommended 300. Write substantially more before publishing.');
});
