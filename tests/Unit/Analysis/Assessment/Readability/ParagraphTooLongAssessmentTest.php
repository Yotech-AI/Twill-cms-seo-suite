<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Readability\ParagraphTooLongAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

/**
 * One paragraph per entry, each of that many words.
 *
 * @param  list<int>  $paragraphWords
 */
function paragraphsOfWords(array $paragraphWords): string
{
    return implode('', array_map(
        fn (int $words) => '<p>'.implode(' ', array_fill(0, $words, 'word')).'</p>',
        $paragraphWords,
    ));
}

function assessParagraphLength(array $paragraphWords): AssessmentResult
{
    return (new ParagraphTooLongAssessment)->assess(AnalysisFactory::context(
        Paper::builder()->text(paragraphsOfWords($paragraphWords))->build()
    ));
}

it('scores the longest paragraph', function (array $paragraphs, int $longest, int $score, string $branch) {
    $result = assessParagraphLength($paragraphs);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.paragraph_too_long.{$branch}")
        ->and($result->messageParams)->toBe(['words' => $longest, 'max' => 150]);
})->with([
    'a short one' => [[40], 40, 9, 'good'],
    'the top of the good band' => [[150], 150, 9, 'good'],
    'one word over' => [[151], 151, 6, 'slightly_long'],
    'the top of the middle band' => [[200], 200, 6, 'slightly_long'],
    'one word over that' => [[201], 201, 3, 'too_long'],
    'the longest of several' => [[40, 210, 90], 210, 3, 'too_long'],
    'several that are all fine' => [[40, 90, 150], 150, 9, 'good'],
]);

it('has no complaint about a text with no paragraphs', function () {
    $result = (new ParagraphTooLongAssessment)->assess(AnalysisFactory::context(
        Paper::builder()->text('<h2>A heading and nothing else</h2>')->build()
    ));

    expect($result->score)->toBe(9)
        ->and($result->messageParams)->toBe(['words' => 0, 'max' => 150]);
});

it('needs a text to say anything', function (string $text, bool $applicable) {
    expect((new ParagraphTooLongAssessment)->isApplicable(AnalysisFactory::context(Paper::builder()->text($text)->build())))
        ->toBe($applicable);
})->with([
    'some text' => ['<p>A paragraph.</p>', true],
    'no text' => ['', false],
]);

it('identifies itself as paragraphTooLong', function () {
    expect((new ParagraphTooLongAssessment)->identifier())->toBe('paragraphTooLong');
});

it('says what is wrong in plain words', function () {
    expect(assessParagraphLength([210])->text)->toBe(
        'The longest paragraph is 210 words, well over the 150 that reads comfortably. Break it up at '
        .'the turns in the argument.'
    );
});
