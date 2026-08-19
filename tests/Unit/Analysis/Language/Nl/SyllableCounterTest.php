<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\Nl;

use TwillSeo\Analysis\Language\Nl\DutchLanguagePack;
use TwillSeo\Analysis\Language\SyllableCounter;

function dutchSyllables(): SyllableCounter
{
    return (new DutchLanguagePack)->syllableCounter();
}

/**
 * The fixture the Dutch counter is held to. Every entry was counted by saying
 * the word out loud and checking the break against the way Dutch hyphenates
 * it; a change to the counter that moves any of them is a regression, not a
 * refinement.
 *
 * @return array<string,int>
 */
function dutchSyllableFixture(): array
{
    return [
        // One beat. Dutch writes long vowels double and its diphthongs with two
        // letters, so most of these are longer than they sound.
        'kat' => 1, 'hond' => 1, 'huis' => 1, 'boom' => 1, 'straat' => 1,
        'weer' => 1, 'maan' => 1, 'zee' => 1, 'twee' => 1, 'drie' => 1,
        'vrij' => 1, 'tijd' => 1, 'zijn' => 1, 'mijn' => 1, 'ijs' => 1,
        'nieuw' => 1, 'leeuw' => 1, 'vrouw' => 1, 'goed' => 1, 'groot' => 1,
        'klein' => 1, 'werk' => 1, 'school' => 1, 'jaar' => 1, 'geen' => 1,
        'schaap' => 1, 'kwaad' => 1, 'uw' => 1, 'één' => 1, 'wij' => 1,

        // Two beats. The final -e is spoken, so there is no silent-e rule to
        // undo the way English needs one.
        'mode' => 2, 'water' => 2, 'tafel' => 2, 'mensen' => 2, 'appel' => 2,
        'lopen' => 2, 'maken' => 2, 'komen' => 2, 'idee' => 2, 'auto' => 2,
        'ijzer' => 2, 'bijna' => 2, 'altijd' => 2, 'seizoen' => 2, 'moeilijk' => 2,
        'geluid' => 2, 'vrijheid' => 2, 'aardbei' => 2, 'voorbeeld' => 2, 'hoeveel' => 2,
        'mooie' => 2, 'koeien' => 2, 'bureau' => 2, 'cadeau' => 2, 'niveau' => 2,
        'oorlog' => 2, 'café' => 2, 'privé' => 2, 'nachtrust' => 2, 'systeem' => 2,
        'baby' => 2, 'hobby' => 2, 'typisch' => 2, 'yoga' => 2, 'royaal' => 2,

        // The diaeresis is Dutch spelling saying "start a new syllable here",
        // and the counter has to hear it.
        'zeeën' => 2, 'cliënt' => 2, 'ideeën' => 3, 'creëren' => 3, 'patiënt' => 3,
        'kopiëren' => 4, 'efficiënt' => 4, 'ruïne' => 3, 'coördinatie' => 5,

        // Three beats and up.
        'kinderen' => 3, 'gebouwen' => 3, 'belangrijk' => 3, 'natuurlijk' => 3,
        'eenvoudig' => 3, 'dagelijks' => 3, 'gebruiken' => 3, 'ervaring' => 3,
        'kwaliteit' => 3, 'geschreven' => 3, 'gemeente' => 3, 'onderzoek' => 3,
        'wetenschap' => 3, 'aardappel' => 3, 'koninkrijk' => 3, 'zeventien' => 3,
        'waarschijnlijk' => 3, 'bijvoorbeeld' => 3, 'politie' => 3, 'vakantie' => 3,
        'familie' => 3, 'energie' => 3, 'provincie' => 3, 'nederlandse' => 4,
        'gemakkelijk' => 4, 'geleidelijk' => 4, 'gebeurtenis' => 4, 'informatie' => 4,
        'geschiedenis' => 4, 'verschillende' => 4, 'ontwikkeling' => 4, 'categorie' => 4,
        'organisatie' => 5, 'communicatie' => 5, 'mogelijkheden' => 5, 'universiteit' => 5,

        // Two vowels side by side that are said apart. Dutch spells no single
        // sound "eo", "ea", "ua" or "io", so the counter must break there.
        'chaos' => 2, 'duo' => 2, 'trio' => 2, 'zoiets' => 2, 'video' => 3,
        'radio' => 3, 'studio' => 3, 'audio' => 3, 'theater' => 3, 'ideaal' => 3,
        'reactie' => 3, 'sociaal' => 3, 'speciaal' => 3, 'theorie' => 3, 'eieren' => 3,
        'januari' => 4, 'februari' => 4, 'situatie' => 4, 'materiaal' => 4,
        'financieel' => 4, 'beoordelen' => 4,

        // The deviation list: the vowel groups are right about the spelling and
        // wrong about the word.
        'museum' => 3, 'jubileum' => 4,
    ];
}

it('counts the syllables of every dutch fixture word exactly', function () {
    $counter = dutchSyllables();
    $wrong = [];

    foreach (dutchSyllableFixture() as $word => $expected) {
        $counted = $counter->count($word);

        if ($counted !== $expected) {
            $wrong[$word] = "expected {$expected}, counted {$counted}";
        }
    }

    expect($wrong)->toBe([]);
});

it('ships a dutch fixture big enough to hold the counter honest', function () {
    expect(count(dutchSyllableFixture()))->toBeGreaterThanOrEqual(100);
});

it('counts at least one syllable for any dutch word with a letter in it', function (string $word) {
    expect(dutchSyllables()->count($word))->toBe(1);
})->with([
    'a single letter' => ['u'],
    'a word of nothing but consonants' => ['tsj'],
    'a two letter word ending in e' => ['de'],
    'a word that is only a diphthong' => ['ei'],
]);

it('counts nothing for a dutch word with no letters at all', function (string $word) {
    expect(dutchSyllables()->count($word))->toBe(0);
})->with([
    'nothing at all' => [''],
    'whitespace' => ['   '],
    'punctuation' => ['—'],
    'digits' => ['2024'],
]);

it('ignores case and surrounding punctuation in dutch', function () {
    $counter = dutchSyllables();

    expect($counter->count('MUSEUM'))->toBe(3)
        ->and($counter->count('Museum'))->toBe(3)
        ->and($counter->count("auto's"))->toBe(2)
        ->and($counter->count('coördinatie.'))->toBe(5);
});
