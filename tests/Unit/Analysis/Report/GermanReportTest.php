<?php

namespace TwillSeo\Tests\Unit\Analysis\Report;

use TwillSeo\Analysis\AnalysisRunner;
use TwillSeo\Analysis\Assessor\AssessorFactory;
use TwillSeo\Analysis\Html\HtmlParser;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Analysis\Messages\ArrayMessageRenderer;
use TwillSeo\Analysis\Paper\Paper;

function germanRunner(): AnalysisRunner
{
    return new AnalysisRunner(
        new HtmlParser,
        LanguagePackRegistry::withDefaults(),
        new AssessorFactory,
        new ArrayMessageRenderer,
    );
}

/**
 * A page as a German author would really write it: short sentences, a
 * subheading every few paragraphs, and enough signposting that a reader always
 * knows how one sentence follows from the last.
 */
function gruenerTeeHtml(): string
{
    return '<h1>Grüner Tee</h1>'
        .'<p>Grünen Tee brüht man leicht falsch. Blätter, die über achtzig Grad ziehen, schmecken '
        .'binnen einer Minute bitter. Dieser Ratgeber behandelt das Wasser, die Blätter und die Zeit, '
        .'in dieser Reihenfolge. Zuerst erklären wir, warum die Temperatur mehr zählt als der '
        .'Preis.</p>'
        .'<h2>Lose Blätter kaufen</h2>'
        .'<p>Lose Blätter schlagen den Beutel aus einem Grund: Platz. Blätter müssen sich öffnen, und '
        .'ein Beutel hält sie in einer Faust. Außerdem zieht der Staub in den meisten Beuteln nach '
        .'Sekunden durch. Deshalb schmeckt Tee aus dem Beutel so schnell bitter. Kaufen Sie also eine '
        .'kleine Dose in einem Laden, der nach Gewicht verkauft.</p>'
        .'<h2>Die richtige Temperatur</h2>'
        .'<p>Kochendes Wasser verbrennt die Blätter. Lassen Sie den Kessel deshalb zwei Minuten '
        .'stehen, nachdem er klickt. Siebzig bis achtzig Grad passen zu den meisten Blättern. Jedoch '
        .'verlangt ein gerösteter Tee wie Hojicha nach heißerem Wasser. Lesen Sie also zuerst die '
        .'Dose, bevor Sie aufgießen.</p>'
        .'<h2>Die Ziehzeit bestimmen</h2>'
        .'<p>Beginnen Sie mit neunzig Sekunden und probieren Sie. Verlängern Sie danach jeweils um '
        .'fünfzehn Sekunden, bis der Geschmack stimmt. Weil der zweite Aufguss die Blätter weiter '
        .'öffnet, dauert er meistens kürzer. Gießen Sie zum Schluss jeden Tropfen aus der Kanne, denn '
        .'Blätter im heißen Wasser ziehen weiter.</p>'
        .'<h2>Häufige Fehler</h2>'
        .'<p>Die meisten enttäuschenden Tassen kommen aus drei Gewohnheiten. Kurz gesagt: zu heißes '
        .'Wasser, zu alte Blätter, zu langes Ziehen. Ändern Sie zuerst die Temperatur, weil das nichts '
        .'kostet und am meisten bewirkt. Zum Beispiel schmecken dieselben Blätter beim Kochpunkt '
        .'scharf und bei fünfundsiebzig Grad süß. Inzwischen schlägt eine billige Dose frisch eine '
        .'teure Dose, die ein Jahr offen steht. Lagern Sie die Blätter deshalb fern von Licht, Hitze '
        .'und dem Gewürzregal.</p>';
}

/**
 * @return array<string,mixed>
 */
function germanReport(?Paper $paper = null): array
{
    $paper ??= Paper::builder()
        ->text(gruenerTeeHtml())
        ->keyword('grüner Tee')
        ->title('Grünen Tee aufbrühen, ohne dass er bitter wird')
        ->description('Brühen Sie grünen Tee ohne bitteren Nachgeschmack. Dieser kurze Ratgeber behandelt '
            .'die Wassertemperatur, die Wahl der Blätter und die Ziehzeit.')
        ->slug('gruenen-tee-aufbruehen')
        ->permalink('https://example.test/gruenen-tee-aufbruehen')
        ->locale('de_DE')
        ->build();

    return germanRunner()->analyze($paper)->jsonSerialize();
}

