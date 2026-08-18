<?php

namespace TwillSeo\Analysis\Language;

/**
 * A language's reading-ease formula.
 *
 * All three inputs are passed even though no single formula uses all of them:
 * the English Flesch Reading Ease works from syllables per word, while the
 * Dutch and German adaptations are written in terms of syllables per hundred
 * words. Passing both spares every caller from knowing which is which.
 */
interface FleschFormula
{
    public function compute(
        float $averageWordsPerSentence,
        float $syllablesPerWord,
        float $syllablesPer100Words,
    ): float;
}
