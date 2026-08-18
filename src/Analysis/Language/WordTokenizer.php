<?php

namespace TwillSeo\Analysis\Language;

use TwillSeo\Analysis\Html\TextNormalizer;

/**
 * Splits text into words. Unicode-aware, because a word count that drops
 * "münchen" or "straße" is worse than no word count at all.
 */
final class WordTokenizer
{
    /**
     * A word is a run of letters or digits, optionally joined to further runs
     * by an apostrophe or a hyphen. Keeping "don't", "auto's" and "well-known"
     * whole is what lets a keyphrase match the words an author actually typed.
     */
    private const WORD_PATTERN = "/[\p{L}\p{N}]+(?:['\-][\p{L}\p{N}]+)*/u";

    /**
     * @return list<string>
     */
    public function tokenize(string $text): array
    {
        // Folded first so a curly apostrophe joins a word the same way a
        // straight one does.
        preg_match_all(self::WORD_PATTERN, TextNormalizer::foldQuotes($text), $matches);

        return $matches[0];
    }

    public function count(string $text): int
    {
        return count($this->tokenize($text));
    }
}
