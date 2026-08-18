<?php

namespace TwillSeo\Tests\Unit\Analysis\Language;

use TwillSeo\Analysis\Language\WordTokenizer;

it('extracts words', function (string $text, array $expected) {
    expect((new WordTokenizer)->tokenize($text))->toBe($expected);
})->with([
    'plain words' => ['Hello world', ['Hello', 'world']],
    'punctuation is not part of a word' => ['Hello, world! Again?', ['Hello', 'world', 'Again']],
    'an apostrophe inside a word is kept' => ["don't stop", ["don't", 'stop']],
    'a possessive plural is kept' => ["auto's rijden", ["auto's", 'rijden']],
    'a curly apostrophe folds onto the straight one' => ['don’t stop', ["don't", 'stop']],
    'a hyphenated word stays whole' => ['well-known e-mail', ['well-known', 'e-mail']],
    'a triple hyphenated word stays whole' => ['state-of-the-art', ['state-of-the-art']],
    'an em dash separates words' => ['one — two', ['one', 'two']],
    'digits count as words' => ['grew 15 percent in 2024', ['grew', '15', 'percent', 'in', '2024']],
    'accents and eszett survive' => ['café münchen straße', ['café', 'münchen', 'straße']],
    'a trailing apostrophe is dropped' => ["James' book", ['James', 'book']],
    'a non breaking space separates words' => ["one\u{00A0}two", ['one', 'two']],
    'nothing at all' => ['', []],
    'only punctuation' => ['--- !!! ...', []],
]);

it('counts the words of a text', function () {
    expect((new WordTokenizer)->count('One two three-four five'))->toBe(4)
        ->and((new WordTokenizer)->count('   '))->toBe(0);
});
