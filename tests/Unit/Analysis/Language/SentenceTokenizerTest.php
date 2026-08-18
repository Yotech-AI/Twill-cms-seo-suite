<?php

namespace TwillSeo\Tests\Unit\Analysis\Language;

use TwillSeo\Analysis\Language\SentenceTokenizer;

it('splits text into sentences', function (string $text, array $expected) {
    expect((new SentenceTokenizer)->tokenize($text))->toBe($expected);
})->with([
    'a full stop between two sentences' => [
        'First sentence. Second sentence.',
        ['First sentence.', 'Second sentence.'],
    ],
    'a question and an exclamation also end a sentence' => [
        'Is it? Yes! Good.',
        ['Is it?', 'Yes!', 'Good.'],
    ],
    'a sentence without a closing terminator still counts' => [
        'No full stop here',
        ['No full stop here'],
    ],
    'a decimal number is not a sentence end' => [
        'It costs 3.5 euros in total. Really.',
        ['It costs 3.5 euros in total.', 'Really.'],
    ],
    'a single letter initial is not a sentence end' => [
        'J. Doe wrote this. Then he left.',
        ['J. Doe wrote this.', 'Then he left.'],
    ],
    'two initials in a row stay in one sentence' => [
        'J. R. Tolkien wrote it.',
        ['J. R. Tolkien wrote it.'],
    ],
    'an ellipsis run is one terminator' => [
        'He waited... Then he left.',
        ['He waited...', 'Then he left.'],
    ],
    'an ellipsis followed by lowercase does not split' => [
        'He waited... then he left.',
        ['He waited... then he left.'],
    ],
    'a unicode ellipsis ends a sentence' => [
        'He waited… Then he left.',
        ['He waited…', 'Then he left.'],
    ],
    'stacked terminators are one terminator' => [
        'Really?! Yes.',
        ['Really?!', 'Yes.'],
    ],
    'a terminator inside a closing quote belongs to the sentence' => [
        'He said "Stop." Then he left.',
        ['He said "Stop."', 'Then he left.'],
    ],
    'a terminator inside a closing paren belongs to the sentence' => [
        'See the note (it matters.) Then continue.',
        ['See the note (it matters.)', 'Then continue.'],
    ],
    'a terminator with no following space does not split' => [
        'Read section2.Now continue',
        ['Read section2.Now continue'],
    ],
    'extra whitespace between sentences is dropped' => [
        "One.   \n  Two.",
        ['One.', 'Two.'],
    ],
    'a digit can open the next sentence' => [
        'Count them. 12 remain.',
        ['Count them.', '12 remain.'],
    ],
    'nothing at all' => ['', []],
    'only whitespace' => ["   \n ", []],
]);

it('keeps a configured abbreviation inside its sentence', function () {
    $tokenizer = new SentenceTokenizer(['dr', 'nr']);

    expect($tokenizer->tokenize('Dr. Smith arrived. He waited.'))
        ->toBe(['Dr. Smith arrived.', 'He waited.']);
});

it('splits on an abbreviation it was not told about', function () {
    // The generic tokenizer has no abbreviation list of its own; language
    // packs supply one. Without it the split is wrong, which is exactly why
    // the constructor takes the list.
    expect((new SentenceTokenizer)->tokenize('Dr. Smith arrived.'))
        ->toBe(['Dr.', 'Smith arrived.']);
});
