<?php

use TwillSeo\Analysis\Assessment\Seo\TitleWidthAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

it('scores a browser measured title by its width in pixels', function (int $width, int $score, string $branch) {
    $assessment = new TitleWidthAssessment;
    $result = $assessment->assess(AnalysisFactory::context(
        Paper::builder()->title('A title')->titleWidth($width)->build()
    ));

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.title_width.{$branch}")
        ->and($result->messageParams)->toBe(['width' => $width, 'max' => 600, 'estimated' => false]);
})->with([
    'nothing at all' => [0, 1, 'missing'],
    'one pixel' => [1, 9, 'good'],
    'good upper bound' => [600, 9, 'good'],
    'too wide lower bound' => [601, 3, 'too_wide'],
    'far too wide' => [900, 3, 'too_wide'],
]);

it('falls back to an estimate when the browser measured nothing', function () {
    $assessment = new TitleWidthAssessment;
    $result = $assessment->assess(AnalysisFactory::context(
        Paper::builder()->title('The complete guide to Twill CMS and SEO')->build()
    ));

    expect($result->score)->toBe(9)
        ->and($result->messageParams['estimated'])->toBeTrue()
        ->and($result->messageParams['width'])->toBeGreaterThan(300);
});

it('treats an empty title as a missing one', function (string $title) {
    $assessment = new TitleWidthAssessment;
    $result = $assessment->assess(AnalysisFactory::context(Paper::builder()->title($title)->build()));

    expect($result->score)->toBe(1)
        ->and($result->messageKey)->toBe('twill-seo::analysis.title_width.missing');
})->with([
    'no title' => [''],
    'whitespace only' => ["  \n "],
]);

it('always applies, title or not', function () {
    $assessment = new TitleWidthAssessment;

    expect($assessment->identifier())->toBe('titleWidth')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    $assessment = new TitleWidthAssessment;
    $result = $assessment->assess(AnalysisFactory::context(
        Paper::builder()->title('A title')->titleWidth(700)->build()
    ));

    expect($result->text)
        ->toBe('The SEO title is around 700 pixels wide and will be cut off after 600. Shorten it so the whole title stays visible.');
});
