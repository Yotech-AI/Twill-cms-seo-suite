<?php

namespace TwillSeo\Tests\Unit\Analysis\Report;

use TwillSeo\Analysis\AnalysisOptions;
use TwillSeo\Analysis\AnalysisRunner;
use TwillSeo\Analysis\Assessor\AssessorFactory;
use TwillSeo\Analysis\Html\HtmlParser;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Analysis\Messages\ArrayMessageRenderer;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Analysis\Report\AnalysisReport;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

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

/**
 * One SEO result out of a report by its identifier.
 *
 * @return array<string,mixed>
 */
function collectResult(array $report, string $identifier): array
{
    $results = array_values(array_filter(
        $report['seo']['results'],
        fn (array $result) => $result['id'] === $identifier,
    ));

    return $results[0] ?? [];
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

    // The fixture paper has no keyphrase, so the assessments that need one are
    // left out; the ones that remain are in registration order.
    expect(array_column($report['seo']['results'], 'id'))->toBe([
        'keyphraseLength',
        'metaDescriptionLength',
        'subheadingsKeyword',
        'imageKeyphrase',
        'images',
        'textLength',
        'externalLinks',
        'keyphraseInSEOTitle',
        'internalLinks',
        'titleWidth',
        'slugKeyword',
        'singleH1',
    ]);
});

it('aggregates the seo section into a score and a rating', function () {
    $report = reportOf(fixturePaper());

    // The missing keyphrase vetoes the section with -999, which no amount of
    // green elsewhere can pull back above the floor of 1.
    expect(array_column($report['seo']['results'], 'score'))->toBe([-999, 9, 1, 3, 9, -20, 9, 2, 9, 9, 3, 8])
        ->and($report['seo']['score'])->toBe(1)
        ->and($report['seo']['rating'])->toBe('bad');
});

it('runs the keyphrase assessments once a paper has a keyphrase', function () {
    $report = reportOf(Paper::builder()
        ->text('<h1>Green tea</h1><p>Brewing green tea is simple once you know the water temperature.</p>')
        ->keyword('green tea')
        ->title('Brewing green tea: the short guide')
        ->description(str_repeat('a', 130))
        ->slug('brewing-green-tea')
        ->build());

    // Everything except the two that stay quiet on a healthy paper: the
    // keyphrase is not all function words, and no host answered the
    // "used elsewhere" question.
    expect(array_column($report['seo']['results'], 'id'))->toBe([
        'introductionKeyword',
        'keyphraseLength',
        'keywordDensity',
        'metaDescriptionKeyword',
        'metaDescriptionLength',
        'subheadingsKeyword',
        'textCompetingLinks',
        'imageKeyphrase',
        'images',
        'textLength',
        'externalLinks',
        'keyphraseInSEOTitle',
        'internalLinks',
        'titleWidth',
        'slugKeyword',
        'singleH1',
    ]);
});

it('reports the readability checks the language pack can actually run', function () {
    $report = reportOf(fixturePaper());

    // The generic pack has no first word, transition or passive data, so those
    // three assessments leave themselves out; the language-free ones still run.
    expect(array_column($report['readability']['results'], 'id'))
        ->toBe(['sentenceLength', 'paragraphTooLong', 'subheadingsTooLong'])
        ->and($report['readability']['score'])->toBe(90)
        ->and($report['readability']['rating'])->toBe('good');
});

it('reports readability as not available for a paper with no text', function () {
    // Only the "there is nothing here" assessment ran, and one counted result
    // is not a readability verdict.
    $report = reportOf(Paper::builder()->title('A title')->build());

    expect(array_column($report['readability']['results'], 'id'))->toBe(['textPresence'])
        ->and($report['readability'])->toMatchArray(['score' => 0, 'rating' => 'not-available']);
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

it('reports the locale it actually resolved the language pack with', function (string $locale, string $reported, bool $supported) {
    // The regression this guards: a paper with no locale reporting "en" while
    // the fallback pack analysed it, which stays invisible until a real en
    // pack exists and then silently disagrees with the report.
    $registry = new LanguagePackRegistry;
    $registry->register(AnalysisFactory::languagePack(code: 'en', supportsFullReadability: true));

    $runner = new AnalysisRunner(new HtmlParser, $registry, new AssessorFactory, new ArrayMessageRenderer);
    $report = $runner->analyze(Paper::builder()->locale($locale)->build());

    expect($report->locale)->toBe($reported)
        ->and($report->languageSupported)->toBe($supported);
})->with([
    'a registered language' => ['en_GB', 'en', true],
    'the same language, hyphenated' => ['en-US', 'en', true],
    'an unregistered language' => ['nl_NL', 'nl', false],
    'no locale at all' => ['', '', false],
    'whitespace only' => ['   ', '', false],
]);

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

    // Found by id rather than by position: the registration order is pinned by
    // its own test, and this one is about the thresholds.
    $textLength = fn (array $report) => collectResult($report, 'textLength');

    $normal = $textLength(reportOf($paper));
    $cornerstone = $textLength(reportOf($paper, new AnalysisOptions(cornerstone: true)));

    expect($normal['messageKey'])->toBe('twill-seo::analysis.text_length.far_too_short')
        ->and($normal['params'])->toBe(['words' => 13, 'recommended' => 300])
        ->and($cornerstone['params'])->toBe(['words' => 13, 'recommended' => 900]);
});

it('leaves out the assessments that have nothing to say about a paper', function () {
    $paper = Paper::builder()->title('A title')->description('A description')->build();

    expect(array_column(reportOf($paper)['seo']['results'], 'id'))->toBe([
        'keyphraseLength',
        'metaDescriptionLength',
        'subheadingsKeyword',
        'imageKeyphrase',
        'textLength',
        'keyphraseInSEOTitle',
        'titleWidth',
        'slugKeyword',
    ]);
});

it('returns an empty section when a section is switched off', function () {
    $report = reportOf(fixturePaper(), new AnalysisOptions(seo: false, insights: false));

    expect($report['seo'])->toBe(['score' => 0, 'rating' => 'not-available', 'results' => []])
        ->and($report['insights'])->toBeNull();
});

it('encodes to json without losing the shape', function () {
    // JSON_PRESERVE_ZERO_FRACTION is what keeps a percentage of 0.0 a float
    // rather than an int on the way back: some assessment parameters are
    // fractions, and a consumer that compares types would see them change.
    $json = json_decode((string) json_encode(runner()->analyze(fixturePaper()), JSON_PRESERVE_ZERO_FRACTION), true);

    expect($json)->toBe(reportOf(fixturePaper()));
});

it('encodes to json a plain consumer can read, fractions or not', function () {
    $json = json_decode((string) json_encode(runner()->analyze(fixturePaper())), true);

    // Without the flag a whole-numbered float comes back as an int, which is
    // the same number to every consumer that matters.
    expect($json)->toEqual(reportOf(fixturePaper()));
});

it('returns a report rather than throwing on unparseable markup', function () {
    $report = runner()->analyze(Paper::builder()->text('<<<>>> <p unclosed attr=')->build());

    expect($report)->toBeInstanceOf(AnalysisReport::class)
        ->and($report->jsonSerialize()['seo']['results'])->not->toBeEmpty();
});
