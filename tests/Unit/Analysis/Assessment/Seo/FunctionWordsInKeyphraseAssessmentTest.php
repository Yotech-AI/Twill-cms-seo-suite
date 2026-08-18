<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Rating;
use TwillSeo\Analysis\Assessment\ResultCategory;
use TwillSeo\Analysis\Assessment\Seo\FunctionWordsInKeyphraseAssessment;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Language\En\EnglishLanguagePack;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function functionWordsContext(string $keyphrase): AnalysisContext
{
    return AnalysisFactory::context(Paper::builder()->keyword($keyphrase)->build(), new EnglishLanguagePack);
}

it('only applies to a keyphrase made of nothing but function words', function (string $keyphrase, bool $applicable) {
    expect((new FunctionWordsInKeyphraseAssessment)->isApplicable(functionWordsContext($keyphrase)))->toBe($applicable);
})->with([
    'all function words' => ['about us', true],
    'a single function word' => ['the', true],
    'a longer function phrase' => ['what to do with it', true],
    'one content word among them' => ['about our prices', false],
    'no function words at all' => ['green tea', false],
    'no keyphrase at all' => ['', false],
    'whitespace only' => ['   ', false],
]);

it('reports feedback rather than a verdict', function () {
    $result = (new FunctionWordsInKeyphraseAssessment)->assess(functionWordsContext('about us'));

    // Score 0 is the feedback sentinel: this is a warning about the keyphrase
    // itself, so it must not drag the SEO average down as if it were a fail.
    expect($result->score)->toBe(0)
        ->and($result->rating)->toBe(Rating::Feedback)
        ->and($result->category)->toBe(ResultCategory::Feedback)
        ->and($result->countsTowardScore)->toBeFalse()
        ->and($result->messageKey)->toBe('twill-seo::analysis.function_words_in_keyphrase.only_function_words');
});

it('identifies itself as functionWordsInKeyphrase', function () {
    expect((new FunctionWordsInKeyphraseAssessment)->identifier())->toBe('functionWordsInKeyphrase');
});

it('says what is wrong in plain words', function () {
    expect((new FunctionWordsInKeyphraseAssessment)->assess(functionWordsContext('about us'))->text)->toBe(
        'The keyphrase is made up entirely of common words, so it cannot single this page out. '
        .'Add the word for what the page is actually about.'
    );
});
