<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\De;

use TwillSeo\Analysis\Language\Data\DataFileLoader;
use TwillSeo\Analysis\Language\De\GermanLanguagePack;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Analysis\Research\Support\KeyphraseMatcher;

function germanDataFile(string $name): array
{
    return DataFileLoader::load(dirname(__DIR__, 5).'/resources/lang-data/de/'.$name.'.php');
}

it('answers to german and supports the full readability analysis', function () {
    $pack = new GermanLanguagePack;

    expect($pack->code())->toBe('de')
        ->and($pack->supportsFullReadability())->toBeTrue()
        ->and($pack->sentenceLengthLimit())->toBe(20);
});

it('carries every readability capability in german', function () {
    $pack = new GermanLanguagePack;

    expect($pack->transitionWords())->not->toBeNull()
        ->and($pack->firstWordExceptions())->not->toBeNull()
        ->and($pack->passiveVoice())->not->toBeNull()
        ->and($pack->syllableCounter())->not->toBeNull()
        ->and($pack->fleschFormula())->not->toBeNull()
        ->and($pack->functionWords()->isEmpty())->toBeFalse();
});

it('is what the default registry hands out for german', function (string $locale) {
    expect(LanguagePackRegistry::withDefaults()->forLocale($locale))->toBeInstanceOf(GermanLanguagePack::class);
})->with(['de', 'de_DE', 'de-AT', 'DE']);

it('keeps a german abbreviation inside its sentence', function (string $text, array $sentences) {
    expect((new GermanLanguagePack)->sentenceTokenizer()->tokenize($text))->toBe($sentences);
})->with([
    'a title' => ['Dr. Schmidt kam an. Er wartete.', ['Dr. Schmidt kam an.', 'Er wartete.']],
    'a professor' => ['Wir sprachen mit Prof. Weber. Sie rief zurück.', ['Wir sprachen mit Prof. Weber.', 'Sie rief zurück.']],
    'an approximation' => ['Es dauert ca. 20 Minuten. Danach ist es fertig.', ['Es dauert ca. 20 Minuten.', 'Danach ist es fertig.']],
    'and so on' => ['Denken Sie an Tee, Kaffee usw. Alles ist da.', ['Denken Sie an Tee, Kaffee usw. Alles ist da.']],
    // A German decimal is written with a comma, and the tokenizer only ever
    // splits on a full stop, so it cannot come apart in the first place.
    'a decimal written with a comma' => ['Es kostet 3,5 Euro pro Stück. Wirklich.', ['Es kostet 3,5 Euro pro Stück.', 'Wirklich.']],
    'a decimal written with a point' => ['Es wiegt 1.5 Kilo insgesamt. Genau.', ['Es wiegt 1.5 Kilo insgesamt.', 'Genau.']],
]);

it('strips german function words from a keyphrase', function (string $keyphrase, array $contentWords) {
    expect((new KeyphraseMatcher)->contentWords($keyphrase, new GermanLanguagePack))->toBe($contentWords);
})->with([
    'articles and prepositions' => ['das Futter für den Hund', ['futter', 'hund']],
    'auxiliaries and modals' => ['was man über Schimmel wissen muss', ['schimmel', 'wissen']],
    'numbers' => ['zehn Tipps für den Anfang', ['tipps', 'anfang']],
    // Words that double as a real subject stay, or the keyphrase would quietly
    // become a different one.
    'a content word that looks like an adverb' => ['recht auf Auskunft', ['recht', 'auskunft']],
    'a phrase of nothing but function words falls back' => ['über uns', ['über', 'uns']],
]);

it('skips a determiner in every case form when comparing how german sentences begin', function () {
    $exceptions = (new GermanLanguagePack)->firstWordExceptions();

    expect($exceptions->contains('Der'))->toBeTrue()
        ->and($exceptions->contains('dem'))->toBeTrue()
        ->and($exceptions->contains('eines'))->toBeTrue()
        ->and($exceptions->contains('drei'))->toBeTrue()
        ->and($exceptions->contains('Katze'))->toBeFalse();
});

it('ships german word lists that are actually compiled', function (string $file, int $minimum) {
    expect(count(germanDataFile($file)))->toBeGreaterThanOrEqual($minimum);
})->with([
    'function words' => ['function-words', 350],
    'transition words' => ['transition-words', 110],
    'two part transitions' => ['two-part-transitions', 10],
    'first word exceptions' => ['first-word-exceptions', 20],
    'abbreviations' => ['abbreviations', 25],
    'passive auxiliaries' => ['passive/auxiliaries', 10],
    'irregular participles' => ['passive/irregular-participles', 70],
    'non participles' => ['passive/non-participles', 50],
]);

it('lists no german word twice', function (string $file) {
    $entries = array_map(fn ($entry) => is_array($entry) ? implode(' ', $entry) : $entry, germanDataFile($file));

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

it('never lists a german word as both a participle and not one', function () {
    $overlap = array_intersect(
        germanDataFile('passive/irregular-participles'),
        germanDataFile('passive/non-participles'),
    );

    expect($overlap)->toBe([]);
});
