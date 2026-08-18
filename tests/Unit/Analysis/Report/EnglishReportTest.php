<?php

namespace TwillSeo\Tests\Unit\Analysis\Report;

use TwillSeo\Analysis\AnalysisOptions;
use TwillSeo\Analysis\AnalysisRunner;
use TwillSeo\Analysis\Assessor\AssessorFactory;
use TwillSeo\Analysis\Contracts\KeyphraseUsageProvider;
use TwillSeo\Analysis\Html\HtmlParser;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Analysis\Messages\ArrayMessageRenderer;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\CountingUsageProvider;

function englishRunner(?KeyphraseUsageProvider $usage = null): AnalysisRunner
{
    return new AnalysisRunner(
        new HtmlParser,
        LanguagePackRegistry::withDefaults(),
        new AssessorFactory,
        new ArrayMessageRenderer,
        $usage,
    );
}

/**
 * A page as an author would really write it: 321 words, a keyphrase that turns
 * up where it should, four subheadings, two illustrated images and a link in
 * each direction.
 */
function greenTeaHtml(): string
{
    return '<h1>Green tea</h1>'
        .'<p>Green tea is the simplest tea to brew badly. Leaves that sit in water above eighty '
        .'degrees turn bitter within a minute. This guide covers the water, the leaves and the '
        .'timing, in that order. First, though, a word about why temperature matters more than '
        .'the price of the leaves.</p>'
        .'<h2>Choosing green tea leaves</h2>'
        .'<p>Loose leaf beats a bag for one reason: room. Leaves need space to open, and a bag '
        .'holds them in a fist. Furthermore, the dust that fills most bags brews out in seconds, '
        .'which is why bagged tea turns bitter so fast. Buy a small tin from a shop that sells it '
        .'by weight, and drink it within a season.</p>'
        .'<h2>Water temperature for green tea</h2>'
        .'<p>Boiling water scalds the leaves. Instead, let the kettle stand for two minutes after '
        .'it clicks off, or pour in a splash of cold water. Seventy to eighty degrees suits most '
        .'leaves. However, a roasted tea such as hojicha takes a hotter pour, so read the tin '
        .'first.</p>'
        .'<h2>Timing the brew</h2>'
        .'<p>Start at ninety seconds and taste. Then adjust by fifteen seconds at a time until the '
        .'cup tastes the way you want it. Because the second steep opens the leaves further, it '
        .'usually needs less time than the first. Finally, pour every drop out of the pot: leaves '
        .'left sitting in hot water keep brewing.</p>'
        .'<h2>Common mistakes</h2>'
        .'<p>Most disappointing cups come from one of three habits. In short: water too hot, '
        .'leaves too old, brew too long. Fix the temperature first, since it costs nothing and '
        .'changes the most. For example, the same leaves that taste harsh at boiling point can '
        .'taste sweet at seventy-five degrees. Meanwhile, a cheap tin drunk fresh beats an '
        .'expensive one left open for a year. Store the leaves away from light, from heat and '
        .'from the spice rack.</p>'
        .'<p>Read our <a href="/tea/oolong">guide to oolong</a> next, or see the '
        .'<a href="https://example.org/tea-research">research on catechins</a> for the '
        .'chemistry.</p>'
        .'<p><img src="/green-tea-pot.jpg" alt="A pot of green tea">'
        .'<img src="/kettle.jpg" alt="A kettle on a stove"></p>';
}

function greenTeaPaper(string $keyword = 'green tea'): Paper
{
    return Paper::builder()
        ->text(greenTeaHtml())
        ->keyword($keyword)
        ->title('Green tea without the bitterness: a brewing guide')
        ->description('Brew green tea without the bitterness. This short guide covers water temperature, '
            .'choosing leaves and timing the steep, step by step.')
        ->slug('green-tea-brewing-guide')
        ->permalink('https://example.test/green-tea-brewing-guide')
        ->locale('en_GB')
        ->build();
}

/**
 * @return array<string,mixed>
 */
function englishReport(?Paper $paper = null, ?KeyphraseUsageProvider $usage = null): array
{
    return englishRunner($usage)->analyze($paper ?? greenTeaPaper())->jsonSerialize();
}

it('runs every seo assessment exactly once, in registration order', function () {
    $report = englishReport(usage: new CountingUsageProvider(0));

    // All eighteen but functionWordsInKeyphrase, which only speaks up about a
    // keyphrase with no content in it.
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
        'previouslyUsedKeyphrase',
    ]);
});

