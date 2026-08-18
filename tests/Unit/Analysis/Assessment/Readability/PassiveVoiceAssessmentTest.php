<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\Readability\PassiveVoiceAssessment;
use TwillSeo\Analysis\Language\DefaultLanguagePack;
use TwillSeo\Analysis\Language\En\EnglishLanguagePack;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

/**
 * $total sentences, the first $passive of them in the passive voice.
 */
function passiveText(int $total, int $passive): string
{
    $sentences = [];

    for ($index = 0; $index < $total; $index++) {
        $sentences[] = $index < $passive
            ? 'The report was written by the team.'
            : 'The team writes a report every week.';
    }

    return '<p>'.implode(' ', $sentences).'</p>';
}

function assessPassive(string $html): AssessmentResult
{
    $context = AnalysisFactory::context(Paper::builder()->text($html)->build(), new EnglishLanguagePack);

    return (new PassiveVoiceAssessment)->assess($context);
}

it('scores the share of sentences written in the passive', function (int $total, int $passive, float $percentage, int $score, string $branch) {
    $result = assessPassive(passiveText($total, $passive));

    expect($result->score)->toBe($score)
        ->and($result->messageKey)->toBe("twill-seo::analysis.passive_voice.{$branch}")
        ->and($result->messageParams)->toBe(['percentage' => $percentage, 'count' => $passive]);
})->with([
    'none at all' => [40, 0, 0.0, 9, 'good'],
    'the top of the good band' => [40, 4, 10.0, 9, 'good'],
    'a hair over it' => [40, 5, 12.5, 6, 'some'],
    'the top of the middle band' => [40, 6, 15.0, 6, 'some'],
    'a hair over that' => [40, 7, 17.5, 3, 'too_many'],
    'every sentence' => [40, 40, 100.0, 3, 'too_many'],
]);

it('reads an active text of any length as fine', function () {
    expect(assessPassive('<p>The team writes the report. Readers enjoy it.</p>')->score)->toBe(9);
});

it('needs a language that can recognise a passive', function () {
    $paper = Paper::builder()->text('<p>The report was written.</p>')->build();

    expect((new PassiveVoiceAssessment)->isApplicable(AnalysisFactory::context($paper, new EnglishLanguagePack)))->toBeTrue()
        ->and((new PassiveVoiceAssessment)->isApplicable(AnalysisFactory::context($paper, new DefaultLanguagePack)))->toBeFalse();
});

it('needs a text to say anything', function () {
    $context = AnalysisFactory::context(Paper::builder()->text('')->build(), new EnglishLanguagePack);

    expect((new PassiveVoiceAssessment)->isApplicable($context))->toBeFalse();
});

it('identifies itself as passiveVoice', function () {
    expect((new PassiveVoiceAssessment)->identifier())->toBe('passiveVoice');
});

it('says what is wrong in plain words', function () {
    expect(assessPassive(passiveText(10, 5))->text)->toBe(
        '50 percent of the sentences are in the passive voice. Say who does what in at least some of '
        .'them — active sentences are shorter and easier to follow.'
    );
});
