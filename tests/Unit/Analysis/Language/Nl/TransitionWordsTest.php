<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\Nl;

use TwillSeo\Analysis\Language\Nl\DutchLanguagePack;
use TwillSeo\Analysis\Language\TransitionWords;

function dutchTransitions(): TransitionWords
{
    return (new DutchLanguagePack)->transitionWords();
}

it('recognises a dutch transition from every category', function (string $sentence) {
    expect(dutchTransitions()->occursIn($sentence))->toBeTrue();
})->with([
    'additive' => ['Bovendien gaat de batterij langer mee.'],
    'additive phrase' => ['Daar komt bij dat de batterij langer meegaat.'],
    'contrast' => ['Echter, de batterij loopt snel leeg.'],
    'contrast phrase' => ['Aan de andere kant loopt de batterij snel leeg.'],
    'cause' => ['Daarom hebben wij de batterij vervangen.'],
    'cause phrase' => ['Als gevolg van de storing hebben wij de batterij vervangen.'],
    'sequence' => ['Vervolgens hebben wij de batterij vervangen.'],
    'sequence phrase' => ['Ten slotte hebben wij de batterij vervangen.'],
    'example' => ['Bijvoorbeeld de batterij is het probleem.'],
    'example phrase' => ['Onder andere de batterij is het probleem.'],
    'summary' => ['Kortom, de batterij is het probleem.'],
    'summary phrase' => ['Met andere woorden: de batterij is het probleem.'],
    'condition' => ['Tenzij de batterij wordt vervangen, blijft hij falen.'],
    'time' => ['Terwijl de winter duurt, faalt de batterij.'],
    'two part zowel als' => ['Zowel de batterij als de lader is stuk.'],
    'two part niet alleen maar ook' => ['Niet alleen de batterij maar ook de lader is stuk.'],
    'two part enerzijds anderzijds' => ['Enerzijds is hij snel, anderzijds is hij duur.'],
]);

it('leaves a dutch sentence with no transition alone', function (string $sentence) {
    expect(dutchTransitions()->occursIn($sentence))->toBeFalse();
})->with([
    'a plain statement' => ['De batterij gaat acht uur mee.'],
    // Whole-word matching: "ook" must not fire inside "ookal" or "boek".
    'a longer word that contains a transition' => ['Wij lazen het boek de hele middag.'],
    'a question' => ['Welke batterij gaat het langst mee?'],
    // Both halves of a pair are needed, in order.
    'only one half of a two part transition' => ['De lader en de batterij zijn stuk.'],
]);

it('matches a dutch phrase only when its words are together', function (string $sentence, bool $expected) {
    expect(dutchTransitions()->occursIn($sentence))->toBe($expected);
})->with([
    'the whole phrase' => ['In het kort: het werkte.', true],
    'the words apart' => ['In dit kort verslag staat alles.', false],
    'punctuation between the words' => ["Met andere\nwoorden, het werkte.", true],
]);
