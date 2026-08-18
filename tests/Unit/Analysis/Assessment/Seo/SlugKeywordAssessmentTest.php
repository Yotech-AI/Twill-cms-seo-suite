<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\SlugKeywordAssessment;
use TwillSeo\Analysis\Language\En\EnglishLanguagePack;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function assessSlug(string $slug, string $keyphrase = 'green tea'): AssessmentResult
{
    $context = AnalysisFactory::context(
        Paper::builder()->slug($slug)->keyword($keyphrase)->build(),
        new EnglishLanguagePack,
    );

    return (new SlugKeywordAssessment)->assess($context);
}

it('wants every word of a short keyphrase in the slug', function (string $slug, int $score, string $branch, int $count) {
    $result = assessSlug($slug);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.slug_keyword.{$branch}")
        ->and($result->messageParams)->toBe(['count' => $count, 'total' => 2]);
})->with([
    'both words' => ['green-tea-guide', 9, 'good', 2],
    'both words with underscores' => ['green_tea_guide', 9, 'good', 2],
    'both words in another order' => ['tea-green', 9, 'good', 2],
    'one of the two' => ['tea-guide', 6, 'some', 1],
    'neither' => ['a-coffee-guide', 6, 'some', 0],
]);

it('wants more than half of a longer keyphrase in the slug', function (string $slug, int $score, int $count) {
    // "best green tea leaves" is four content words.
    $result = assessSlug($slug, 'best green tea leaves');

    expect($result->score)->toBe($score)
        ->and($result->messageParams)->toBe(['count' => $count, 'total' => 4]);
})->with([
    'all four' => ['best-green-tea-leaves', 9, 4],
    'three of four' => ['green-tea-leaves', 9, 3],
    // Exactly half is not more than half.
    'two of four' => ['green-tea', 6, 2],
    'one of four' => ['tea-time', 6, 1],
]);

it('counts three content words as the longer kind', function (string $slug, int $score, int $count) {
    $result = assessSlug($slug, 'green tea leaves');

    expect($result->score)->toBe($score)
        ->and($result->messageParams)->toBe(['count' => $count, 'total' => 3]);
})->with([
    'two of three is more than half' => ['green-tea', 9, 2],
    'one of three is not' => ['tea-guide', 6, 1],
]);

it('needs a slug and a keyphrase before it can look', function (string $slug, string $keyphrase) {
    $result = assessSlug($slug, $keyphrase);

    expect($result->score)->toBe(3)
        ->and($result->messageKey)->toBe('twill-seo::analysis.slug_keyword.missing_input');
})->with([
    'no slug' => ['', 'green tea'],
    'whitespace slug' => ['   ', 'green tea'],
    'no keyphrase' => ['green-tea', ''],
]);

it('matches a slug word only as a whole word', function () {
    // "tealeaf" is not "tea", however much of it looks like it.
    expect(assessSlug('tealeaf-shop')->messageParams['count'])->toBe(0);
});

it('always applies and identifies itself as slugKeyword', function () {
    $assessment = new SlugKeywordAssessment;

    expect($assessment->identifier())->toBe('slugKeyword')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    expect(assessSlug('tea-guide')->text)->toBe(
        'The slug contains 1 of the 2 keyphrase words. Work the rest in, as long as the URL stays '
        .'readable.'
    );
});
