<?php

namespace TwillSeo\Tests\Unit\Analysis\Language;

use TwillSeo\Analysis\Language\DefaultLanguagePack;
use TwillSeo\Analysis\Language\LanguagePackRegistry;

it('falls back to the default pack for a language it does not know', function (string $locale) {
    $registry = new LanguagePackRegistry;

    expect($registry->forLocale($locale))->toBeInstanceOf(DefaultLanguagePack::class)
        ->and($registry->forLocale($locale)->supportsFullReadability())->toBeFalse();
})->with(['en', 'nl_NL', 'xx', '']);

it('resolves a registered pack through every spelling of its locale', function (string $locale) {
    $registry = new LanguagePackRegistry;
    $dutch = new DefaultLanguagePack('nl');
    $registry->register($dutch);

    expect($registry->forLocale($locale))->toBe($dutch);
})->with(['nl', 'nl_NL', 'nl-NL', 'NL', 'nl_BE']);

it('keeps a registered pack away from another language', function () {
    $registry = new LanguagePackRegistry;
    $registry->register(new DefaultLanguagePack('nl'));

    expect($registry->forLocale('de_DE'))->not->toBe($registry->forLocale('nl'));
});

it('gives the default pack generic capabilities and no readability support', function () {
    $pack = new DefaultLanguagePack;

    expect($pack->functionWords()->isEmpty())->toBeTrue()
        ->and($pack->transitionWords())->toBeNull()
        ->and($pack->firstWordExceptions())->toBeNull()
        ->and($pack->passiveVoice())->toBeNull()
        ->and($pack->syllableCounter())->toBeNull()
        ->and($pack->fleschFormula())->toBeNull()
        ->and($pack->sentenceLengthLimit())->toBe(20)
        ->and($pack->supportsFullReadability())->toBeFalse();
});
