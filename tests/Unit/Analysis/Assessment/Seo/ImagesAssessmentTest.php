<?php

use TwillSeo\Analysis\Assessment\Seo\ImagesAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

it('scores the text by how many images it holds', function (string $html, int $count, int $score, string $branch) {
    $assessment = new ImagesAssessment;
    $result = $assessment->assess(AnalysisFactory::context(Paper::builder()->text($html)->build()));

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.images.{$branch}")
        ->and($result->messageParams)->toBe(['count' => $count]);
})->with([
    'no images' => ['<p>Just words.</p>', 0, 3, 'none'],
    'one image' => ['<p>Words <img src="/a.png" alt="a"></p>', 1, 9, 'good'],
    'an image with no alt still counts as an image' => ['<p><img src="/a.png"></p>', 1, 9, 'good'],
    'several images' => ['<p><img src="/a.png"><img src="/b.png"><img src="/c.png"></p>', 3, 9, 'good'],
]);

it('has nothing to say about a paper with no text', function () {
    $assessment = new ImagesAssessment;

    expect($assessment->identifier())->toBe('images')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeFalse()
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->text('<p>Words.</p>')->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    $assessment = new ImagesAssessment;
    $result = $assessment->assess(AnalysisFactory::context(Paper::builder()->text('<p>Just words.</p>')->build()));

    expect($result->text)
        ->toBe('The text contains no images. Add at least one relevant image or video so the page is not a wall of text.');
});
