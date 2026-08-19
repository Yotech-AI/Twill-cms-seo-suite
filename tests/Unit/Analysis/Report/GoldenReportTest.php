<?php

namespace TwillSeo\Tests\Unit\Analysis\Report;

use TwillSeo\Analysis\AnalysisRunner;
use TwillSeo\Analysis\Assessor\AssessorFactory;
use TwillSeo\Analysis\Html\HtmlParser;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Analysis\Messages\ArrayMessageRenderer;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\CountingUsageProvider;

/**
 * Pins the FULL report the real runner produces for one realistic paper per
 * shipped language, so an engine regression anywhere (a threshold nudged, a
 * message reworded, a word list edited) shows up as an exact diff against a
 * committed golden file instead of a scattered set of assertions that only
 * cover what someone thought to check.
 *
 * Regeneration is deliberate and explicit: run with UPDATE_GOLDEN=1 to
 * (re)write tests/Fixtures/golden/{locale}.json from the current runner
 * output, eyeball the diff for sanity (plausible score bands, every expected
 * assessment id present — see this file's own sanity assertions, which run
 * unconditionally, even during regeneration) and commit the result. Without
 * that env var, a mismatch fails with a normal Pest diff plus a pointer to
 * this comment.
 */
function goldenRunner(): AnalysisRunner
{
    return new AnalysisRunner(
        new HtmlParser,
        LanguagePackRegistry::withDefaults(),
        new AssessorFactory,
        new ArrayMessageRenderer,
        // A real saved-mode analysis always has a usage provider backed by
        // the DB (see PaperFactory) — an explicit "zero other uses" here
        // keeps the golden fixture representative of that, and lets
        // previouslyUsedKeyphrase actually contribute a result rather than
        // silently disappearing the way it does behind the default
        // NullKeyphraseUsageProvider ("I don't know").
        new CountingUsageProvider(0),
    );
}

/**
 * Recursively sorts keys of every associative (map-like) array so the golden
 * JSON's key order can never drift between runs on its own — a genuinely
 * ordered list (results, the params of a list-shaped assessment) is left
 * exactly as produced, since ITS order is meaningful data.
 */
function goldenCanonicalize(mixed $value): mixed
{
    if (! is_array($value)) {
        return $value;
    }

    $isList = array_is_list($value);
    $out = [];

    foreach ($value as $key => $item) {
        $out[$key] = goldenCanonicalize($item);
    }

    if (! $isList) {
        ksort($out);
    }

    return $out;
}

/**
 * Pretty + sorted keys + JSON_PRESERVE_ZERO_FRACTION (see docs/analysis.md's
 * own note: plain json_encode emits a 0.0 float as the integer 0, which a
 * host re-serializing this report would silently narrow a float column to
 * an int) + unescaped unicode so the nl/de fixtures stay human-readable
 * rather than \uXXXX-escaped.
 */
function goldenJson(array $report): string
{
    return json_encode(
        goldenCanonicalize($report),
        JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    )."\n";
}

/**
 * Every SEO id the golden fixtures below are expected to produce: all
 * eighteen registered assessments except functionWordsInKeyphrase, which
 * only speaks up about a keyphrase made of nothing but function words (see
 * EnglishReportTest's own identical list) — none of the three fixture
 * keyphrases here are that.
 *
 * @return list<string>
 */
function goldenExpectedSeoIds(): array
{
    return [
        'introductionKeyword', 'keyphraseLength', 'keywordDensity', 'metaDescriptionKeyword',
        'metaDescriptionLength', 'subheadingsKeyword', 'textCompetingLinks', 'imageKeyphrase',
        'images', 'textLength', 'externalLinks', 'keyphraseInSEOTitle', 'internalLinks',
        'titleWidth', 'slugKeyword', 'singleH1', 'previouslyUsedKeyphrase',
    ];
}

/**
 * All seven readability assessments except textPresence, which only speaks
 * up when there is too little text to judge — none of the three fixtures
 * are that short.
 *
 * @return list<string>
 */
function goldenExpectedReadabilityIds(): array
{
    return [
        'sentenceLength', 'paragraphTooLong', 'subheadingsTooLong',
        'sentenceBeginnings', 'transitionWords', 'passiveVoice',
    ];
}

