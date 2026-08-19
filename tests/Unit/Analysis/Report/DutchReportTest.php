<?php

namespace TwillSeo\Tests\Unit\Analysis\Report;

use TwillSeo\Analysis\AnalysisRunner;
use TwillSeo\Analysis\Assessor\AssessorFactory;
use TwillSeo\Analysis\Html\HtmlParser;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Analysis\Messages\ArrayMessageRenderer;
use TwillSeo\Analysis\Paper\Paper;

function dutchRunner(): AnalysisRunner
{
    return new AnalysisRunner(
        new HtmlParser,
        LanguagePackRegistry::withDefaults(),
        new AssessorFactory,
        new ArrayMessageRenderer,
    );
}

/**
 * A page as a Dutch author would really write it: short sentences, a
 * subheading every few paragraphs, and enough signposting that a reader always
 * knows how one sentence follows from the last.
 */
function groeneTheeHtml(): string
{
    return '<h1>Groene thee</h1>'
        .'<p>Groene thee zet je snel verkeerd. Blaadjes die boven de tachtig graden trekken, smaken '
        .'binnen een minuut bitter. Deze gids behandelt het water, de blaadjes en de tijd, in die '
        .'volgorde. Eerst leggen wij uit waarom de temperatuur meer uitmaakt dan de prijs.</p>'
        .'<h2>Losse thee kiezen</h2>'
        .'<p>Losse thee wint het van een zakje om één reden: ruimte. Blaadjes moeten open kunnen gaan, '
        .'en een zakje houdt ze in een vuist. Bovendien trekt het stof in de meeste zakjes er in '
        .'seconden uit. Daarom smaakt thee uit een zakje zo snel bitter. Koop dus een klein blik bij '
        .'een winkel die op gewicht verkoopt.</p>'
        .'<h2>De juiste temperatuur</h2>'
        .'<p>Kokend water verbrandt de blaadjes. Laat de waterkoker daarom twee minuten staan nadat '
        .'hij klikt. Zeventig tot tachtig graden past bij de meeste blaadjes. Echter, een geroosterde '
        .'thee zoals hojicha vraagt om heter water. Lees dus eerst het blik voordat je schenkt.</p>'
        .'<h2>De trektijd bepalen</h2>'
        .'<p>Begin met negentig seconden en proef. Verleng daarna telkens met vijftien seconden totdat '
        .'de smaak klopt. Omdat de tweede opgieting de blaadjes verder opent, duurt die meestal '
        .'korter. Schenk tot slot elke druppel uit de pot, want blaadjes in heet water blijven '
        .'trekken.</p>'
        .'<h2>Veelgemaakte fouten</h2>'
        .'<p>De meeste tegenvallende koppen komen uit drie gewoontes. Kortom: te heet water, te oude '
        .'blaadjes, te lang trekken. Pas eerst de temperatuur aan, omdat dat niets kost en het meest '
        .'verandert. Bijvoorbeeld dezelfde blaadjes die bij kookpunt scherp smaken, worden bij '
        .'vijfenzeventig graden zoet. Ondertussen wint een goedkoop blik dat vers opgaat het van een '
        .'duur blik dat een jaar openstaat. Bewaar de blaadjes daarom uit de buurt van licht, hitte '
        .'en het kruidenrek.</p>';
}

/**
 * @return array<string,mixed>
 */
function dutchReport(?Paper $paper = null): array
{
    $paper ??= Paper::builder()
        ->text(groeneTheeHtml())
        ->keyword('groene thee')
        ->title('Groene thee zetten zonder bitter te worden')
        ->description('Zet groene thee zonder bittere nasmaak. Deze korte gids behandelt de temperatuur '
            .'van het water, het kiezen van blaadjes en de trektijd.')
        ->slug('groene-thee-zetten')
        ->permalink('https://example.test/groene-thee-zetten')
        ->locale('nl_NL')
        ->build();

    return dutchRunner()->analyze($paper)->jsonSerialize();
}

