<?php

namespace TwillSeo\Analysis\Language;

/**
 * Counts the syllables of a single word, which is the one input a readability
 * formula cannot derive from the text itself.
 *
 * Every implementation is an approximation: syllable counts are a property of
 * pronunciation, and the text only carries spelling. The contract is therefore
 * "close enough to band a text", not "correct for every word".
 */
interface SyllableCounter
{
    /** @return int at least 1 for any word with a letter in it */
    public function count(string $word): int;
}
