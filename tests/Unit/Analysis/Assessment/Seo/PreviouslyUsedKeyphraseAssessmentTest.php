<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Seo\PreviouslyUsedKeyphraseAssessment;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Analysis\Research\KeyphraseUsageCount;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;
use TwillSeo\Tests\Unit\Analysis\Support\CountingUsageProvider;

function usageContext(?int $usages, string $keyphrase = 'green tea'): AnalysisContext
{
    return AnalysisFactory::context(
        Paper::builder()->keyword($keyphrase)->build(),
        usage: new CountingUsageProvider($usages),
    );
}

function assessUsage(?int $usages, string $keyphrase = 'green tea'): AssessmentResult
{
    return (new PreviouslyUsedKeyphraseAssessment)->assess(usageContext($usages, $keyphrase));
}

it('scores how often the keyphrase is already in use elsewhere', function (int $usages, int $score, string $branch) {
    $result = assessUsage($usages);

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.previously_used_keyphrase.{$branch}")
        ->and($result->messageParams)->toBe(['count' => $usages]);
})->with([
    'nowhere else' => [0, 9, 'unique'],
    'on one other page' => [1, 6, 'used_once'],
    'on two other pages' => [2, 1, 'used_multiple'],
    'all over the site' => [11, 1, 'used_multiple'],
]);

it('says nothing about a paper with no keyphrase', function () {
    $result = assessUsage(0, '');

    expect($result->score)->toBe(1)
        ->and($result->messageKey)->toBe('twill-seo::analysis.previously_used_keyphrase.missing_keyphrase');
});

it('does not apply when the host cannot answer', function () {
    // Null is "I do not know", which is not the same as "nowhere else" — a
    // keyphrase must never read as unique because nothing was wired up.
    expect((new PreviouslyUsedKeyphraseAssessment)->isApplicable(usageContext(null)))->toBeFalse()
        ->and((new PreviouslyUsedKeyphraseAssessment)->isApplicable(usageContext(0)))->toBeTrue();
});

it('does not apply with the default provider, which knows nothing', function () {
    $context = AnalysisFactory::context(Paper::builder()->keyword('green tea')->build());

    expect((new PreviouslyUsedKeyphraseAssessment)->isApplicable($context))->toBeFalse();
});

it('asks the host exactly once per analysis', function () {
    $provider = new CountingUsageProvider(2);
    $context = AnalysisFactory::context(Paper::builder()->keyword('green tea')->build(), usage: $provider);
    $assessment = new PreviouslyUsedKeyphraseAssessment;

    $assessment->isApplicable($context);
    $assessment->assess($context);
    $context->research(KeyphraseUsageCount::class);

    expect($provider->calls)->toBe(1);
});

it('identifies itself as previouslyUsedKeyphrase', function () {
    expect((new PreviouslyUsedKeyphraseAssessment)->identifier())->toBe('previouslyUsedKeyphrase');
});

it('says what is wrong in plain words', function () {
    expect(assessUsage(2)->text)->toBe(
        'This keyphrase is already used on 2 other pages. They will compete with each other in the '
        .'results — give each page a phrase of its own.'
    );
});
