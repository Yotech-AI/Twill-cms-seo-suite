<?php

namespace TwillSeo\Analysis\Language\De;

use TwillSeo\Analysis\Language\FleschFormula;

/**
 * Reading ease for German: the Amstad adaptation, published by Toni Amstad in
 * 1978 and in general use since — 180 − words per sentence − 58.5 × syllables
 * per word.
 *
 * Amstad refitted Flesch's constants to German because German compounds its
 * nouns and runs its sentences long; scoring German on the English formula
 * would report every ordinary page as difficult.
 *
 * Higher is easier, on the same hundred-point scale. The constants are the
 * formula itself, not a tuning knob.
 */
final readonly class GermanFleschFormula implements FleschFormula
{
    public function compute(
        float $averageWordsPerSentence,
        float $syllablesPerWord,
        float $syllablesPer100Words,
    ): float {
        return 180 - $averageWordsPerSentence - (58.5 * $syllablesPerWord);
    }
}
