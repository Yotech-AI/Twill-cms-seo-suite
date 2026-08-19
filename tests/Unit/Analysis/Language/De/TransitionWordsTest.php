<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\De;

use TwillSeo\Analysis\Language\De\GermanLanguagePack;
use TwillSeo\Analysis\Language\TransitionWords;

function germanTransitions(): TransitionWords
{
    return (new GermanLanguagePack)->transitionWords();
}

it('recognises a german transition from every category', function (string $sentence) {
    expect(germanTransitions()->occursIn($sentence))->toBeTrue();
})->with([
    'additive' => ['Außerdem hält der Akku länger.'],
    'additive phrase' => ['Darüber hinaus hält der Akku länger.'],
    'contrast' => ['Jedoch ist der Akku schnell leer.'],
    'contrast phrase' => ['Auf der anderen Seite ist der Akku schnell leer.'],
    'cause' => ['Deshalb haben wir den Akku ersetzt.'],
    'cause phrase' => ['Aus diesem Grund haben wir den Akku ersetzt.'],
    'sequence' => ['Anschließend haben wir den Akku ersetzt.'],
    'sequence phrase' => ['Zum Schluss haben wir den Akku ersetzt.'],
    'example' => ['Beispielsweise ist der Akku das Problem.'],
    'example phrase' => ['Zum Beispiel ist der Akku das Problem.'],
    'summary' => ['Zusammenfassend ist der Akku das Problem.'],
    'summary phrase' => ['Mit anderen Worten: der Akku ist das Problem.'],
    'condition' => ['Sofern der Akku ersetzt wird, hält er wieder.'],
    'time' => ['Während des Winters versagt der Akku.'],
    'two part sowohl als auch' => ['Sowohl der Akku als auch das Ladegerät ist kaputt.'],
    'two part entweder oder' => ['Entweder der Akku oder das Ladegerät ist kaputt.'],
    'two part weder noch' => ['Weder der Akku noch das Ladegerät hält lange.'],
    'two part je desto' => ['Je kälter es ist, desto schneller ist der Akku leer.'],
]);

it('leaves a german sentence with no transition alone', function (string $sentence) {
    expect(germanTransitions()->occursIn($sentence))->toBeFalse();
})->with([
    'a plain statement' => ['Der Akku hält acht Stunden.'],
    // Whole-word matching: "etwa" must not fire inside "etwas".
    'a longer word that starts like a transition' => ['Der Akku hält etwas länger.'],
    'a question' => ['Welcher Akku hält am längsten?'],
    // Both halves of a pair are needed, in order.
    'only one half of a two part transition' => ['Der Akku und das Ladegerät sind kaputt.'],
]);

it('matches a german phrase only when its words are together', function (string $sentence, bool $expected) {
    expect(germanTransitions()->occursIn($sentence))->toBe($expected);
})->with([
    'the whole phrase' => ['Vor allem hat es funktioniert.', true],
    'the words apart' => ['Vor dem allem steht ein Wort.', false],
    'punctuation between the words' => ["Mit anderen\nWorten, es hat funktioniert.", true],
]);
