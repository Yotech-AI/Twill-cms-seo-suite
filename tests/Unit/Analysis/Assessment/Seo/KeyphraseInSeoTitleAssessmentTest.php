<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\KeyphraseInSeoTitleAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function assessTitleKeyphrase(string $title, string $keyphrase = 'green tea'): AssessmentResult
{
    $paper = Paper::builder()->title($title)->keyword($keyphrase)->build();

    return (new KeyphraseInSeoTitleAssessment)->assess(AnalysisFactory::context($paper));
}

it('scores where the keyphrase sits in the seo title', function (string $title, int $score, string $branch) {
    $result = assessTitleKeyphrase($title);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.keyphrase_in_seo_title.{$branch}");
})->with([
    'at the very start' => ['Green tea: a beginner\'s guide', 9, 'good_start'],
    'at the start in another case' => ['GREEN TEA for beginners', 9, 'good_start'],
    'one word in' => ['The green tea guide', 6, 'good_not_start'],
    'at the end' => ['A beginner\'s guide to green tea', 6, 'good_not_start'],
    'the words present but apart' => ['Tea, and why the green kind wins', 6, 'all_words'],
    'only one of the words' => ['A guide to tea', 2, 'not_found'],
    'nothing of it' => ['A guide to coffee', 2, 'not_found'],
]);

it('needs a title and a keyphrase before it can look', function (string $title, string $keyphrase) {
    $result = assessTitleKeyphrase($title, $keyphrase);

    expect($result->score)->toBe(2)
        ->and($result->messageKey)->toBe('twill-seo::analysis.keyphrase_in_seo_title.missing_input');
})->with([
    'no title' => ['', 'green tea'],
    'whitespace title' => ['   ', 'green tea'],
    'no keyphrase' => ['Green tea guide', ''],
    'neither' => ['', ''],
]);

it('ignores leading whitespace when deciding whether it starts the title', function () {
    expect(assessTitleKeyphrase('  Green tea guide')->messageKey)
        ->toBe('twill-seo::analysis.keyphrase_in_seo_title.good_start');
});

it('always applies and identifies itself as keyphraseInSEOTitle', function () {
    $assessment = new KeyphraseInSeoTitleAssessment;

    expect($assessment->identifier())->toBe('keyphraseInSEOTitle')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    expect(assessTitleKeyphrase('A guide to coffee')->text)->toBe(
        'The keyphrase does not appear in the SEO title. Put it in, as near the front as the wording '
        .'allows.'
    );
});
