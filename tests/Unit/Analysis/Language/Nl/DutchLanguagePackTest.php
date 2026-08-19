<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\Nl;

use TwillSeo\Analysis\Language\Data\DataFileLoader;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Analysis\Language\Nl\DutchLanguagePack;
use TwillSeo\Analysis\Research\Support\KeyphraseMatcher;

function dutchDataFile(string $name): array
{
    return DataFileLoader::load(dirname(__DIR__, 5).'/resources/lang-data/nl/'.$name.'.php');
}

it('answers to dutch and supports the full readability analysis', function () {
    $pack = new DutchLanguagePack;

    expect($pack->code())->toBe('nl')
        ->and($pack->supportsFullReadability())->toBeTrue()
        ->and($pack->sentenceLengthLimit())->toBe(20);
});

it('carries every readability capability in dutch', function () {
    $pack = new DutchLanguagePack;

    expect($pack->transitionWords())->not->toBeNull()
        ->and($pack->firstWordExceptions())->not->toBeNull()
        ->and($pack->passiveVoice())->not->toBeNull()
        ->and($pack->syllableCounter())->not->toBeNull()
        ->and($pack->fleschFormula())->not->toBeNull()
        ->and($pack->functionWords()->isEmpty())->toBeFalse();
});

it('is what the default registry hands out for dutch', function (string $locale) {
    expect(LanguagePackRegistry::withDefaults()->forLocale($locale))->toBeInstanceOf(DutchLanguagePack::class);
})->with(['nl', 'nl_NL', 'nl-BE', 'NL']);

it('keeps a dutch abbreviation inside its sentence', function (string $text, array $sentences) {
    expect((new DutchLanguagePack)->sentenceTokenizer()->tokenize($text))->toBe($sentences);
})->with([
    'a title' => ['Dhr. Jansen kwam binnen. Hij wachtte.', ['Dhr. Jansen kwam binnen.', 'Hij wachtte.']],
    'an academic title' => ['Wij spraken drs. De Vries. Zij belde terug.', ['Wij spraken drs. De Vries.', 'Zij belde terug.']],
    'an example' => ['Neem bijv. groene thee. Die smaakt fris.', ['Neem bijv. groene thee.', 'Die smaakt fris.']],
    'and so on' => ['Denk aan thee, koffie enz. Alles is er.', ['Denk aan thee, koffie enz. Alles is er.']],
    // A Dutch decimal is written with a comma, and the tokenizer only ever
    // splits on a full stop, so it cannot come apart in the first place.
    'a decimal written with a comma' => ['Het kost 3,5 euro per stuk. Echt waar.', ['Het kost 3,5 euro per stuk.', 'Echt waar.']],
    'a decimal written with a point' => ['Het weegt 1.5 kilo in totaal. Precies.', ['Het weegt 1.5 kilo in totaal.', 'Precies.']],
]);

it('strips dutch function words from a keyphrase', function (string $keyphrase, array $contentWords) {
    expect((new KeyphraseMatcher)->contentWords($keyphrase, new DutchLanguagePack))->toBe($contentWords);
})->with([
    'articles and prepositions' => ['de voeding voor de hond', ['voeding', 'hond']],
    'auxiliaries and modals' => ['wat je moet doen tegen schimmel', ['schimmel']],
    'numbers' => ['tien tips voor de eerste keer', ['tips', 'keer']],
    // Words that double as a real subject stay, or the keyphrase would quietly
    // become a different one.
    'a content word that looks like a quantifier' => ['halve marathon training', ['halve', 'marathon', 'training']],
    'a content word that looks like an adverb' => ['vrij parkeren amsterdam', ['vrij', 'parkeren', 'amsterdam']],
    'a phrase of nothing but function words falls back' => ['over ons', ['over', 'ons']],
]);

it('skips a determiner when comparing how dutch sentences begin', function () {
    $exceptions = (new DutchLanguagePack)->firstWordExceptions();

    expect($exceptions->contains('De'))->toBeTrue()
        ->and($exceptions->contains('er'))->toBeTrue()
        ->and($exceptions->contains('drie'))->toBeTrue()
        ->and($exceptions->contains('kat'))->toBeFalse();
});

it('ships dutch word lists that are actually compiled', function (string $file, int $minimum) {
    expect(count(dutchDataFile($file)))->toBeGreaterThanOrEqual($minimum);
})->with([
    'function words' => ['function-words', 300],
    'transition words' => ['transition-words', 110],
    'two part transitions' => ['two-part-transitions', 10],
    'first word exceptions' => ['first-word-exceptions', 20],
    'abbreviations' => ['abbreviations', 25],
    'passive auxiliaries' => ['passive/auxiliaries', 10],
    'irregular participles' => ['passive/irregular-participles', 50],
    'non participles' => ['passive/non-participles', 50],
]);

it('lists no dutch word twice', function (string $file) {
    $entries = array_map(fn ($entry) => is_array($entry) ? implode(' ', $entry) : $entry, dutchDataFile($file));

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

it('never lists a dutch word as both a participle and not one', function () {
    $overlap = array_intersect(
        dutchDataFile('passive/irregular-participles'),
        dutchDataFile('passive/non-participles'),
    );

    expect($overlap)->toBe([]);
});
