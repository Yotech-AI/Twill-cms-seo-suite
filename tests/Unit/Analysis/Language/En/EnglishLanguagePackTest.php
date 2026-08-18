<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\En;

use TwillSeo\Analysis\Language\Data\DataFileLoader;
use TwillSeo\Analysis\Language\En\EnglishLanguagePack;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Analysis\Research\Support\KeyphraseMatcher;

function englishDataFile(string $name): array
{
    return DataFileLoader::load(dirname(__DIR__, 5).'/resources/lang-data/en/'.$name.'.php');
}

it('answers to english and supports the full readability analysis', function () {
    $pack = new EnglishLanguagePack;

    expect($pack->code())->toBe('en')
        ->and($pack->supportsFullReadability())->toBeTrue()
        ->and($pack->sentenceLengthLimit())->toBe(20);
});

it('carries every readability capability', function () {
    $pack = new EnglishLanguagePack;

    expect($pack->transitionWords())->not->toBeNull()
        ->and($pack->firstWordExceptions())->not->toBeNull()
        ->and($pack->passiveVoice())->not->toBeNull()
        ->and($pack->syllableCounter())->not->toBeNull()
        ->and($pack->fleschFormula())->not->toBeNull()
        ->and($pack->functionWords()->isEmpty())->toBeFalse();
});

it('is what the default registry hands out for english', function (string $locale) {
    $pack = LanguagePackRegistry::withDefaults()->forLocale($locale);

    expect($pack)->toBeInstanceOf(EnglishLanguagePack::class);
})->with(['en', 'en_GB', 'en-US', 'EN']);

it('still falls back to the generic pack for an unknown language', function () {
    expect(LanguagePackRegistry::withDefaults()->forLocale('nl_NL')->supportsFullReadability())->toBeFalse();
});

it('keeps an abbreviation inside its sentence', function (string $text, array $sentences) {
    expect((new EnglishLanguagePack)->sentenceTokenizer()->tokenize($text))->toBe($sentences);
})->with([
    'a title' => ['Dr. Smith arrived. He waited.', ['Dr. Smith arrived.', 'He waited.']],
    'a company' => ['We called Acme Inc. They answered.', ['We called Acme Inc. They answered.']],
    'a measurement' => ['Wait 5 min. Then stir.', ['Wait 5 min. Then stir.']],
    'an address' => ['It is on Fifth Ave. Turn left there.', ['It is on Fifth Ave. Turn left there.']],
    // "no." is deliberately not an abbreviation: a sentence ending in the word
    // "no" is far more common in copy than a numbered reference.
    'a sentence that ends in no' => ['The answer is no. Next question.', ['The answer is no.', 'Next question.']],
]);

it('strips english function words from a keyphrase', function (string $keyphrase, array $contentWords) {
    expect((new KeyphraseMatcher)->contentWords($keyphrase, new EnglishLanguagePack))->toBe($contentWords);
})->with([
    'articles and prepositions' => ['the best of the dog food', ['best', 'dog', 'food']],
    'auxiliaries and modals' => ['what you should do about mould', ['mould']],
    'numbers' => ['ten tips for first time buyers', ['tips', 'time', 'buyers']],
    // Words that double as a real subject stay, or the keyphrase would quietly
    // become a different one.
    'a content word that looks like a quantifier' => ['whole grain bread', ['whole', 'grain', 'bread']],
    'a content word that looks like a preposition' => ['half marathon training', ['half', 'marathon', 'training']],
    'a phrase of nothing but function words falls back' => ['about us', ['about', 'us']],
]);

it('skips a determiner when comparing how sentences begin', function () {
    $exceptions = (new EnglishLanguagePack)->firstWordExceptions();

    expect($exceptions->contains('The'))->toBeTrue()
        ->and($exceptions->contains('three'))->toBeTrue()
        ->and($exceptions->contains('cat'))->toBeFalse();
});

it('ships word lists that are actually compiled', function (string $file, int $minimum) {
    expect(count(englishDataFile($file)))->toBeGreaterThanOrEqual($minimum);
})->with([
    'function words' => ['function-words', 250],
    'transition words' => ['transition-words', 120],
    'two part transitions' => ['two-part-transitions', 10],
    'first word exceptions' => ['first-word-exceptions', 20],
    'abbreviations' => ['abbreviations', 25],
    'passive auxiliaries' => ['passive/auxiliaries', 10],
    'irregular participles' => ['passive/irregular-participles', 180],
    'non participles' => ['passive/non-participles', 25],
]);

it('lists no word twice', function (string $file) {
    $entries = array_map(fn ($entry) => is_array($entry) ? implode(' ', $entry) : $entry, englishDataFile($file));

    expect(array_unique($entries))->toHaveCount(count($entries));
})->with([
    'function words' => ['function-words'],
    'transition words' => ['transition-words'],
    'two part transitions' => ['two-part-transitions'],
    'first word exceptions' => ['first-word-exceptions'],
    'abbreviations' => ['abbreviations'],
    'passive auxiliaries' => ['passive/auxiliaries'],
    'irregular participles' => ['passive/irregular-participles'],
    'non participles' => ['passive/non-participles'],
]);

it('never lists a word as both a participle and not one', function () {
    $overlap = array_intersect(
        englishDataFile('passive/irregular-participles'),
        englishDataFile('passive/non-participles'),
    );

    expect($overlap)->toBe([]);
});