/**
 * Runs $paper, asserts the sanity bar every golden fixture must clear no
 * matter what (both when comparing AND when regenerating — see this file's
 * own class doc comment), then compares/writes the canonical JSON.
 */
function assertGolden(string $locale, Paper $paper): void
{
    $report = goldenRunner()->analyze($paper)->jsonSerialize();

    expect(array_column($report['seo']['results'], 'id'))->toBe(goldenExpectedSeoIds())
        ->and(array_column($report['readability']['results'], 'id'))->toBe(goldenExpectedReadabilityIds())
        ->and($report['seo']['score'])->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(100)
        ->and($report['readability']['score'])->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(100)
        ->and($report['locale'])->toBe($locale)
        ->and($report['languageSupported'])->toBeTrue();

    $goldenPath = __DIR__."/../../../Fixtures/golden/{$locale}.json";
    $actual = goldenJson($report);

    if ((string) getenv('UPDATE_GOLDEN') === '1') {
        file_put_contents($goldenPath, $actual);
    }

    expect(is_file($goldenPath))->toBeTrue(
        "No golden file for \"{$locale}\" at {$goldenPath}. Generate it with: ".
            'UPDATE_GOLDEN=1 vendor/bin/pest tests/Unit/Analysis/Report/GoldenReportTest.php'
    );

    expect($actual)->toBe(
        file_get_contents($goldenPath),
        "The report for \"{$locale}\" no longer matches its golden file. If this change is intentional, ".
            'regenerate it with: UPDATE_GOLDEN=1 vendor/bin/pest tests/Unit/Analysis/Report/GoldenReportTest.php '.
            '— then eyeball the diff before committing (plausible score bands, every expected assessment id present).'
    );
}

/**
 * ~350 words, four subheadings (two carrying the keyphrase, two not — the
 * same split EnglishReportTest's own green-tea fixture uses), two images
 * (one alt text carrying the keyphrase) and two links (one internal, one
 * external).
 */
function goldenEnglishHtml(): string
{
    return '<h1>Oolong tea</h1>'
        .'<p>Oolong tea sits between green and black tea, and brewing it well means treating it like neither. '
        .'Leaves that steep too hot turn harsh within a minute, while water that is too cool leaves the cup thin '
        .'and flat. This guide covers the leaves, the water and the timing, in that order. First, though, a word '
        .'about why oolong rewards patience more than most teas do.</p>'
        .'<h2>Choosing oolong tea leaves</h2>'
        .'<p>Rolled leaves beat flat leaves for one reason: they open slowly, releasing flavor over several steeps '
        .'instead of one. A tightly rolled ball unfurls a little more each time water touches it, which is why the '
        .'same leaves can be steeped four or five times. Furthermore, leaves sold loose rather than bagged keep '
        .'their oil far longer, so buy from a shop that turns over stock quickly. Store the tin somewhere dark and '
        .'dry, away from anything with a strong smell.</p>'
        .'<h2>Water temperature for oolong tea</h2>'
        .'<p>Boiling water scalds the delicate rolled leaves. Instead, let the kettle rest for a minute after it '
        .'clicks off, or add a little cold water first. Eighty five to ninety degrees suits most oolongs. However, '
        .'a darker roasted oolong can take a hotter pour, so taste as you go and adjust the next cup.</p>'
        .'<h2>Timing the steep</h2>'
        .'<p>Start at thirty seconds for the first steep and taste before pouring the next. Because rolled leaves '
        .'need time to open, the second steep often needs a few seconds longer than the first, not less. Then add '
        .'roughly ten seconds to each steep after that. Finally, pour every drop out of the pot, since leaves left '
        .'sitting in hot water keep releasing tannins.</p>'
        .'<h2>Common mistakes</h2>'
        .'<p>Most disappointing cups share one of three causes. In short: water too hot, leaves too old, or too '
        .'little patience with the later steeps. Fix the water first, since it costs nothing and changes the most. '
        .'For example, the same leaves that taste harsh at boiling point can taste sweet and floral at eighty five '
        .'degrees. Meanwhile, a fresh tin drunk within a season beats an expensive one left open for a year.</p>'
        .'<p>Read our guide to <a href="/tea/pu-erh">pu-erh tea</a> next, or see the '
        .'<a href="https://example.org/tea-research">research on tea polyphenols</a> for the chemistry.</p>'
        .'<p><img src="/oolong-cups.jpg" alt="Two cups of oolong tea">'
        .'<img src="/kettle-pour.jpg" alt="A kettle on a stove"></p>';
}

