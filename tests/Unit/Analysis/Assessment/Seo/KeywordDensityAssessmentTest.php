<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\KeywordDensityAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

/**
 * Four hundred words of copy, of which $matches are the keyphrase, so the
 * density is exactly $matches / 4 percent. Ten words to a sentence and five
 * sentences to a paragraph, because the matcher counts per sentence.
 */
function densityText(int $matches): string
{
    $words = array_merge(array_fill(0, $matches, 'seo'), array_fill(0, 400 - $matches, 'word'));

    $sentences = array_map(
        fn (array $chunk) => ucfirst(implode(' ', $chunk)).'.',
        array_chunk($words, 10),
    );

    return implode('', array_map(
        fn (array $chunk) => '<p>'.implode(' ', $chunk).'</p>',
        array_chunk($sentences, 5),
    ));
}

function assessDensity(int $matches, string $keyphrase = 'seo'): AssessmentResult
{
    $paper = Paper::builder()->text(densityText($matches))->keyword($keyphrase)->build();

    return (new KeywordDensityAssessment)->assess(AnalysisFactory::context($paper));
}

it('scores the keyphrase density over a four hundred word text', function (int $matches, float $density, int $score) {
    $result = assessDensity($matches);

    expect($result->score)->toBe($score)
        ->and($result->messageParams['count'])->toBe($matches)
        ->and($result->messageParams['density'])->toBe(round($density, 1));
})->with([
    // matches | density | score
    'none at all' => [0, 0.0, 4],
    'under the minimum' => [1, 0.25, 4],
    'exactly the minimum' => [2, 0.50, 9],
    'comfortably inside' => [6, 1.50, 9],
    'exactly the maximum' => [12, 3.00, 9],
    'just over the maximum' => [13, 3.25, -10],
    'the top of the over band' => [16, 4.00, -10],
    'past the over band' => [17, 4.25, -50],
]);

it('names the branch it took', function (int $matches, string $branch) {
    expect(assessDensity($matches)->messageKey)->toBe("twill-seo::analysis.keyword_density.{$branch}");
})->with([
    'none' => [0, 'none'],
    'under' => [1, 'under'],
    'good' => [6, 'good'],
    'over' => [13, 'over'],
    'way over' => [17, 'way_over'],
]);

it('recommends a maximum from the length of the text and the keyphrase', function () {
    // 3 percent of 400 words, divided over a one word keyphrase:
    // floor(1200 / (100 * (0.7 + 1/3))) = floor(11.61) = 11.
    expect(assessDensity(6)->messageParams['recommendedMax'])->toBe(11);
});

it('never recommends fewer than two occurrences', function () {
    $paper = Paper::builder()->text('<p>A very short text about seo indeed.</p>')->keyword('seo')->build();

    expect((new KeywordDensityAssessment)->assess(AnalysisFactory::context($paper))->messageParams['recommendedMax'])
        ->toBe(2);
});

it('needs both a text and a keyphrase to say anything', function (string $text, string $keyword, bool $applicable) {
    $paper = Paper::builder()->text($text)->keyword($keyword)->build();

    expect((new KeywordDensityAssessment)->isApplicable(AnalysisFactory::context($paper)))->toBe($applicable);
})->with([
    'both present' => ['<p>Some words about seo.</p>', 'seo', true],
    'no keyphrase' => ['<p>Some words about seo.</p>', '', false],
    'no text' => ['', 'seo', false],
    'neither' => ['', '', false],
]);

it('identifies itself as keywordDensity', function () {
    expect((new KeywordDensityAssessment)->identifier())->toBe('keywordDensity');
});

it('says what the density is in plain words', function () {
    expect(assessDensity(17)->text)->toBe(
        'The keyphrase appears 17 times, a density of 4.3 percent. That is far too often — rewrite '
        .'most of them away, aiming for around 11 uses.'
    );
});
