<?php

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\MetaDescriptionLengthAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function assessDescriptionOfLength(int $length): AssessmentResult
{
    $assessment = new MetaDescriptionLengthAssessment;

    return $assessment->assess(AnalysisFactory::context(
        Paper::builder()->description(str_repeat('a', $length))->build()
    ));
}

it('scores the meta description by length', function (int $length, int $score, string $branch) {
    $result = assessDescriptionOfLength($length);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.meta_description_length.{$branch}")
        ->and($result->messageParams)->toBe(['length' => $length, 'max' => 156]);
})->with([
    'nothing at all' => [0, 1, 'missing'],
    'one character' => [1, 6, 'too_short'],
    'short upper bound' => [120, 6, 'too_short'],
    'good lower bound' => [121, 9, 'good'],
    'good upper bound' => [156, 9, 'good'],
    'long lower bound' => [157, 6, 'too_long'],
    'far too long' => [400, 6, 'too_long'],
]);

it('counts characters and not bytes', function () {
    $assessment = new MetaDescriptionLengthAssessment;

    // 130 accented characters: 260 bytes, but a search engine counts 130.
    $result = $assessment->assess(AnalysisFactory::context(
        Paper::builder()->description(str_repeat('é', 130))->build()
    ));

    expect($result->messageParams['length'])->toBe(130)
        ->and($result->score)->toBe(9);
});

it('always applies, description or not', function () {
    $assessment = new MetaDescriptionLengthAssessment;

    expect($assessment->identifier())->toBe('metaDescriptionLength')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    expect(assessDescriptionOfLength(0)->text)
        ->toBe('No meta description is set. Write one of up to 156 characters so search engines show your summary instead of guessing at one.');
});