function goldenEnglishPaper(): Paper
{
    return Paper::builder()
        ->text(goldenEnglishHtml())
        ->keyword('oolong tea')
        ->title('Oolong tea without the harshness: a brewing guide')
        ->description('Brew oolong tea without the harsh edge. This short guide covers water temperature, '
            .'choosing leaves and timing the steep, step by step.')
        ->slug('oolong-tea-brewing-guide')
        ->permalink('https://example.test/oolong-tea-brewing-guide')
        ->locale('en_GB')
        ->build();
}

/**
 * Dutch prose adapted from the same proven, hand-verified sentence
 * structures as DutchReportTest's own fixture (only the tea, the numbers
 * and — where a sentence's own logic required it — a single word change),
 * plus a fresh closing links/images paragraph.
 */
function goldenDutchHtml(): string
{
    return '<h1>Oolongthee</h1>'
        .'<p>Oolongthee zet je snel verkeerd. Blaadjes die boven de vijfennegentig graden trekken, smaken binnen '
        .'een minuut bitter. Deze gids behandelt het water, de blaadjes en de tijd, in die volgorde. Eerst leggen '
        .'wij uit waarom de temperatuur meer uitmaakt dan de prijs.</p>'
        .'<h2>Losse oolongthee kiezen</h2>'
        .'<p>Losse oolongthee wint het van een zakje om één reden: ruimte. Blaadjes moeten open kunnen gaan, en '
        .'een zakje houdt ze in een vuist. Bovendien trekt het stof in de meeste zakjes er in seconden uit. Daarom '
        .'smaakt thee uit een zakje zo snel bitter. Koop dus een klein blik bij een winkel die op gewicht '
        .'verkoopt.</p>'
        .'<h2>De juiste temperatuur voor oolongthee</h2>'
        .'<p>Kokend water verbrandt de blaadjes. Laat de waterkoker daarom twee minuten staan nadat hij klikt. '
        .'Vijfentachtig tot vijfennegentig graden past bij de meeste oolongthee. Echter, een donkere oolongthee '
        .'vraagt soms om heter water. Lees dus eerst het blik voordat je schenkt.</p>'
        .'<h2>De trektijd bepalen</h2>'
        .'<p>Begin met dertig seconden en proef. Verleng daarna telkens met tien seconden totdat de smaak klopt. '
        .'Omdat de tweede opgieting de blaadjes verder opent, duurt die meestal iets langer. Schenk tot slot elke '
        .'druppel uit de pot, want blaadjes in heet water blijven trekken.</p>'
        .'<h2>Veelgemaakte fouten</h2>'
        .'<p>De meeste tegenvallende koppen komen uit drie gewoontes. Kortom: te heet water, te oude blaadjes, te '
        .'lang trekken. Pas eerst de temperatuur aan, omdat dat niets kost en het meest verandert. Bijvoorbeeld '
        .'dezelfde oolongthee die bij kookpunt scherp smaakt, wordt bij vijfentachtig graden zoet en bloemig. '
        .'Ondertussen wint een goedkoop blik dat vers opgaat het van een duur blik dat een jaar openstaat. Bewaar '
        .'de blaadjes daarom uit de buurt van licht, hitte en het kruidenrek.</p>'
        .'<p>Lees onze gids over <a href="/thee/poerh">poerh-thee</a> voor de volgende stap, of bekijk het '
        .'<a href="https://example.org/thee-onderzoek">onderzoek naar theepolyfenolen</a> voor de chemie.</p>'
        .'<p><img src="/oolong-kopjes.jpg" alt="Twee kopjes oolongthee">'
        .'<img src="/waterketel.jpg" alt="Een waterketel op het vuur"></p>';
}

function goldenDutchPaper(): Paper
{
    return Paper::builder()
        ->text(goldenDutchHtml())
        ->keyword('oolongthee')
        ->title('Oolongthee zetten zonder bitter te worden')
        ->description('Zet oolongthee zonder bittere nasmaak. Deze korte gids behandelt de temperatuur van het '
            .'water, het kiezen van blaadjes en de trektijd.')
        ->slug('oolongthee-zetten')
        ->permalink('https://example.test/oolongthee-zetten')
        ->locale('nl_NL')
        ->build();
}

