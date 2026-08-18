<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Readability\TransitionWordsAssessment;
use TwillSeo\Analysis\Language\DefaultLanguagePack;
use TwillSeo\Analysis\Language\En\EnglishLanguagePack;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

/**
 * $total sentences of 21 words each — comfortably past the 200 word floor —
 * of which $withTransition open with a transition word.
 */
function transitionText(int $total, int $withTransition): string
{
    $sentences = [];

    for ($index = 0; $index < $total; $index++) {
        $words = array_fill(0, 20, 'word');
        array_unshift($words, $index < $withTransition ? 'However' : 'Word');
        $sentences[] = implode(' ', $words).'.';
    }

    return '<p>'.implode(' ', $sentences).'</p>';
}

function assessTransitions(string $html): AssessmentResult
{
    $context = AnalysisFactory::context(Paper::builder()->text($html)->build(), new EnglishLanguagePack);

    return (new TransitionWordsAssessment)->assess($context);
}

it('scores the share of sentences that carry a transition', function (int $total, int $withTransition, float $percentage, int $score, string $branch) {
    $result = assessTransitions(transitionText($total, $withTransition));

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.transition_words.{$branch}")
        ->and($result->messageParams)->toBe(['percentage' => $percentage]);
})->with([
    'none at all' => [20, 0, 0.0, 3, 'few'],
    'just under the first band' => [20, 3, 15.0, 3, 'few'],
    'the bottom of the middle band' => [20, 4, 20.0, 6, 'some'],
    'the top of the middle band' => [20, 5, 25.0, 6, 'some'],
    'the bottom of the good band' => [20, 6, 30.0, 9, 'good'],
    'every sentence' => [20, 20, 100.0, 9, 'good'],
]);

it('asks nothing of a text too short to need signposting', function (int $words) {
    $result = assessTransitions('<p>'.implode(' ', array_fill(0, $words, 'word')).'.</p>');

    expect($result->score)->toBe(9)
        ->and($result->messageKey)->toBe('twill-seo::analysis.transition_words.short_text');
})->with([
    'a very short text' => [20],
    'one word under the floor' => [199],
]);

it('starts judging at two hundred words', function () {
    // The same text with no transitions at all: short enough passes, one word
    // longer fails.
    expect(assessTransitions('<p>'.implode(' ', array_fill(0, 199, 'word')).'.</p>')->score)->toBe(9)
        ->and(assessTransitions('<p>'.implode(' ', array_fill(0, 200, 'word')).'.</p>')->score)->toBe(3);
});

it('recognises a multi word transition and a two part one', function (string $opening) {
    $sentences = [$opening];

    for ($index = 0; $index < 2; $index++) {
        $sentences[] = implode(' ', array_fill(0, 100, 'word')).'.';
    }

    // One transition in three sentences is 33 percent, which passes.
    expect(assessTransitions('<p>'.implode(' ', $sentences).'</p>')->score)->toBe(9);
})->with([
    'a phrase' => ['In addition the price went up.'],
    'a two part pair' => ['Both the price and the delivery time went up.'],
]);

it('needs a language that has a transition word list', function () {
    $paper = Paper::builder()->text('<p>Some text.</p>')->build();

    expect((new TransitionWordsAssessment)->isApplicable(AnalysisFactory::context($paper, new EnglishLanguagePack)))->toBeTrue()
        ->and((new TransitionWordsAssessment)->isApplicable(AnalysisFactory::context($paper, new DefaultLanguagePack)))->toBeFalse();
});

it('needs a text to say anything', function () {
    $context = AnalysisFactory::context(Paper::builder()->text('')->build(), new EnglishLanguagePack);

    expect((new TransitionWordsAssessment)->isApplicable($context))->toBeFalse();
});

it('identifies itself as transitionWords', function () {
    expect((new TransitionWordsAssessment)->identifier())->toBe('transitionWords');
});

it('says what is wrong in plain words', function () {
    expect(assessTransitions(transitionText(20, 2))->text)->toBe(
        'Only 10 percent of the sentences use a transition word. Signpost how one sentence follows '
        .'from the last, so the text reads as an argument rather than a list.'
    );
});
