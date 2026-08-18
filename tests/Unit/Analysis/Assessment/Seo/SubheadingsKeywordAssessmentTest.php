<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\SubheadingsKeywordAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

/**
 * $subheadings headings of $level, the first $matching of them carrying the
 * keyphrase, followed by enough body copy to reach $words in total. Every
 * heading is three words long, so the body makes up the rest exactly.
 */
function subheadingsHtml(int $subheadings, int $matching, int $words, int $level = 2): string
{
    $html = '';

    for ($index = 0; $index < $subheadings; $index++) {
        $text = $index < $matching ? 'Brewing green tea' : 'Notes on brewing';
        $html .= "<h{$level}>{$text}</h{$level}>";
    }

    $bodyWords = max(0, $words - 3 * $subheadings);

    return $html.($bodyWords > 0 ? '<p>'.implode(' ', array_fill(0, $bodyWords, 'word')).'</p>' : '');
}

function assessSubheadings(int $subheadings, int $matching, int $words, int $level = 2, string $keyphrase = 'green tea'): AssessmentResult
{
    $paper = Paper::builder()
        ->text(subheadingsHtml($subheadings, $matching, $words, $level))
        ->keyword($keyphrase)
        ->build();

    return (new SubheadingsKeywordAssessment)->assess(AnalysisFactory::context($paper));
}

it('scores the keyphrase in the subheadings', function (int $subheadings, int $matching, int $words, int $score) {
    expect(assessSubheadings($subheadings, $matching, $words)->score)->toBe($score);
})->with([
    // subheadings | matching | words | score
    'no subheadings and a long text' => [0, 0, 300, 2],
    'no subheadings and a short text' => [0, 0, 299, 9],
    'a single matching subheading' => [1, 1, 500, 9],
    'the lowest ratio that still counts' => [10, 3, 500, 9],
    'just below that ratio' => [10, 2, 500, 3],
    'the highest ratio that still counts' => [4, 3, 500, 9],
    'just above that ratio' => [10, 8, 500, 3],
    'subheadings that never mention it' => [4, 0, 500, 3],
]);

it('says nothing useful without a keyphrase', function (int $subheadings, int $words) {
    expect(assessSubheadings($subheadings, $subheadings, $words, keyphrase: '')->score)->toBe(1);
})->with([
    'with subheadings' => [4, 500],
    'without subheadings' => [0, 299],
    'with a long text' => [4, 1000],
]);

it('counts only h2 and h3 as subheadings', function () {
    // Three H4s and a short text: the H4s are not subheadings, so this is the
    // "no subheadings, short text" case rather than a ratio of zero.
    expect(assessSubheadings(3, 0, 299, level: 4)->score)->toBe(9)
        ->and(assessSubheadings(3, 0, 500, level: 4)->score)->toBe(2)
        // H3 does count, so the same shape built from H3s is judged on its
        // ratio instead of reported as having no subheadings at all.
        ->and(assessSubheadings(4, 2, 500, level: 3)->score)->toBe(9)
        ->and(assessSubheadings(4, 0, 500, level: 3)->score)->toBe(3);
});

it('names the branch it took', function (int $subheadings, int $matching, int $words, string $branch) {
    expect(assessSubheadings($subheadings, $matching, $words)->messageKey)
        ->toBe("twill-seo::analysis.subheadings_keyword.{$branch}");
})->with([
    'none, long text' => [0, 0, 300, 'none_long_text'],
    'none, short text' => [0, 0, 299, 'none_short_text'],
    'good' => [4, 2, 500, 'good'],
    'too few' => [10, 2, 500, 'too_few'],
    'too many' => [10, 8, 500, 'too_many'],
    'never mentioned' => [4, 0, 500, 'none'],
]);

it('reports how many of the subheadings matched', function () {
    expect(assessSubheadings(10, 3, 500)->messageParams)->toBe(['count' => 3, 'total' => 10]);
});

it('needs a text as well as a keyphrase', function () {
    $paper = Paper::builder()->text('')->keyword('green tea')->build();

    expect((new SubheadingsKeywordAssessment)->assess(AnalysisFactory::context($paper))->score)->toBe(1);
});

it('always applies and identifies itself as subheadingsKeyword', function () {
    $assessment = new SubheadingsKeywordAssessment;

    expect($assessment->identifier())->toBe('subheadingsKeyword')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    expect(assessSubheadings(10, 2, 500)->text)->toBe(
        'Only 2 of the 10 subheadings contain the keyphrase. Work it into a few more of them so the '
        .'structure of the page reflects its subject.'
    );
});
