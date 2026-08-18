<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\En;

use TwillSeo\Analysis\Language\En\EnglishLanguagePack;
use TwillSeo\Analysis\Language\TransitionWords;

function transitionsOf(array $singles, array $twoPart = []): TransitionWords
{
    return new TransitionWords($singles, $twoPart);
}

function englishTransitions(): TransitionWords
{
    return (new EnglishLanguagePack)->transitionWords();
}

it('finds a single transition word as a whole word', function (string $sentence, bool $expected) {
    expect(transitionsOf(['however', 'so', 'in addition'])->occursIn($sentence))->toBe($expected);
})->with([
    'the word on its own' => ['However, the price went up.', true],
    'the word mid sentence' => ['The price, however, went up.', true],
    'a different case' => ['HOWEVER the price went up.', true],
    'a short word is still matched whole' => ['So the price went up.', true],
    // Without whole word matching every sofa and every howitzer would count.
    'a longer word that starts the same way' => ['We bought a sofa today.', false],
    'a longer word that ends the same way' => ['The whatsoever clause applies.', false],
    'no transition at all' => ['The price went up.', false],
    'nothing at all' => ['', false],
]);

it('matches a multi word phrase only when the words are together', function (string $sentence, bool $expected) {
    expect(transitionsOf(['in addition', 'on the other hand'])->occursIn($sentence))->toBe($expected);
})->with([
    'the whole phrase' => ['In addition, we lowered the price.', true],
    'the phrase mid sentence' => ['We lowered it and in addition we apologised.', true],
    'a longer phrase' => ['On the other hand, the quality went up.', true],
    'the words apart' => ['In this addition we lowered the price.', false],
    'only the first word' => ['In the shop we lowered the price.', false],
]);

it('matches a two part transition only when both halves appear in order', function (string $sentence, bool $expected) {
    expect(transitionsOf([], [['both', 'and'], ['not only', 'but also']])->occursIn($sentence))->toBe($expected);
})->with([
    'both halves in order' => ['Both cats and dogs are welcome.', true],
    'a longer pair' => ['Not only cats but also dogs are welcome.', true],
    'the halves the wrong way round' => ['Dogs and cats are both welcome.', false],
    'only the first half' => ['Both of them are welcome.', false],
    'only the second half' => ['Cats and dogs are welcome.', false],
]);

it('treats punctuation and extra whitespace as word separators', function () {
    expect(transitionsOf(['in short'])->occursIn("In  short:\nit worked."))->toBeTrue();
});

it('recognises an english transition from every category', function (string $sentence) {
    expect(englishTransitions()->occursIn($sentence))->toBeTrue();
})->with([
    'additive' => ['Furthermore, the battery lasts longer.'],
    'additive phrase' => ['In addition, the battery lasts longer.'],
    'contrast' => ['Nevertheless, the battery drains fast.'],
    'contrast phrase' => ['On the other hand, the battery drains fast.'],
    'cause' => ['Therefore, we replaced the battery.'],
    'cause phrase' => ['As a result, we replaced the battery.'],
    'sequence' => ['Finally, we replaced the battery.'],
    'sequence phrase' => ['In the meantime, we replaced the battery.'],
    'example' => ['Specifically, the battery is the problem.'],
    'example phrase' => ['For example, the battery is the problem.'],
    'summary' => ['Ultimately, the battery is the problem.'],
    'summary phrase' => ['In conclusion, the battery is the problem.'],
    'condition' => ['Unless the battery is replaced, it fails.'],
    'time' => ['During the winter the battery fails.'],
    'two part' => ['Either the battery or the charger has failed.'],
]);

it('leaves a sentence with no transition alone', function (string $sentence) {
    expect(englishTransitions()->occursIn($sentence))->toBeFalse();
})->with([
    'a plain statement' => ['The battery lasts eight hours.'],
    'a statement with a near miss' => ['We tested the batteries all afternoon.'],
    'a question' => ['Which battery lasts longest?'],
]);
