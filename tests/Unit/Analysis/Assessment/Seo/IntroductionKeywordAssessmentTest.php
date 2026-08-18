<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\IntroductionKeywordAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function assessIntroduction(string $html, string $keyphrase = 'green tea'): AssessmentResult
{
    $paper = Paper::builder()->text($html)->keyword($keyphrase)->build();

    return (new IntroductionKeywordAssessment)->assess(AnalysisFactory::context($paper));
}

it('scores where the keyphrase turns up in the first paragraph', function (string $html, int $score, string $branch) {
    $result = assessIntroduction($html);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.introduction_keyword.{$branch}");
})->with([
    'all of it in one sentence' => [
        '<p>Brewing green tea well takes practice. It is worth it.</p><p>More later.</p>',
        9, 'good',
    ],
    'in the very first sentence' => [
        '<p>Green tea is the subject here.</p>',
        9, 'good',
    ],
    'spread over two sentences of the first paragraph' => [
        '<p>This page is about tea. The green kind, specifically.</p>',
        6, 'spread',
    ],
    'only in a later paragraph' => [
        '<p>This page is about drinks.</p><p>Green tea in particular.</p>',
        3, 'none',
    ],
    'nowhere at all' => [
        '<p>This page is about coffee.</p>',
        3, 'none',
    ],
    'only one of the words' => [
        '<p>This page is about tea. Black tea, mostly.</p>',
        3, 'none',
    ],
]);

it('reads a text with no paragraph at all as not mentioning it', function () {
    // A heading is not a paragraph, so there is no introduction to look at.
    expect(assessIntroduction('<h2>Green tea</h2>')->score)->toBe(3);
});

it('needs both a text and a keyphrase to say anything', function (string $text, string $keyword, bool $applicable) {
    $paper = Paper::builder()->text($text)->keyword($keyword)->build();

    expect((new IntroductionKeywordAssessment)->isApplicable(AnalysisFactory::context($paper)))->toBe($applicable);
})->with([
    'both present' => ['<p>Green tea.</p>', 'green tea', true],
    'no keyphrase' => ['<p>Green tea.</p>', '', false],
    'no text' => ['', 'green tea', false],
]);

it('identifies itself as introductionKeyword', function () {
    expect((new IntroductionKeywordAssessment)->identifier())->toBe('introductionKeyword');
});

it('says what is wrong in plain words', function () {
    expect(assessIntroduction('<p>This page is about coffee.</p>')->text)->toBe(
        'The keyphrase does not appear in the first paragraph. Say what the page is about in the '
        .'opening lines, in the reader\'s words.'
    );
});
