<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Seo\SingleH1Assessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

it('scores the text by how many H1 headings it holds', function (string $html, int $count, int $score, string $branch) {
    $assessment = new SingleH1Assessment;
    $result = $assessment->assess(AnalysisFactory::context(Paper::builder()->text($html)->build()));

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.single_h1.{$branch}")
        ->and($result->messageParams)->toBe(['count' => $count]);
})->with([
    'no H1 at all' => ['<h2>Sub</h2><p>Words.</p>', 0, 8, 'good'],
    'exactly one H1' => ['<h1>Title</h1><p>Words.</p>', 1, 8, 'good'],
    'two H1 headings' => ['<h1>One</h1><h1>Two</h1>', 2, 1, 'multiple'],
    'three H1 headings' => ['<h1>One</h1><h1>Two</h1><h1>Three</h1>', 3, 1, 'multiple'],
    'deeper headings do not count' => ['<h1>One</h1><h2>Two</h2><h3>Three</h3>', 1, 8, 'good'],
]);

it('has nothing to say about a paper with no text', function () {
    $assessment = new SingleH1Assessment;

    expect($assessment->identifier())->toBe('singleH1')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeFalse()
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->text('<p>Words.</p>')->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    $assessment = new SingleH1Assessment;
    $result = $assessment->assess(AnalysisFactory::context(Paper::builder()->text('<h1>One</h1><h1>Two</h1>')->build()));

    expect($result->text)
        ->toBe('The text contains 2 H1 headings. Keep one H1 for the page title and demote the rest to H2 or lower.');
});
