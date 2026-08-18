<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\KeyphraseLengthAssessment;
use TwillSeo\Analysis\Language\En\EnglishLanguagePack;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function assessKeyphraseLength(string $keyphrase): AssessmentResult
{
    $context = AnalysisFactory::context(
        Paper::builder()->keyword($keyphrase)->build(),
        new EnglishLanguagePack,
    );

    return (new KeyphraseLengthAssessment)->assess($context);
}

function keyphraseOfWords(int $words): string
{
    return implode(' ', array_map(fn (int $index) => 'word'.$index, range(1, $words)));
}

it('scores the keyphrase by how many content words it has', function (int $words, int $score, string $branch) {
    $result = assessKeyphraseLength(keyphraseOfWords($words));

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.keyphrase_length.{$branch}")
        ->and($result->messageParams)->toBe(['count' => $words, 'recommendedMax' => 4, 'acceptableMax' => 8]);
})->with([
    'a single word' => [1, 9, 'good'],
    'the top of the recommended range' => [4, 9, 'good'],
    'one word past it' => [5, 6, 'slightly_long'],
    'the top of the acceptable range' => [8, 6, 'slightly_long'],
    'one word past that' => [9, 3, 'too_long'],
    'far too long' => [15, 3, 'too_long'],
]);

it('vetoes a paper with no keyphrase at all', function (string $keyphrase) {
    $result = assessKeyphraseLength($keyphrase);

    // -999 is a veto, not a bad grade: nothing else in the SEO analysis means
    // much without a keyphrase to analyse against.
    expect($result->score)->toBe(-999)
        ->and($result->messageKey)->toBe('twill-seo::analysis.keyphrase_length.missing')
        ->and($result->messageParams['count'])->toBe(0);
})->with([
    'empty' => [''],
    'whitespace' => ['   '],
    'punctuation only' => ['---'],
]);

it('counts content words rather than words', function () {
    // "the", "of" and "for" are function words, leaving four that matter.
    expect(assessKeyphraseLength('the best of the dog food for puppies')->messageParams['count'])->toBe(4)
        ->and(assessKeyphraseLength('the best of the dog food for puppies')->score)->toBe(9);
});

it('falls back to every word when the keyphrase is all function words', function () {
    expect(assessKeyphraseLength('about us')->messageParams['count'])->toBe(2);
});

it('always applies and identifies itself as keyphraseLength', function () {
    $assessment = new KeyphraseLengthAssessment;

    expect($assessment->identifier())->toBe('keyphraseLength')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    expect(assessKeyphraseLength(keyphraseOfWords(9))->text)->toBe(
        'The keyphrase is 9 content words long. Nobody searches for a phrase that specific — cut it '
        .'back to 4 words or so.'
    );
});
