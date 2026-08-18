<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\ImageKeyphraseAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

/**
 * $total images, of which $matching describe the keyphrase and the rest carry
 * some other alt text. $withoutAlt of them have no alt attribute at all.
 */
function imagesHtml(int $total, int $matching, int $withoutAlt = 0): string
{
    $html = '';

    for ($index = 0; $index < $total; $index++) {
        if ($index >= $total - $withoutAlt) {
            $html .= '<img src="/'.$index.'.png">';

            continue;
        }

        $alt = $index < $matching ? 'A cup of green tea' : 'A coffee pot';
        $html .= '<img src="/'.$index.'.png" alt="'.$alt.'">';
    }

    return '<p>'.$html.'</p>';
}

function assessImageKeyphrase(int $total, int $matching, int $withoutAlt = 0, string $keyphrase = 'green tea'): AssessmentResult
{
    $paper = Paper::builder()->text(imagesHtml($total, $matching, $withoutAlt))->keyword($keyphrase)->build();

    return (new ImageKeyphraseAssessment)->assess(AnalysisFactory::context($paper));
}

it('scores the alt text of a handful of images on whether any describes the keyphrase', function (int $total, int $matching, int $score, string $branch) {
    $result = assessImageKeyphrase($total, $matching);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.image_keyphrase.{$branch}");
})->with([
    // Under five images one match is enough — there is no ratio to speak of.
    'one image that matches' => [1, 1, 9, 'good'],
    'four images, one matches' => [4, 1, 9, 'good'],
    'four images, all match' => [4, 4, 9, 'good'],
    'four images, none match' => [4, 0, 6, 'none_match'],
]);

it('scores a gallery on the share of alt texts that mention the keyphrase', function (int $total, int $matching, int $score, string $branch) {
    $result = assessImageKeyphrase($total, $matching);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.image_keyphrase.{$branch}");
})->with([
    'five images, two match' => [5, 2, 9, 'good'],
    'ten images, three match' => [10, 3, 9, 'good'],
    'ten images, two match' => [10, 2, 6, 'too_few'],
    'eight images, six match' => [8, 6, 9, 'good'],
    'ten images, eight match' => [10, 8, 6, 'too_many'],
    'ten images, none match' => [10, 0, 6, 'none_match'],
]);

it('asks for alt text at all before it asks for the keyphrase in it', function () {
    $result = assessImageKeyphrase(3, 0, withoutAlt: 3);

    expect($result->score)->toBe(6)
        ->and($result->messageKey)->toBe('twill-seo::analysis.image_keyphrase.no_alts');
});

it('judges only the images that have alt text', function () {
    // Two of the three images have no alt at all; the one that does matches, so
    // this is a small set with a match rather than a set with no alt text.
    expect(assessImageKeyphrase(3, 1, withoutAlt: 2)->messageKey)
        ->toBe('twill-seo::analysis.image_keyphrase.good');
});

it('has nothing to say without images or without a keyphrase', function (int $total, string $keyphrase) {
    $result = assessImageKeyphrase($total, 0, keyphrase: $keyphrase);

    expect($result->score)->toBe(3)
        ->and($result->messageKey)->toBe('twill-seo::analysis.image_keyphrase.missing_input');
})->with([
    'no images' => [0, 'green tea'],
    'no keyphrase' => [4, ''],
    'neither' => [0, ''],
]);

it('reports how many images matched out of how many', function () {
    expect(assessImageKeyphrase(10, 3)->messageParams)->toBe(['count' => 3, 'total' => 10]);
});

it('always applies and identifies itself as imageKeyphrase', function () {
    $assessment = new ImageKeyphraseAssessment;

    expect($assessment->identifier())->toBe('imageKeyphrase')
        ->and($assessment->isApplicable(AnalysisFactory::context(Paper::builder()->build())))->toBeTrue();
});

it('says what is wrong in plain words', function () {
    expect(assessImageKeyphrase(10, 2)->text)->toBe(
        'Only 2 of the 10 images mention the keyphrase in their alt text. Describe a few more of them '
        .'in the words the page is about.'
    );
});
