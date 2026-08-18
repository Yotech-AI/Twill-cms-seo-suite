<?php

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\ExternalLinksAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function assessExternalLinks(string $html): AssessmentResult
{
    $assessment = new ExternalLinksAssessment;

    return $assessment->assess(AnalysisFactory::context(
        Paper::builder()->permalink('https://example.test/page')->text($html)->build()
    ));
}

it('scores external links by how many of them are nofollow', function (string $html, int $total, int $nofollow, int $score, string $branch) {
    $result = assessExternalLinks($html);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.external_links.{$branch}")
        ->and($result->messageParams)->toBe(['total' => $total, 'nofollow' => $nofollow]);
})->with([
    'no links at all' => ['<p>Just words.</p>', 0, 0, 3, 'none'],
    'only internal links' => ['<p><a href="/x">x</a><a href="https://example.test/y">y</a></p>', 0, 0, 3, 'none'],
    'one followed link' => ['<p><a href="https://other.test/one">a</a></p>', 1, 0, 9, 'good'],
    'every link followed' => ['<p><a href="https://other.test/one">a</a><a href="https://third.test/two">b</a></p>', 2, 0, 9, 'good'],
    'the only link is nofollow' => ['<p><a href="https://other.test/one" rel="nofollow">a</a></p>', 1, 1, 7, 'all_nofollow'],
    'every link nofollow' => ['<p><a href="https://other.test/one" rel="nofollow">a</a><a href="https://third.test/two" rel="nofollow">b</a></p>', 2, 2, 7, 'all_nofollow'],
    'some links nofollow' => ['<p><a href="https://other.test/one" rel="nofollow">a</a><a href="https://third.test/two">b</a></p>', 2, 1, 8, 'some_nofollow'],
]);

it('has nothing to say about a paper with no text', function () {
    $assessment = new ExternalLinksAssessment;

    expect($assessment->identifier())->toBe('externalLinks')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeFalse()
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->text('<p>Words.</p>')->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    expect(assessExternalLinks('<p>Just words.</p>')->text)
        ->toBe('The text links to no other sites. Link out to a source or reference where it helps the reader.');
});
