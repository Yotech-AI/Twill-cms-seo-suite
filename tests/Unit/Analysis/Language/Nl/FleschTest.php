<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\Nl;

use TwillSeo\Analysis\Language\Nl\DutchFleschFormula;
use TwillSeo\Analysis\Language\Nl\DutchLanguagePack;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Analysis\Report\FleschBand;
use TwillSeo\Analysis\Research\FleschReadingEase;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function dutchFleschOf(string $html): ?float
{
    return AnalysisFactory::context(Paper::builder()->text($html)->locale('nl')->build(), new DutchLanguagePack)
        ->research(FleschReadingEase::class);
}

it('computes the douma reading ease of a dutch text whose numbers are known', function () {
    // Two sentences, 28 words, 53 syllables, counted by hand:
    //
    //   De(1) gemeente(3) heeft(1) besloten(3) om(1) de(1) bibliotheek(4) in(1)
    //   het(1) centrum(2) volgend(2) jaar(1) te(1) verbouwen(3)          = 25
    //   Bezoekers(3) kunnen(2) daarna(2) gebruikmaken(4) van(1) een(1)
    //   moderne(3) leeszaal(2) met(1) veel(1) ruimte(2) voor(1) jonge(2)
    //   studenten(3)                                                     = 28
    //
    //   206.84 - 0.77 * (5300 / 28) - 0.93 * (28 / 2) = 48.07
    $text = '<p>De gemeente heeft besloten om de bibliotheek in het centrum volgend jaar te verbouwen. '
        .'Bezoekers kunnen daarna gebruikmaken van een moderne leeszaal met veel ruimte voor jonge '
        .'studenten.</p>';

    expect(dutchFleschOf($text))->toBe(48.1);
});

it('bands the dutch score the same way every other language is banded', function () {
    $score = dutchFleschOf('<p>De gemeente heeft besloten om de bibliotheek in het centrum volgend jaar te '
        .'verbouwen. Bezoekers kunnen daarna gebruikmaken van een moderne leeszaal met veel ruimte voor '
        .'jonge studenten.</p>');

    expect(FleschBand::fromScore((float) $score))->toBe(FleschBand::Difficult);
});

it('scores plain dutch prose far higher than dense dutch prose', function () {
    $plain = '<p>De kat slaapt op de bank. Wij lopen elke dag met de hond door het park.</p>';
    $dense = '<p>De onvermijdelijke institutionalisering van de organisatorische verantwoordelijkheden '
        .'kenmerkt de administratieve besluitvormingsprocedures binnen de gemeentelijke organisatie.</p>';

    expect(dutchFleschOf($plain))->toBeGreaterThan(80.0)
        ->and(dutchFleschOf($dense))->toBeLessThan(20.0);
});

it('reads the douma formula from its three inputs', function () {
    // The engine hands the formula all three figures; Douma works from
    // syllables per hundred words and ignores syllables per word.
    expect((new DutchFleschFormula)->compute(14.0, 1.8928571428571428, 189.28571428571428))
        ->toBeGreaterThan(48.0)
        ->toBeLessThan(48.2);
});

it('reports no dutch reading ease for a text too short to judge', function () {
    expect(dutchFleschOf('<p>Dit is te kort om iets over te zeggen.</p>'))->toBeNull();
});
