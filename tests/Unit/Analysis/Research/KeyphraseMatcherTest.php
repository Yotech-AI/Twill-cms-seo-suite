<?php

namespace TwillSeo\Tests\Unit\Analysis\Research;

use TwillSeo\Analysis\Language\DefaultLanguagePack;
use TwillSeo\Analysis\Language\LanguagePack;
use TwillSeo\Analysis\Research\Support\KeyphraseMatcher;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function matcher(): KeyphraseMatcher
{
    return new KeyphraseMatcher;
}

function englishish(): LanguagePack
{
    return AnalysisFactory::languagePack(['the', 'a', 'of', 'and', 'is']);
}

it('reduces a keyphrase to its content words', function (string $keyphrase, array $expected) {
    expect(matcher()->contentWords($keyphrase, englishish()))->toBe($expected);
})->with([
    'function words are stripped' => ['the best of dogs', ['best', 'dogs']],
    'case is folded' => ['The Best Dogs', ['best', 'dogs']],
    'a curly apostrophe folds onto the straight one' => ['a dog’s life', ["dog's", 'life']],
    'a hyphenated word stays whole' => ['the well-known dog', ['well-known', 'dog']],
    // Stripping everything would leave nothing to match on, so the whole
    // keyphrase is kept instead of silently matching every text.
    'an all function word keyphrase falls back to every word' => ['the a of', ['the', 'a', 'of']],
    'nothing at all' => ['', []],
]);

it('keeps every word when the language pack has no function words', function () {
    expect(matcher()->contentWords('the best of dogs', new DefaultLanguagePack))
        ->toBe(['the', 'best', 'of', 'dogs']);
});

it('recognises a keyphrase made only of function words', function (string $keyphrase, bool $expected) {
    expect(matcher()->isOnlyFunctionWords($keyphrase, englishish()))->toBe($expected);
})->with([
    'all function words' => ['the a of', true],
    'one function word' => ['the', true],
    'mixed' => ['the dog', false],
    'no function words' => ['best dogs', false],
    'empty is not a keyphrase at all' => ['', false],
    'whitespace only' => ['   ', false],
]);

it('never calls a keyphrase all function words when the pack has none', function () {
    expect(matcher()->isOnlyFunctionWords('the a of', new DefaultLanguagePack))->toBeFalse();
});

it('finds every content word in a text', function (array $words, string $text, bool $expected) {
    expect(matcher()->allWordsInText($words, $text))->toBe($expected);
})->with([
    'all present' => [['best', 'dogs'], 'The best dogs are here.', true],
    'one missing' => [['best', 'cats'], 'The best dogs are here.', false],
    'case is folded on both sides' => [['Best', 'DOGS'], 'the BEST Dogs', true],
    'a needle is matched as a whole word' => [['dog'], 'The dogs are here.', false],
    'a hyphenated haystack word is retried in parts' => [['well', 'known'], 'a well-known fact', true],
    'a hyphenated needle matches a hyphenated word' => [['well-known'], 'a well-known fact', true],
    'a hyphenated needle matches a spaced haystack' => [['well-known'], 'a well known fact', true],
    'a hyphenated needle is still whole-word: one missing part fails' => [['well-known'], 'a well done fact', false],
    'a curly apostrophe in the text still matches' => [["don't"], 'we don’t stop', true],
    'an entity in the text still matches' => [['tom', 'jerry'], 'Tom &amp; Jerry', true],
    'no words at all matches anything' => [[], 'The best dogs.', true],
    'nothing matches an empty text' => [['best'], '', false],
]);

it('checks whether one sentence holds every content word', function () {
    $sentences = ['The best cats are loud.', 'The best dogs are here.'];

    expect(matcher()->allWordsInOneSentence(['best', 'dogs'], $sentences))->toBeTrue()
        ->and(matcher()->allWordsInOneSentence(['cats', 'dogs'], $sentences))->toBeFalse()
        ->and(matcher()->allWordsInOneSentence(['best'], []))->toBeFalse();
});

it('counts a keyphrase as its least common content word in each sentence', function () {
    // best appears twice and dog once in the first sentence, so the phrase can
    // only be there once; the second sentence has two of each.
    $sentences = ['Best best dog runs.', 'Dog dog best best jump.'];

    expect(matcher()->countOccurrences('best dog', $sentences, englishish()))->toBe(3);
});

it('counts nothing in a sentence that is missing a content word', function () {
    expect(matcher()->countOccurrences('best dog', ['Only the best here.'], englishish()))->toBe(0)
        ->and(matcher()->countOccurrences('best dog', [], englishish()))->toBe(0)
        ->and(matcher()->countOccurrences('', ['Only the best here.'], englishish()))->toBe(0);
});

it('counts a hyphenated occurrence through its parts', function () {
    expect(matcher()->countOccurrences('well known', ['A well-known well known fact.'], englishish()))->toBe(2);
});

it('finds an exact phrase regardless of case and quote style', function (string $haystack, string $keyphrase, bool $expected) {
    expect(matcher()->containsExactPhrase($haystack, $keyphrase))->toBe($expected);
})->with([
    'exact' => ['This is the best dog ever', 'best dog', true],
    'different case' => ['This is the Best Dog ever', 'best dog', true],
    'curly apostrophe in the text' => ['the dog’s life is good', "dog's life", true],
    'words out of order' => ['This is the dog best ever', 'best dog', false],
    'absent' => ['This is the best cat ever', 'best dog', false],
    'an empty keyphrase is never present' => ['This is the best dog', '', false],
]);

it('reports where the exact phrase starts', function () {
    expect(matcher()->exactPhrasePosition('This is the Best Dog ever', 'best dog'))->toBe(12)
        ->and(matcher()->exactPhrasePosition('This is the best cat', 'best dog'))->toBeNull()
        ->and(matcher()->exactPhrasePosition('best dog first', 'best dog'))->toBe(0);
});