it('reports german as a language it fully supports', function () {
    $report = germanReport();

    expect($report['locale'])->toBe('de')
        ->and($report['languageSupported'])->toBeTrue();
});

it('runs the german-only readability assessments', function () {
    // The three that need a language pack with data: without them a German page
    // would silently be judged on nothing but its sentence and paragraph
    // lengths, which is what every locale got before this pack existed.
    expect(array_column($report = germanReport()['readability']['results'], 'id'))->toBe([
        'sentenceLength',
        'paragraphTooLong',
        'subheadingsTooLong',
        'sentenceBeginnings',
        'transitionWords',
        'passiveVoice',
    ])->and($report)->not->toBeEmpty();
});

it('scores a well written german page green', function () {
    $report = germanReport();

    expect($report['readability']['rating'])->toBe('good')
        ->and($report['readability']['score'])->toBe(90);
});

it('finds nothing to complain about in the clean german text', function () {
    $problems = array_values(array_filter(
        germanReport()['readability']['results'],
        fn (array $result) => $result['rating'] !== 'good',
    ));

    expect($problems)->toBe([]);
});

it('reports the amstad reading ease of a german text alongside its word count', function () {
    $insights = germanReport()['insights'];

    expect($insights['wordCount'])->toBeGreaterThan(240)
        // Plain German prose, short sentences: comfortably in the easy half of
        // the Amstad scale.
        ->and($insights['fleschScore'])->toBeGreaterThan(60.0)
        ->and($insights['fleschBand'])->toBeIn(['very_easy', 'easy', 'fairly_easy', 'standard']);
});

/**
 * The counterpart: long sentences, no signposting, and the actor hidden behind
 * a passive wherever German allows one.
 */
function unlesbarHtml(): string
{
    return '<p>Der Quartalsbericht wurde von der Finanzabteilung über viele Wochen sorgfältiger Arbeit '
        .'erstellt, die im weiteren Unternehmen niemand jemals wirklich bemerkt hat. Jede Zahl im '
        .'Anhang wurde von zwei getrennten Teams zweimal geprüft, die sich im ganzen langen '
        .'Projektverlauf nie persönlich getroffen haben. Die Empfehlungen des Ausschusses wurden ohne '
        .'jede Diskussion in einer Sitzung angenommen, die an einem nassen Dienstagnachmittag kaum '
        .'zwanzig Minuten dauerte. Die Finanzabteilung führt eine Tabelle über jede Rechnung, die '
        .'gegen jeden Projektcode gebucht wird, in einem Format, das außerhalb dieser Abteilung '
        .'niemand lesen kann.</p>'
        .'<p>Unsere Leute verbringen Stunden mit dem Übertragen von Zahlen aus einem System in ein '
        .'anderes System, das von niemandem jemals ordentlich dokumentiert worden ist. Die Leitung bat '
        .'um eine Zusammenfassung des Ganzen auf einer Seite und erhielt ein Dokument von vierzig '
        .'Seiten, in dem keine einzige Zusammenfassung enthalten war. Der Vorschlag zur '
        .'Zusammenführung der beiden Systeme wurde im vergangenen Jahr von der Leitung besprochen und '
        .'ohne jede weitere Erklärung erneut verschoben. Niemand will das Problem besitzen, zwei '
        .'Systeme in Einklang zu bringen, die sich darüber uneinig sind, wie viele Projekte die Firma '
        .'im letzten Jahr wirklich ausgeführt hat. Die ganze Übung wird alle drei Monate erneut '
        .'durchgeführt, mit denselben Argumenten über dieselben Zahlen und ohne eine wirklich klare '
        .'Entscheidung.</p>';
}

it('scores a german text that fails three readability checks at the bottom band', function () {
    $report = germanReport(Paper::builder()->text(unlesbarHtml())->locale('de')->build());

    $scores = array_column($report['readability']['results'], 'score');
    $ids = array_column($report['readability']['results'], 'id');

    expect(array_combine($ids, $scores))->toMatchArray([
        'sentenceLength' => 3,
        'transitionWords' => 3,
        'passiveVoice' => 3,
    ])->and($report['readability']['score'])->toBe(30)
        ->and($report['readability']['rating'])->toBe('bad');
});
