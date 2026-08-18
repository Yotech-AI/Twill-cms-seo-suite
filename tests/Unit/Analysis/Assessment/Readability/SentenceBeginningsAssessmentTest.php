<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Readability\SentenceBeginningsAssessment;
use TwillSeo\Analysis\Language\DefaultLanguagePack;
use TwillSeo\Analysis\Language\En\EnglishLanguagePack;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function assessBeginnings(string $text): AssessmentResult
{
    $context = AnalysisFactory::context(
        Paper::builder()->text("<p>{$text}</p>")->build(),
        new EnglishLanguagePack,
    );

    return (new SentenceBeginningsAssessment)->assess($context);
}

it('scores a run of sentences that all start the same way', function (string $text, int $score, string $branch) {
    $result = assessBeginnings($text);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.sentence_beginnings.{$branch}");
})->with([
    'every sentence starts differently' => [
        'The cat sat down. The dog barked loudly. Birds sang all morning.',
        9, 'varied',
    ],
    'two in a row is a coincidence' => [
        'The cat sat down. The cat ate well. Birds sang all morning.',
        9, 'varied',
    ],
    'three in a row is a habit' => [
        'The cat sat down. The cat ate well. The cat slept all day.',
        3, 'repeated',
    ],
    'a run that starts partway in' => [
        'Birds sang all morning. The cat sat down. The cat ate well. The cat slept.',
        3, 'repeated',
    ],
    'a run broken by one different sentence' => [
        'The cat sat. The cat ate. Birds sang. The cat slept. The cat woke.',
        9, 'varied',
    ],
    'with no determiner in front at all' => [
        'Cats sleep often. Cats eat often. Cats wake late.',
        3, 'repeated',
    ],
]);

it('steps over a determiner to compare the word that matters', function () {
    // Same opening word, different subject: not a repetition.
    expect(assessBeginnings('The cat sat. The dog barked. The bird sang.')->score)->toBe(9)
        // Different determiner, same subject: still a repetition.
        ->and(assessBeginnings('The cat sat. My cat ate. Their cat slept.')->score)->toBe(3);
});

it('compares without regard to case', function () {
    // A lowercase word cannot open a sentence as far as the tokenizer is
    // concerned, so the case that matters here is a shouted opening word.
    expect(assessBeginnings('CATS sleep well. Cats eat often. Cats wake late.')->score)->toBe(3);
});

it('reports the word and the length of the longest run', function () {
    $result = assessBeginnings('The cat sat. The cat ate. The cat slept. The cat woke. Birds sang.');

    expect($result->messageParams)->toBe(['word' => 'cat', 'count' => 4]);
});

it('needs a language that knows which words to step over', function () {
    $english = AnalysisFactory::context(Paper::builder()->text('<p>A text.</p>')->build(), new EnglishLanguagePack);
    $generic = AnalysisFactory::context(Paper::builder()->text('<p>A text.</p>')->build(), new DefaultLanguagePack);

    expect((new SentenceBeginningsAssessment)->isApplicable($english))->toBeTrue()
        ->and((new SentenceBeginningsAssessment)->isApplicable($generic))->toBeFalse();
});

it('needs a text to say anything', function () {
    $context = AnalysisFactory::context(Paper::builder()->text('')->build(), new EnglishLanguagePack);

    expect((new SentenceBeginningsAssessment)->isApplicable($context))->toBeFalse();
});

it('identifies itself as sentenceBeginnings', function () {
    expect((new SentenceBeginningsAssessment)->identifier())->toBe('sentenceBeginnings');
});

it('says what is wrong in plain words', function () {
    expect(assessBeginnings('The cat sat. The cat ate. The cat slept.')->text)->toBe(
        '3 sentences in a row start with "cat". Vary the openings so the text does not read as a list.'
    );
});
