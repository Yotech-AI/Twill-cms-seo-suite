<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\En;

use TwillSeo\Analysis\Language\En\EnglishLanguagePack;
use TwillSeo\Analysis\Language\SyllableCounter;

function englishSyllables(): SyllableCounter
{
    return (new EnglishLanguagePack)->syllableCounter();
}

/**
 * The fixture the counter is held to. Every entry was counted by reading the
 * word aloud; a change to the counter that moves any of them is a regression,
 * not a refinement.
 *
 * @return array<string,int>
 */
function syllableFixture(): array
{
    return [
        // One beat, however many vowels are written.
        'cat' => 1, 'dog' => 1, 'through' => 1, 'thought' => 1, 'strength' => 1,
        'straight' => 1, 'world' => 1, 'time' => 1, 'work' => 1, 'house' => 1,
        'school' => 1, 'friend' => 1, 'night' => 1, 'please' => 1, 'place' => 1,
        'make' => 1, 'made' => 1, 'free' => 1, 'three' => 1, 'eight' => 1,
        'search' => 1, 'score' => 1, 'voice' => 1, 'health' => 1, 'style' => 1,
        // A silent -e that a plural or a past tense keeps silent.
        'makes' => 1, 'times' => 1, 'homes' => 1, 'smiles' => 1, 'notes' => 1,
        'walked' => 1, 'watched' => 1, 'played' => 1, 'used' => 1, 'freed' => 1,

        // Two beats.
        'water' => 2, 'table' => 2, 'little' => 2, 'people' => 2, 'simple' => 2,
        'apple' => 2, 'single' => 2, 'middle' => 2, 'tables' => 2, 'candles' => 2,
        'because' => 2, 'before' => 2, 'about' => 2, 'reader' => 2, 'writing' => 2,
        'written' => 2, 'easy' => 2, 'content' => 2,
        // -ing after a vowel is a beat the vowel groups run together.
        'being' => 2, 'going' => 2, 'doing' => 2, 'playing' => 2, 'trying' => 2,
        'thing' => 1, 'string' => 1, 'reading' => 2,
        'keyword' => 2, 'heading' => 2, 'passive' => 2, 'website' => 2, 'online' => 2,
        'image' => 2, 'engine' => 2, 'orange' => 2, 'language' => 2, 'sentence' => 2,
        'question' => 2, 'nation' => 2, 'wanted' => 2, 'needed' => 2, 'houses' => 2,
        'boxes' => 2, 'pages' => 2, 'places' => 2, 'watches' => 2, 'wishes' => 2,

        // Three beats and up.
        'beautiful' => 3, 'important' => 3, 'another' => 3, 'paragraph' => 3,
        'family' => 3, 'camera' => 3, 'probably' => 3, 'average' => 3, 'readable' => 3,
        'syllable' => 3, 'restaurant' => 3, 'difficult' => 3, 'analysis' => 4,
        'information' => 4, 'available' => 4, 'comfortable' => 4, 'university' => 5,
        'optimization' => 5,

        // The deviation list: spelling and speech disagree.
        'business' => 2, 'wednesday' => 2, 'chocolate' => 2, 'evening' => 2,
        'every' => 2, 'different' => 2, 'interesting' => 3, 'everything' => 3,
        'science' => 2, 'rhythm' => 2, 'area' => 3, 'idea' => 3, 'video' => 3,
        'radio' => 3, 'audio' => 3, 'piano' => 3, 'lion' => 2, 'poem' => 2,
        'quiet' => 2, 'maybe' => 2, 'recipe' => 3, 'create' => 2, 'created' => 3,
        'experience' => 4,
    ];
}

it('counts the syllables of every fixture word exactly', function () {
    $counter = englishSyllables();
    $wrong = [];

    foreach (syllableFixture() as $word => $expected) {
        $counted = $counter->count($word);

        if ($counted !== $expected) {
            $wrong[$word] = "expected {$expected}, counted {$counted}";
        }
    }

    expect($wrong)->toBe([]);
});

it('ships a fixture big enough to hold the counter honest', function () {
    expect(count(syllableFixture()))->toBeGreaterThanOrEqual(100);
});

it('counts at least one syllable for anything with a letter in it', function (string $word) {
    expect(englishSyllables()->count($word))->toBe(1);
})->with([
    'a single letter' => ['a'],
    'a word of nothing but consonants' => ['tsk'],
    'a two letter word ending in e' => ['be'],
    'a word ending in a silent e that carries the only vowel' => ['the'],
]);

it('counts nothing for a word with no letters at all', function (string $word) {
    expect(englishSyllables()->count($word))->toBe(0);
})->with([
    'nothing at all' => [''],
    'whitespace' => ['   '],
    'punctuation' => ['—'],
    'digits' => ['2024'],
]);

it('ignores case and surrounding punctuation', function () {
    $counter = englishSyllables();

    expect($counter->count('BUSINESS'))->toBe(2)
        ->and($counter->count('Business'))->toBe(2)
        ->and($counter->count("don't"))->toBe(1);
});
