<?php

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\InternalLinksAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function assessInternalLinks(string $html): AssessmentResult
{
    $assessment = new InternalLinksAssessment;

    return $assessment->assess(AnalysisFactory::context(
        Paper::builder()->permalink('https://example.test/page')->text($html)->build()
    ));
}

it('scores internal links by how many of them are nofollow', function (string $html, int $total, int $nofollow, int $score, string $branch) {
    $result = assessInternalLinks($html);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.internal_links.{$branch}")
        ->and($result->messageParams)->toBe(['total' => $total, 'nofollow' => $nofollow]);
})->with([
    'no links at all' => ['<p>Just words.</p>', 0, 0, 3, 'none'],
    'only external links' => ['<p><a href="https://other.test/x">x</a></p>', 0, 0, 3, 'none'],
    'only fragment and mail links' => ['<p><a href="#x">x</a><a href="mailto:a@b.test">m</a></p>', 0, 0, 3, 'none'],
    'one followed link' => ['<p><a href="/one">a</a></p>', 1, 0, 9, 'good'],
    'every link followed' => ['<p><a href="/one">a</a><a href="https://example.test/two">b</a></p>', 2, 0, 9, 'good'],
    'the only link is nofollow' => ['<p><a href="/one" rel="nofollow">a</a></p>', 1, 1, 7, 'all_nofollow'],
    'every link nofollow' => ['<p><a href="/one" rel="nofollow">a</a><a href="/two" rel="nofollow">b</a></p>', 2, 2, 7, 'all_nofollow'],
    'some links nofollow' => ['<p><a href="/one" rel="nofollow">a</a><a href="/two">b</a></p>', 2, 1, 8, 'some_nofollow'],
]);

it('has nothing to say about a paper with no text', function () {
    $assessment = new InternalLinksAssessment;

    expect($assessment->identifier())->toBe('internalLinks')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeFalse()
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->text('<p>Words.</p>')->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    expect(assessInternalLinks('<p><a href="/one" rel="nofollow">a</a><a href="/two">b</a></p>')->text)
        ->toBe('1 of the 2 internal links are nofollow. Check that each of those is deliberate.');
});
