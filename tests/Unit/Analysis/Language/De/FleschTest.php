<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\De;

use TwillSeo\Analysis\Language\De\GermanFleschFormula;
use TwillSeo\Analysis\Language\De\GermanLanguagePack;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Analysis\Report\FleschBand;
use TwillSeo\Analysis\Research\FleschReadingEase;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function germanFleschOf(string $html): ?float
{
    return AnalysisFactory::context(Paper::builder()->text($html)->locale('de')->build(), new GermanLanguagePack)
        ->research(FleschReadingEase::class);
}

it('computes the amstad reading ease of a german text whose numbers are known', function () {
    // Two sentences, 27 words, 48 syllables, counted by hand:
    //
    //   Die(1) Stadt(1) hat(1) beschlossen(3) die(1) Bibliothek(4) im(1)
    //   Zentrum(2) nächstes(2) Jahr(1) neu(1) zu(1) bauen(2)            = 21
    //   Besucher(3) können(2) danach(2) einen(2) modernen(3) Lesesaal(3)
    //   mit(1) viel(1) Platz(1) für(1) die(1) jungen(2) Studenten(3)
    //   nutzen(2)                                                       = 27
    //
    //   180 - (27 / 2) - 58.5 * (48 / 27) = 62.5
    $text = '<p>Die Stadt hat beschlossen, die Bibliothek im Zentrum nächstes Jahr neu zu bauen. '
        .'Besucher können danach einen modernen Lesesaal mit viel Platz für die jungen Studenten '
        .'nutzen.</p>';

    expect(germanFleschOf($text))->toBe(62.5);
});

it('bands the german score the same way every other language is banded', function () {
    $score = germanFleschOf('<p>Die Stadt hat beschlossen, die Bibliothek im Zentrum nächstes Jahr neu zu '
        .'bauen. Besucher können danach einen modernen Lesesaal mit viel Platz für die jungen '
        .'Studenten nutzen.</p>');

    expect(FleschBand::fromScore((float) $score))->toBe(FleschBand::Standard);
});

it('scores plain german prose far higher than dense german prose', function () {
    $plain = '<p>Die Katze schläft auf der Bank. Wir gehen jeden Tag mit dem Hund durch den Park.</p>';
    $dense = '<p>Die unvermeidliche Institutionalisierung der organisatorischen Verantwortlichkeiten '
        .'kennzeichnet die administrativen Entscheidungsverfahren innerhalb der städtischen '
        .'Verwaltungsorganisation.</p>';

    expect(germanFleschOf($plain))->toBeGreaterThan(80.0)
        ->and(germanFleschOf($dense))->toBeLessThan(20.0);
});

it('reads the amstad formula from its three inputs', function () {
    // The engine hands the formula all three figures; Amstad works from
    // syllables per word and ignores syllables per hundred words.
    expect((new GermanFleschFormula)->compute(13.5, 48 / 27, 4800 / 27))
        ->toBeGreaterThan(62.4)
        ->toBeLessThan(62.6);
});

it('reports no german reading ease for a text too short to judge', function () {
    expect(germanFleschOf('<p>Das ist zu kurz für ein Urteil.</p>'))->toBeNull();
});
