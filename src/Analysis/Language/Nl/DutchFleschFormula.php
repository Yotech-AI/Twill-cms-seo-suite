<?php

namespace TwillSeo\Analysis\Language\Nl;

use TwillSeo\Analysis\Language\FleschFormula;

/**
 * Reading ease for Dutch: the Flesch-Douma adaptation, published by W. H.
 * Douma in 1960 and long in general use — 206.84 − 0.77 × syllables per
 * hundred words − 0.93 × words per sentence.
 *
 * Douma refitted Flesch's constants to Dutch because Dutch words carry more
 * syllables than English ones and its sentences run longer; scoring Dutch on
 * the English formula would report every ordinary page as difficult.
 *
 * Higher is easier, on the same hundred-point scale. The constants are the
 * formula itself, not a tuning knob.
 */
final readonly class DutchFleschFormula implements FleschFormula
{
    public function compute(
        float $averageWordsPerSentence,
        float $syllablesPerWord,
        float $syllablesPer100Words,
    ): float {
        return 206.84 - (0.77 * $syllablesPer100Words) - (0.93 * $averageWordsPerSentence);
    }
}