it('reports dutch as a language it fully supports', function () {
    $report = dutchReport();

    expect($report['locale'])->toBe('nl')
        ->and($report['languageSupported'])->toBeTrue();
});

it('runs the dutch-only readability assessments', function () {
    // The three that need a language pack with data: without them a Dutch page
    // would silently be judged on nothing but its sentence and paragraph
    // lengths, which is what every locale got before this pack existed.
    expect(array_column($report = dutchReport()['readability']['results'], 'id'))->toBe([
        'sentenceLength',
        'paragraphTooLong',
        'subheadingsTooLong',
        'sentenceBeginnings',
        'transitionWords',
        'passiveVoice',
    ])->and($report)->not->toBeEmpty();
});

it('scores a well written dutch page green', function () {
    $report = dutchReport();

    expect($report['readability']['rating'])->toBe('good')
        ->and($report['readability']['score'])->toBe(90);
});

it('finds nothing to complain about in the clean dutch text', function () {
    $problems = array_values(array_filter(
        dutchReport()['readability']['results'],
        fn (array $result) => $result['rating'] !== 'good',
    ));

    expect($problems)->toBe([]);
});

it('reports the douma reading ease of a dutch text alongside its word count', function () {
    $insights = dutchReport()['insights'];

    expect($insights['wordCount'])->toBeGreaterThan(240)
        // Plain Dutch prose, short sentences: comfortably in the easy half of
        // the Douma scale.
        ->and($insights['fleschScore'])->toBeGreaterThan(60.0)
        ->and($insights['fleschBand'])->toBeIn(['very_easy', 'easy', 'fairly_easy', 'standard']);
});

/**
 * The counterpart: long sentences, no signposting, and the actor hidden behind
 * a passive wherever Dutch allows one.
 */
function onleesbaarHtml(): string
{
    return '<p>Het kwartaalrapport werd door de financiële afdeling opgesteld gedurende vele weken van '
        .'zorgvuldig werk dat niemand in de bredere organisatie ooit echt heeft opgemerkt. Elk getal '
        .'in de bijlage werd twee keer gecontroleerd door twee gescheiden teams die elkaar tijdens het '
        .'hele project nooit in levenden lijve hebben ontmoet. De aanbevelingen van de commissie werden '
        .'zonder enige discussie aangenomen op een vergadering die nauwelijks twintig minuten duurde '
        .'op een natte dinsdagmiddag in november. De financiële afdeling houdt een spreadsheet bij van '
        .'elke factuur die tegen elke projectcode wordt geboekt in een opmaak die buiten die afdeling '
        .'niemand kan lezen.</p>'
        .'<p>Onze mensen besteden uren aan het overtypen van getallen uit het ene systeem in een ander '
        .'systeem dat nooit door iemand behoorlijk is gedocumenteerd. De directie vroeg om een '
        .'samenvatting van één pagina over het geheel en ontving een document van veertig bladzijden '
        .'waarin geen enkele samenvatting was opgenomen. Het voorstel om beide systemen samen te '
        .'voegen werd vorig jaar door de directie besproken en zonder enige uitleg opnieuw '
        .'uitgesteld. Niemand wil het probleem bezitten van het '
        .'verzoenen van twee systemen die het oneens zijn over hoeveel projecten er vorig jaar door '
        .'het bedrijf werden uitgevoerd. De hele exercitie wordt elke drie maanden herhaald met '
        .'dezelfde argumenten over dezelfde getallen en hetzelfde gebrek aan een duidelijk besluit '
        .'van iemand.</p>';
}

it('scores a dutch text that fails three readability checks at the bottom band', function () {
    $report = dutchReport(Paper::builder()->text(onleesbaarHtml())->locale('nl')->build());

    $scores = array_column($report['readability']['results'], 'score');
    $ids = array_column($report['readability']['results'], 'id');

    expect(array_combine($ids, $scores))->toMatchArray([
        'sentenceLength' => 3,
        'transitionWords' => 3,
        'passiveVoice' => 3,
    ])->and($report['readability']['score'])->toBe(30)
        ->and($report['readability']['rating'])->toBe('bad');
});
