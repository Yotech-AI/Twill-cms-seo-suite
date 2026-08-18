<?php

namespace TwillSeo\Tests\Unit\Analysis\Report;

use TwillSeo\Analysis\Language\DefaultLanguagePack;
use TwillSeo\Analysis\Language\En\EnglishLanguagePack;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Analysis\Report\FleschBand;
use TwillSeo\Analysis\Research\FleschReadingEase;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function fleschOf(string $html, bool $english = true): ?float
{
    $context = AnalysisFactory::context(
        Paper::builder()->text($html)->build(),
        $english ? new EnglishLanguagePack : new DefaultLanguagePack,
    );

    return $context->research(FleschReadingEase::class);
}

it('computes the reading ease of a text whose numbers are known', function () {
    // Two sentences, 22 words, 38 syllables:
    //   206.835 - 1.015 * 11 - 84.6 * (38 / 22) = 49.5
    $text = '<p>The quick brown fox jumps over the lazy dog. A second sentence follows here '
        .'with several longer words about information and communication.</p>';

    expect(fleschOf($text))->toBeGreaterThan(49.0)->toBeLessThan(50.0);
});

it('reports nothing for a text too short to say anything about', function (int $words, bool $expected) {
    $text = '<p>'.implode(' ', array_fill(0, $words, 'word')).'.</p>';

    expect(fleschOf($text) === null)->toBe($expected);
})->with([
    'nothing at all' => [0, true],
    'a handful of words' => [5, true],
    'exactly ten' => [10, true],
    'eleven' => [11, false],
]);

it('reports nothing for a language that cannot count syllables', function () {
    $text = '<p>'.implode(' ', array_fill(0, 30, 'word')).'.</p>';

    expect(fleschOf($text, english: false))->toBeNull()
        ->and(fleschOf($text))->not->toBeNull();
});

it('keeps the score inside the hundred point scale', function () {
    $simple = '<p>'.str_repeat('The cat sat. The dog ran. ', 4).'</p>';
    $dense = '<p>'.str_repeat('Incomprehensible institutionalisation predominantly characterises '
        .'multidimensional organisational infrastructure administration methodologies ', 3).'.</p>';

    expect(fleschOf($simple))->toBe(100.0)
        ->and(fleschOf($dense))->toBe(0.0);
});

it('rounds to one decimal', function () {
    $score = fleschOf('<p>The quick brown fox jumps over the lazy dog. A second sentence follows here '
        .'with several longer words about information and communication.</p>');

    expect($score)->toBe(round((float) $score, 1));
});

it('bands a score into what it means for a reader', function (float $score, FleschBand $band) {
    expect(FleschBand::fromScore($score))->toBe($band);
})->with([
    'the top of the scale' => [100.0, FleschBand::VeryEasy],
    'the bottom of very easy' => [90.0, FleschBand::VeryEasy],
    'the top of easy' => [89.9, FleschBand::Easy],
    'the bottom of easy' => [80.0, FleschBand::Easy],
    'the top of fairly easy' => [79.9, FleschBand::FairlyEasy],
    'the bottom of fairly easy' => [70.0, FleschBand::FairlyEasy],
    'the top of standard' => [69.9, FleschBand::Standard],
    'the bottom of standard' => [60.0, FleschBand::Standard],
    'the top of fairly difficult' => [59.9, FleschBand::FairlyDifficult],
    'the bottom of fairly difficult' => [50.0, FleschBand::FairlyDifficult],
    'the top of difficult' => [49.9, FleschBand::Difficult],
    'the bottom of difficult' => [30.0, FleschBand::Difficult],
    'the top of very difficult' => [29.9, FleschBand::VeryDifficult],
    'the bottom of the scale' => [0.0, FleschBand::VeryDifficult],
]);

it('names each band in the report as a stable string', function () {
    expect(array_map(fn (FleschBand $band) => $band->value, FleschBand::cases()))->toBe([
        'very_easy',
        'easy',
        'fairly_easy',
        'standard',
        'fairly_difficult',
        'difficult',
        'very_difficult',
    ]);
});
