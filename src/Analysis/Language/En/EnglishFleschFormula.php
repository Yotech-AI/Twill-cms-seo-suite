<?php

namespace TwillSeo\Analysis\Language\En;

use TwillSeo\Analysis\Language\FleschFormula;

/**
 * Flesch Reading Ease for English, published by Rudolf Flesch in 1948 and in
 * the public domain: 206.835 − 1.015 × words per sentence − 84.6 × syllables
 * per word.
 *
 * Higher is easier. The constants are the formula itself, not a tuning knob.
 */
final readonly class EnglishFleschFormula implements FleschFormula
{
    public function compute(
        float $averageWordsPerSentence,
        float $syllablesPerWord,
        float $syllablesPer100Words,
    ): float {
        // The third input is what the Dutch and German adaptations work from;
        // the English formula has no use for it.
        return 206.835 - (1.015 * $averageWordsPerSentence) - (84.6 * $syllablesPerWord);
    }
}
