<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Config\SentenceLengthThresholds;
use TwillSeo\Analysis\Assessment\Readability\SentenceLengthAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

/**
 * $total sentences, $long of which run to 25 words and the rest to 5, so the
 * share of over-long sentences is exactly $long / $total.
 */
function sentencesOfLength(int $total, int $long): string
{
    $sentences = [];

    for ($index = 0; $index < $total; $index++) {
        $words = array_fill(0, $index < $long ? 25 : 5, 'word');
        $words[0] = 'Word';
        $sentences[] = implode(' ', $words).'.';
    }

    return '<p>'.implode(' ', $sentences).'</p>';
}

function assessSentenceLength(int $total, int $long, ?SentenceLengthThresholds $thresholds = null): AssessmentResult
{
    $assessment = new SentenceLengthAssessment($thresholds ?? SentenceLengthThresholds::default());

    return $assessment->assess(AnalysisFactory::context(
        Paper::builder()->text(sentencesOfLength($total, $long))->build()
    ));
}

it('scores the share of sentences that run too long', function (int $total, int $long, float $percentage, int $score, string $branch) {
    $result = assessSentenceLength($total, $long);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.sentence_length.{$branch}")
        ->and($result->messageParams)->toBe(['percentage' => $percentage, 'limit' => 20]);
})->with([
    'none at all' => [10, 0, 0.0, 9, 'good'],
    'the top of the good band' => [4, 1, 25.0, 9, 'good'],
    'a hair over it' => [100, 26, 26.0, 6, 'some_long'],
    'the top of the middle band' => [100, 29, 29.0, 6, 'some_long'],
    'the bottom of the bad band' => [10, 3, 30.0, 3, 'too_many_long'],
    'every sentence' => [10, 10, 100.0, 3, 'too_many_long'],
]);

it('holds cornerstone content to a tighter bar', function (int $total, int $long, int $score, string $branch) {
    $result = assessSentenceLength($total, $long, SentenceLengthThresholds::cornerstone());

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.sentence_length.{$branch}");
})->with([
    'the top of the good band' => [5, 1, 9, 'good'],
    'a hair over it' => [100, 21, 6, 'some_long'],
    'the top of the middle band' => [4, 1, 6, 'some_long'],
    'past the middle band' => [100, 26, 3, 'too_many_long'],
    // A share that is merely acceptable on the normal scale fails here.
    'a normal pass is a cornerstone fail' => [4, 1, 6, 'some_long'],
]);

it('counts a sentence of exactly the limit as short enough', function () {
    $twenty = implode(' ', array_fill(0, 20, 'word'));
    $twentyOne = implode(' ', array_fill(0, 21, 'word'));

    $assess = fn (string $text) => (new SentenceLengthAssessment(SentenceLengthThresholds::default()))
        ->assess(AnalysisFactory::context(Paper::builder()->text("<p>{$text}.</p>")->build()));

    expect($assess($twenty)->messageParams['percentage'])->toBe(0.0)
        ->and($assess($twentyOne)->messageParams['percentage'])->toBe(100.0);
});

it('needs a text to say anything', function (string $text, bool $applicable) {
    $assessment = new SentenceLengthAssessment(SentenceLengthThresholds::default());

    expect($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->text($text)->build())))
        ->toBe($applicable);
})->with([
    'some text' => ['<p>A sentence.</p>', true],
    'no text' => ['', false],
]);

it('identifies itself as sentenceLength', function () {
    expect((new SentenceLengthAssessment(SentenceLengthThresholds::default()))->identifier())->toBe('sentenceLength');
});

it('says what is wrong in plain words', function () {
    expect(assessSentenceLength(10, 5)->text)->toBe(
        '50 percent of the sentences are longer than 20 words. Split the longest ones so a reader can '
        .'follow them in one pass.'
    );
});