/**
 * German prose adapted the same way as the Dutch fixture above, from
 * GermanReportTest's own proven sentence structures.
 */
function goldenGermanHtml(): string
{
    return '<h1>Oolongtee</h1>'
        .'<p>Oolongtee brüht man leicht falsch. Blätter, die über fünfundneunzig Grad ziehen, schmecken binnen '
        .'einer Minute bitter. Dieser Ratgeber behandelt das Wasser, die Blätter und die Zeit, in dieser '
        .'Reihenfolge. Zuerst erklären wir, warum die Temperatur mehr zählt als der Preis.</p>'
        .'<h2>Losen Oolongtee kaufen</h2>'
        .'<p>Lose Blätter schlagen den Beutel aus einem Grund: Platz. Blätter müssen sich öffnen, und ein Beutel '
        .'hält sie in einer Faust. Außerdem zieht der Staub in den meisten Beuteln nach Sekunden durch. Deshalb '
        .'schmeckt Tee aus dem Beutel so schnell bitter. Kaufen Sie also eine kleine Dose in einem Laden, der '
        .'nach Gewicht verkauft.</p>'
        .'<h2>Die richtige Temperatur für Oolongtee</h2>'
        .'<p>Kochendes Wasser verbrennt die Blätter. Lassen Sie den Kessel deshalb eine Minute stehen, nachdem er '
        .'klickt. Fünfundachtzig bis fünfundneunzig Grad passen zu den meisten Blättern. Jedoch verlangt eine '
        .'dunklere Röstung nach heißerem Wasser. Lesen Sie also zuerst die Dose, bevor Sie aufgießen.</p>'
        .'<h2>Die Ziehzeit bestimmen</h2>'
        .'<p>Beginnen Sie mit dreißig Sekunden und probieren Sie. Verlängern Sie danach jeweils um zehn Sekunden, '
        .'bis der Geschmack stimmt. Weil der zweite Aufguss die Blätter weiter öffnet, dauert er meistens etwas '
        .'länger. Gießen Sie zum Schluss jeden Tropfen aus der Kanne, denn Blätter im heißen Wasser ziehen '
        .'weiter.</p>'
        .'<h2>Häufige Fehler</h2>'
        .'<p>Die meisten enttäuschenden Tassen kommen aus drei Gewohnheiten. Kurz gesagt: zu heißes Wasser, zu '
        .'alte Blätter, zu langes Ziehen. Ändern Sie zuerst die Temperatur, weil das nichts kostet und am meisten '
        .'bewirkt. Zum Beispiel schmeckt derselbe Oolongtee beim Kochpunkt scharf und bei fünfundachtzig Grad '
        .'blumig und süß. Inzwischen schlägt eine billige Dose frisch eine teure Dose, die ein Jahr offen steht. '
        .'Lagern Sie die Blätter deshalb fern von Licht, Hitze und dem Gewürzregal.</p>'
        .'<p>Lesen Sie unseren <a href="/tee/pu-erh">Ratgeber zu Pu-Erh-Tee</a>, oder werfen Sie einen Blick auf '
        .'die <a href="https://example.org/tee-forschung">Forschung zu Tee-Polyphenolen</a> für die Chemie '
        .'dahinter.</p>'
        .'<p><img src="/oolong-tassen.jpg" alt="Zwei Tassen Oolongtee">'
        .'<img src="/wasserkessel.jpg" alt="Ein Wasserkessel auf dem Herd"></p>';
}

function goldenGermanPaper(): Paper
{
    return Paper::builder()
        ->text(goldenGermanHtml())
        ->keyword('Oolongtee')
        ->title('Oolongtee aufgießen, ohne dass er bitter wird')
        ->description('Bereiten Sie Oolongtee ohne bitteren Nachgeschmack zu. Dieser kurze Ratgeber behandelt '
            .'die Wassertemperatur, die Wahl der Blätter und die Ziehzeit.')
        ->slug('oolongtee-zubereiten')
        ->permalink('https://example.test/oolongtee-zubereiten')
        ->locale('de_DE')
        ->build();
}

it('matches the committed English golden report', function () {
    assertGolden('en', goldenEnglishPaper());
});

it('matches the committed Dutch golden report', function () {
    assertGolden('nl', goldenDutchPaper());
});

it('matches the committed German golden report', function () {
    assertGolden('de', goldenGermanPaper());
});
