<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\TextCompetingLinksAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function assessCompetingLinks(string $html, string $keyphrase = 'green tea'): AssessmentResult
{
    $paper = Paper::builder()->text($html)->keyword($keyphrase)->permalink('https://example.test/page')->build();

    return (new TextCompetingLinksAssessment)->assess(AnalysisFactory::context($paper));
}

it('counts links whose anchor text is the keyphrase itself', function (string $html, int $score, string $branch, int $count) {
    $result = assessCompetingLinks($html);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.text_competing_links.{$branch}")
        ->and($result->messageParams)->toBe(['count' => $count]);
})->with([
    'no links at all' => ['<p>Plain text about green tea.</p>', 8, 'good', 0],
    'links about something else' => [
        '<p>Read about <a href="/coffee">coffee brewing</a> instead.</p>',
        8, 'good', 0,
    ],
    'one link with the keyphrase as anchor' => [
        '<p>Read about <a href="/tea">green tea</a> here.</p>',
        2, 'competing', 1,
    ],
    'the keyphrase inside a longer anchor' => [
        '<p>Read <a href="/tea">our green tea guide</a>.</p>',
        2, 'competing', 1,
    ],
    'several competing links' => [
        '<p><a href="/a">green tea</a> and <a href="https://other.test/b">Green Tea</a>.</p>',
        2, 'competing', 2,
    ],
    // The words have to be together to compete for the phrase.
    'the words apart in the anchor' => [
        '<p><a href="/a">tea that is green</a></p>',
        8, 'good', 0,
    ],
]);

it('counts an external link as competing too', function () {
    expect(assessCompetingLinks('<p><a href="https://other.test/x">green tea</a></p>')->messageParams['count'])->toBe(1);
});

it('needs both a text and a keyphrase to say anything', function (string $text, string $keyword, bool $applicable) {
    $paper = Paper::builder()->text($text)->keyword($keyword)->build();

    expect((new TextCompetingLinksAssessment)->isApplicable(AnalysisFactory::context($paper)))->toBe($applicable);
})->with([
    'both present' => ['<p>Text.</p>', 'green tea', true],
    'no keyphrase' => ['<p>Text.</p>', '', false],
    'no text' => ['', 'green tea', false],
]);

it('identifies itself as textCompetingLinks', function () {
    expect((new TextCompetingLinksAssessment)->identifier())->toBe('textCompetingLinks');
});

it('says what is wrong in plain words', function () {
    expect(assessCompetingLinks('<p>Read about <a href="/tea">green tea</a> here.</p>')->text)->toBe(
        '1 link in the text uses the keyphrase as its anchor text. Those pages now compete with this '
        .'one for the same search — reword the anchor or drop the link.'
    );
});
