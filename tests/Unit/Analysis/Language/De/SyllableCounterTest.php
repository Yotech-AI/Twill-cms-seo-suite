<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\De;

use TwillSeo\Analysis\Language\De\GermanLanguagePack;
use TwillSeo\Analysis\Language\SyllableCounter;

function germanSyllables(): SyllableCounter
{
    return (new GermanLanguagePack)->syllableCounter();
}

/**
 * The fixture the German counter is held to. Every entry was counted by saying
 * the word out loud and checking the break against the way German hyphenates
 * it; a change to the counter that moves any of them is a regression, not a
 * refinement.
 *
 * @return array<string,int>
 */
function germanSyllableFixture(): array
{
    return [
        // One beat, however many vowel letters are written.
        'Haus' => 1, 'Zeit' => 1, 'Buch' => 1, 'Baum' => 1, 'Jahr' => 1,
        'Kind' => 1, 'Mensch' => 1, 'Stadt' => 1, 'Weg' => 1, 'Tag' => 1,
        'gut' => 1, 'groß' => 1, 'schön' => 1, 'neu' => 1, 'drei' => 1,
        'zwei' => 1, 'eins' => 1, 'Bier' => 1, 'Zoo' => 1, 'Boot' => 1,
        'Meer' => 1, 'Stuhl' => 1, 'Kraft' => 1, 'Herz' => 1, 'Licht' => 1,
        'Nacht' => 1, 'Freund' => 1, 'Schuh' => 1, 'Wein' => 1, 'grün' => 1,

        // Two beats. The final -e is spoken, so there is no silent-e rule to
        // undo the way English needs one.
        'Katze' => 2, 'Sprache' => 2, 'Liebe' => 2, 'heute' => 2, 'Straße' => 2,
        'Wasser' => 2, 'Mutter' => 2, 'Vater' => 2, 'Kinder' => 2, 'Häuser' => 2,
        'Mädchen' => 2, 'über' => 2, 'Wüste' => 2, 'größer' => 2, 'Auge' => 2,
        'Handschuh' => 2, 'Deutschland' => 2, 'vielleicht' => 2, 'Eier' => 2,
        'Jahre' => 2, 'seine' => 2, 'Arbeit' => 2, 'Anfang' => 2, 'Antwort' => 2,
        'Beispiel' => 2, 'Erfolg' => 2, 'Wirtschaft' => 2, 'Zukunft' => 2,
        'Nachricht' => 2, 'Sprachen' => 2, 'Zeitung' => 2, 'Wohnung' => 2,
        'Prüfung' => 2, 'Idee' => 2, 'Studie' => 2, 'Linie' => 2,

        // y is a vowel in a Greek loan and a consonant in front of one.
        'System' => 2, 'Physik' => 2, 'Symbol' => 2, 'Analyse' => 4,
        'Bayern' => 2, 'Yoga' => 2,

        // qu is one consonant plus a vowel, not two vowels.
        'Quelle' => 2, 'Qualität' => 3, 'Quadrat' => 2, 'bequem' => 2,

        // Three beats and up.
        'Geschichte' => 3, 'Gesellschaft' => 3, 'Möglichkeit' => 3,
        'Unternehmen' => 4, 'Entwicklung' => 3, 'natürlich' => 3,
        'Wissenschaft' => 3, 'Gesundheit' => 3, 'Verantwortung' => 4,
        'Bedeutung' => 3, 'wahrscheinlich' => 3, 'Geschwindigkeit' => 4,
        'Zusammenarbeit' => 5, 'Überschrift' => 3, 'Wörterbuch' => 3,
        'Rechtschreibung' => 3, 'Beziehung' => 3, 'Erfahrung' => 3,
        'Bedingung' => 3, 'gemeinsam' => 3, 'besonders' => 3,
        'Veranstaltung' => 4, 'Bevölkerung' => 4, 'Jahrhundert' => 3,
        'Krankenhaus' => 3, 'Lebensmittel' => 4, 'Geschäftsführer' => 4,
        'Universität' => 5, 'beeinflussen' => 4,

        // Two vowels side by side that are said apart. German spells no single
        // sound "io", "ea" or "ua", so -tion counts as the three beats it is.
        'Nation' => 3, 'Station' => 3, 'Funktion' => 3, 'Information' => 5,
        'Organisation' => 6, 'Situation' => 5, 'Theater' => 3, 'Europa' => 3,
        'Ruine' => 3, 'aktuell' => 3, 'individuell' => 5, 'Familie' => 3,

        // The deviation list: the vowel groups are right about the spelling and
        // wrong about the word.
        'Museum' => 3, 'Museen' => 3, 'Jubiläum' => 4, 'Ideen' => 3,
        'Ferien' => 3, 'Familien' => 4, 'Linien' => 3, 'Studien' => 3,
        'Serien' => 3, 'Kopien' => 3, 'Aktien' => 3, 'Italien' => 4,
        'Spanien' => 3, 'Prinzipien' => 4, 'Kategorien' => 5,
    ];
}

it('counts the syllables of every german fixture word exactly', function () {
    $counter = germanSyllables();
    $wrong = [];

    foreach (germanSyllableFixture() as $word => $expected) {
        $counted = $counter->count($word);

        if ($counted !== $expected) {
            $wrong[$word] = "expected {$expected}, counted {$counted}";
        }
    }

    expect($wrong)->toBe([]);
});

it('ships a german fixture big enough to hold the counter honest', function () {
    expect(count(germanSyllableFixture()))->toBeGreaterThanOrEqual(100);
});

it('counts at least one syllable for any german word with a letter in it', function (string $word) {
    expect(germanSyllables()->count($word))->toBe(1);
})->with([
    'a single letter' => ['a'],
    'a word of nothing but consonants' => ['pst'],
    'a two letter word ending in e' => ['die'],
    'a word that is only a diphthong' => ['ei'],
]);

it('counts nothing for a german word with no letters at all', function (string $word) {
    expect(germanSyllables()->count($word))->toBe(0);
})->with([
    'nothing at all' => [''],
    'whitespace' => ['   '],
    'punctuation' => ['—'],
    'digits' => ['2024'],
]);

it('ignores case and surrounding punctuation in german', function () {
    $counter = germanSyllables();

    // The umlaut has to survive lowercasing, or "HÄUSER" would count a beat
    // more than "Häuser" does.
    expect($counter->count('HÄUSER'))->toBe(2)
        ->and($counter->count('Häuser'))->toBe(2)
        ->and($counter->count('Straße.'))->toBe(2)
        ->and($counter->count('MUSEUM'))->toBe(3);
});
