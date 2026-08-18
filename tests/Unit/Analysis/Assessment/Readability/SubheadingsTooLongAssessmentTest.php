<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Readability\SubheadingsTooLongAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

/**
 * A leading run of $sections[0] words, then a subheading before each further
 * entry. Every heading is one word long, so the totals stay easy to read.
 *
 * @param  list<int>  $sections
 */
function sectionsOfWords(array $sections, int $level = 2): string
{
    $html = '';

    foreach ($sections as $index => $words) {
        if ($index > 0) {
            $html .= "<h{$level}>Section</h{$level}>";
        }

        $html .= '<p>'.implode(' ', array_fill(0, $words, 'word')).'</p>';
    }

    return $html;
}

function assessSections(array $sections, int $level = 2): AssessmentResult
{
    return (new SubheadingsTooLongAssessment)->assess(AnalysisFactory::context(
        Paper::builder()->text(sectionsOfWords($sections, $level))->build()
    ));
}

it('has no complaint about a text short enough to read in one go', function (array $sections) {
    $result = assessSections($sections);

    expect($result->score)->toBe(9)
        ->and($result->messageKey)->toBe('twill-seo::analysis.subheadings_too_long.short_text');
})->with([
    'well under the limit' => [[100]],
    'one word under it' => [[299]],
    'exactly at it' => [[300]],
    // 297 words of body plus the two one-word headings.
    'split into sections but still short' => [[100, 99, 98]],
]);

it('asks a long text for subheadings', function (array $sections, int $words) {
    $result = assessSections($sections);

    expect($result->score)->toBe(2)
        ->and($result->messageKey)->toBe('twill-seo::analysis.subheadings_too_long.none')
        ->and($result->messageParams)->toBe(['words' => $words, 'max' => 300]);
})->with([
    'one word over the short text limit' => [[301], 301],
    'a properly long text' => [[400], 400],
]);

it('scores the longest section of a long text', function (array $sections, int $longest, int $score, string $branch) {
    $result = assessSections($sections);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.subheadings_too_long.{$branch}")
        ->and($result->messageParams)->toBe(['words' => $longest, 'max' => 300]);
})->with([
    'sections that all fit' => [[200, 200], 200, 9, 'good'],
    'the top of the good band' => [[300, 100], 300, 9, 'good'],
    'one word over' => [[301, 100], 301, 6, 'long_section'],
    'the top of the middle band' => [[350, 100], 350, 6, 'long_section'],
    'one word over that' => [[351, 100], 351, 3, 'too_long_section'],
    'the longest of several' => [[100, 360, 100], 360, 3, 'too_long_section'],
]);

it('counts the run before the first subheading as a section of its own', function () {
    // Deliberately stricter than judging only what follows a heading: an
    // unbroken 320 word opening is exactly as hard to read wherever it sits.
    $result = assessSections([320, 50]);

    expect($result->messageParams['words'])->toBe(320)
        ->and($result->messageKey)->toBe('twill-seo::analysis.subheadings_too_long.long_section');
});

it('splits a text into sections on h2 and on h3', function (int $level) {
    expect(assessSections([200, 200], $level)->messageParams['words'])->toBe(200);
})->with(['h2' => [2], 'h3' => [3]]);

it('does not split on h4, which leaves a long text with no subheadings at all', function () {
    $result = assessSections([200, 200], 4);

    expect($result->score)->toBe(2)
        ->and($result->messageKey)->toBe('twill-seo::analysis.subheadings_too_long.none');
});

it('needs a text to say anything', function (string $text, bool $applicable) {
    expect((new SubheadingsTooLongAssessment)->isApplicable(AnalysisFactory::context(Paper::builder()->text($text)->build())))
        ->toBe($applicable);
})->with([
    'some text' => ['<p>A paragraph.</p>', true],
    'no text' => ['', false],
]);

it('identifies itself as subheadingsTooLong', function () {
    expect((new SubheadingsTooLongAssessment)->identifier())->toBe('subheadingsTooLong');
});

it('says what is wrong in plain words', function () {
    expect(assessSections([360, 100])->text)->toBe(
        'The longest stretch between subheadings is 360 words, over the 300 a reader will follow. Add '
        .'a subheading where the subject turns.'
    );
});
