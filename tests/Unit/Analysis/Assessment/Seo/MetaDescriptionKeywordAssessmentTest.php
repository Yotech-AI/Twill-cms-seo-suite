<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\MetaDescriptionKeywordAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function assessDescriptionKeyword(string $description, string $keyphrase = 'green tea'): AssessmentResult
{
    $paper = Paper::builder()->description($description)->keyword($keyphrase)->build();

    return (new MetaDescriptionKeywordAssessment)->assess(AnalysisFactory::context($paper));
}

it('scores how often the keyphrase appears in the meta description', function (string $description, int $score, string $branch, int $count) {
    $result = assessDescriptionKeyword($description);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.meta_description_keyword.{$branch}")
        ->and($result->messageParams)->toBe(['count' => $count]);
})->with([
    'once' => ['Everything about green tea, from leaf to cup.', 9, 'good', 1],
    'twice' => ['Green tea explained. Brewing green tea, step by step.', 9, 'good', 2],
    'three times' => ['Green tea, green tea and more green tea.', 3, 'too_many', 3],
    'not at all' => ['Everything about coffee, from bean to cup.', 3, 'none', 0],
    'only part of it' => ['Everything about tea, from leaf to cup.', 3, 'none', 0],
]);

it('asks for a description before it asks for a keyphrase in one', function (string $description) {
    $result = assessDescriptionKeyword($description);

    expect($result->score)->toBe(3)
        ->and($result->messageKey)->toBe('twill-seo::analysis.meta_description_keyword.missing_description');
})->with([
    'empty' => [''],
    'whitespace' => ['   '],
]);

it('needs a keyphrase to say anything', function (string $keyword, bool $applicable) {
    $paper = Paper::builder()->description('A description about green tea.')->keyword($keyword)->build();

    expect((new MetaDescriptionKeywordAssessment)->isApplicable(AnalysisFactory::context($paper)))->toBe($applicable);
})->with([
    'a keyphrase' => ['green tea', true],
    'no keyphrase' => ['', false],
]);

it('identifies itself as metaDescriptionKeyword', function () {
    expect((new MetaDescriptionKeywordAssessment)->identifier())->toBe('metaDescriptionKeyword');
});

it('says what is wrong in plain words', function () {
    expect(assessDescriptionKeyword('Green tea, green tea and more green tea.')->text)->toBe(
        'The keyphrase appears 3 times in the meta description. Once is enough — use the space to '
        .'tell the reader why to click.'
    );
});
