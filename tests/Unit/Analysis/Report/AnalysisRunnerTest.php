<?php

use TwillSeo\Analysis\AnalysisOptions;
use TwillSeo\Analysis\AnalysisRunner;
use TwillSeo\Analysis\Assessor\AssessorFactory;
use TwillSeo\Analysis\Html\HtmlParser;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Analysis\Messages\ArrayMessageRenderer;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Analysis\Report\AnalysisReport;

function runner(): AnalysisRunner
{
    return new AnalysisRunner(
        new HtmlParser,
        new LanguagePackRegistry,
        new AssessorFactory,
        new ArrayMessageRenderer,
    );
}

function fixturePaper(): Paper
{
    return Paper::builder()
        ->text(
            '<h1>Twill CMS</h1>'
            .'<p>A short guide with <a href="/more">one internal link</a> '
            .'and <a href="https://other.test/ref">one external link</a>.</p>'
            .'<p><img src="/screenshot.png" alt="A screenshot"></p>'
        )
        ->title('The complete guide to Twill CMS and SEO')
        ->description(str_repeat('a', 130))
        ->permalink('https://example.test/page')
        ->locale('en_GB')
        ->build();
}

function reportOf(Paper $paper, ?AnalysisOptions $options = null): array
{
    return runner()->analyze($paper, $options)->jsonSerialize();
}

it('reports the frozen top level shape', function () {
    expect(array_keys(reportOf(fixturePaper())))
        ->toBe(['locale', 'languageSupported', 'seo', 'readability', 'insights']);
});

it('reports each section with a score, a rating and its results', function () {
    $report = reportOf(fixturePaper());

    expect(array_keys($report['seo']))->toBe(['score', 'rating', 'results'])
        ->and(array_keys($report['readability']))->toBe(['score', 'rating', 'results']);
});

it('reports each result in the frozen result shape', function () {
    $report = reportOf(fixturePaper());

    expect(array_keys($report['seo']['results'][0]))
        ->toBe(['id', 'score', 'rating', 'category', 'text', 'messageKey', 'params']);
});

it('keeps the results in assessor registration order', function () {
    $report = reportOf(fixturePaper());

    expect(array_column($report['seo']['results'], 'id'))->toBe([
        'metaDescriptionLength',
        'textLength',
        'images',
        'singleH1',
        'titleWidth',
        'internalLinks',
        'externalLinks',
    ]);
});

it('aggregates the seo section into a score and a rating', function () {
    $report = reportOf(fixturePaper());

    // 9 - 20 + 9 + 8 + 9 + 9 + 9 = 33 of a possible 63.
    expect(array_column($report['seo']['results'], 'score'))->toBe([9, -20, 9, 8, 9, 9, 9])
        ->and($report['seo']['score'])->toBe(52)
        ->and($report['seo']['rating'])->toBe('ok');
});

it('reports readability as not available until it has assessments', function () {
    expect(reportOf(fixturePaper())['readability'])
        ->toBe(['score' => 0, 'rating' => 'not-available', 'results' => []]);
});

it('reports insights with the flesch keys already present', function () {
    expect(reportOf(fixturePaper())['insights'])->toBe([
        'wordCount' => 13,
        'readingTimeMinutes' => 1,
        'fleschScore' => null,
        'fleschBand' => null,
    ]);
});

it('reduces the locale to its language and reports whether it is supported', function () {
    $report = reportOf(fixturePaper());

    expect($report['locale'])->toBe('en')
        // Only the generic pack is registered so far; the real packs flip this.
        ->and($report['languageSupported'])->toBeFalse();
});

it('rounds reading time up to whole minutes and never to zero', function (int $words, int $minutes) {
    $text = $words === 0 ? '' : '<p>'.implode(' ', array_fill(0, $words, 'word')).'</p>';

    expect(reportOf(Paper::builder()->text($text)->build())['insights'])
        ->toMatchArray(['wordCount' => $words, 'readingTimeMinutes' => $minutes]);
})->with([
    'an empty paper still reads in a minute' => [0, 1],
    'exactly one minute' => [200, 1],
    'just over a minute' => [201, 2],
    'a longer read' => [420, 3],
]);

it('holds cornerstone content to the higher text length bar', function () {
    $paper = fixturePaper();

    $normal = reportOf($paper);
    $cornerstone = reportOf($paper, new AnalysisOptions(cornerstone: true));

    expect($normal['seo']['results'][1]['messageKey'])->toBe('twill-seo::analysis.text_length.far_too_short')
        ->and($normal['seo']['results'][1]['params'])->toBe(['words' => 13, 'recommended' => 300])
        ->and($cornerstone['seo']['results'][1]['params'])->toBe(['words' => 13, 'recommended' => 900]);
});

it('leaves out the assessments that have nothing to say about a paper', function () {
    $paper = Paper::builder()->title('A title')->description('A description')->build();

    expect(array_column(reportOf($paper)['seo']['results'], 'id'))
        ->toBe(['metaDescriptionLength', 'textLength', 'titleWidth']);
});

it('returns an empty section when a section is switched off', function () {
    $report = reportOf(fixturePaper(), new AnalysisOptions(seo: false, insights: false));

    expect($report['seo'])->toBe(['score' => 0, 'rating' => 'not-available', 'results' => []])
        ->and($report['insights'])->toBeNull();
});

it('encodes to json without losing the shape', function () {
    $json = json_decode((string) json_encode(runner()->analyze(fixturePaper())), true);

    expect($json)->toBe(reportOf(fixturePaper()));
});

it('returns a report rather than throwing on unparseable markup', function () {
    $report = runner()->analyze(Paper::builder()->text('<<<>>> <p unclosed attr=')->build());

    expect($report)->toBeInstanceOf(AnalysisReport::class)
        ->and($report->jsonSerialize()['seo']['results'])->not->toBeEmpty();
});