it('runs every readability assessment exactly once, in registration order', function () {
    $report = englishReport();

    // textPresence stays quiet: there is plenty of text to judge.
    expect(array_column($report['readability']['results'], 'id'))->toBe([
        'sentenceLength',
        'paragraphTooLong',
        'subheadingsTooLong',
        'sentenceBeginnings',
        'transitionWords',
        'passiveVoice',
    ]);
});

it('warns in place about a keyphrase of nothing but function words', function () {
    $report = englishReport(greenTeaPaper('about us'));
    $ids = array_column($report['seo']['results'], 'id');

    expect(array_slice($ids, -3))->toBe(['slugKeyword', 'functionWordsInKeyphrase', 'singleH1'])
        ->and(array_column($report['seo']['results'], 'score')[array_search('functionWordsInKeyphrase', $ids, true)])
        ->toBe(0);
});

it('reports english as a language it fully supports', function () {
    $report = englishReport();

    expect($report['locale'])->toBe('en')
        ->and($report['languageSupported'])->toBeTrue();
});

it('scores a well written page green in both sections', function () {
    $report = englishReport(usage: new CountingUsageProvider(0));

    expect($report['seo']['rating'])->toBe('good')
        ->and($report['seo']['score'])->toBeGreaterThan(90)
        ->and($report['readability']['rating'])->toBe('good')
        ->and($report['readability']['score'])->toBe(90);
});

it('reports the reading ease of the text alongside the word count', function () {
    $insights = englishReport()['insights'];

    expect($insights['wordCount'])->toBe(321)
        ->and($insights['readingTimeMinutes'])->toBe(2)
        // Plain prose, short sentences: comfortably in the easy band.
        ->and($insights['fleschScore'])->toBeGreaterThan(75.0)->toBeLessThan(90.0)
        ->and($insights['fleschBand'])->toBe('easy');
});

it('finds nothing to complain about in the clean text', function () {
    $problems = array_values(array_filter(
        englishReport()['readability']['results'],
        fn (array $result) => $result['rating'] !== 'good',
    ));

    expect($problems)->toBe([]);
});

/**
 * The counterpart: long sentences, no signposting, and the actor hidden behind
 * a passive wherever possible.
 */
function unreadableHtml(): string
{
    return '<p>The quarterly report was prepared by the finance department over several weeks of '
        .'careful work that nobody in the wider organisation ever really noticed at all. Every '
        .'figure in the appendix was checked twice by two separate teams who had never met each '
        .'other in person across the whole of the project. The recommendations of the committee '
        .'were accepted without discussion at a meeting that lasted barely twenty minutes on a wet '
        .'Tuesday afternoon. The finance department maintains a spreadsheet of every invoice '
        .'raised against every project code in a format nobody outside that department can '
        .'read.</p>'
        .'<p>Our people spend hours copying numbers out of one system and typing them into another '
        .'one that nobody has ever properly documented anywhere. The board asked for a single page '
        .'summary of the whole thing and received a forty page document with no summary page at '
        .'all. Nobody wants to own the problem of reconciling two systems that disagree about how '
        .'many projects the company actually ran last year. A future version of the process will '
        .'need a much clearer owner and a much shorter list of people entitled to change the '
        .'numbers. The whole exercise gets repeated every three months with the same arguments '
        .'about the same numbers and the same lack of a decision.</p>';
}

it('scores a text that fails three readability checks at the bottom band', function () {
    $report = englishReport(Paper::builder()->text(unreadableHtml())->locale('en')->build());

    $scores = array_column($report['readability']['results'], 'score');
    $ids = array_column($report['readability']['results'], 'id');

    expect(array_combine($ids, $scores))->toMatchArray([
        'sentenceLength' => 3,
        'transitionWords' => 3,
        'passiveVoice' => 3,
    ])->and($report['readability']['score'])->toBe(30)
        ->and($report['readability']['rating'])->toBe('bad');
});

it('holds cornerstone content to the tighter sentence length bar', function () {
    // One sentence in four runs long: acceptable for an ordinary page, not for
    // a pillar page.
    $long = implode(' ', array_fill(0, 25, 'word'));
    $paper = Paper::builder()
        ->text("<p>Short one here. Short two here. Short three here. Word {$long}.</p>")
        ->locale('en')
        ->build();

    $normal = englishRunner()->analyze($paper)->jsonSerialize();
    $cornerstone = englishRunner()->analyze($paper, new AnalysisOptions(cornerstone: true))->jsonSerialize();

    expect($normal['readability']['results'][0]['score'])->toBe(9)
        ->and($cornerstone['readability']['results'][0]['score'])->toBe(6)
        ->and($cornerstone['readability']['results'][0]['params'])->toBe(['percentage' => 25.0, 'limit' => 20]);
});
