<?php

namespace TwillSeo\Analysis\Language;

use TwillSeo\Analysis\Html\TextNormalizer;

/**
 * Splits text into sentences.
 *
 * Deliberately simple and language-free: a terminator ends a sentence only
 * when whitespace and something that can open a sentence follow it. That one
 * rule already handles decimals ("3.5") and mid-word dots, leaving only
 * initials and abbreviations as real exceptions. A language pack supplies its
 * own abbreviation list; without one, "Dr." does split, which is the honest
 * behaviour for a tokenizer that knows no language.
 */
final class SentenceTokenizer
{
    private const TERMINATORS = ['.', '!', '?', '…'];

    /** A terminator inside these belongs to the sentence, not to the next one. */
    private const CLOSERS = ['"', "'", ')', ']', '}', '»', '”', '’'];

    /** @var list<string> lowercased, without the trailing dot */
    private array $abbreviations;

    /**
     * @param  list<string>  $abbreviations  words that end in a dot without ending a sentence
     */
    public function __construct(array $abbreviations = [])
    {
        $this->abbreviations = array_values(array_map(
            fn (string $abbreviation) => rtrim(mb_strtolower(trim($abbreviation)), '.'),
            $abbreviations,
        ));
    }

    /**
     * @return list<string>
     */
    public function tokenize(string $text): array
    {
        $characters = preg_split('//u', TextNormalizer::collapseWhitespace($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $total = count($characters);

        $sentences = [];
        $current = '';
        $index = 0;

        while ($index < $total) {
            $character = $characters[$index];
            $current .= $character;
            $index++;

            if (! in_array($character, self::TERMINATORS, true)) {
                continue;
            }

            // Swallow the rest of the terminator run ("..." or "?!") and any
            // closing quote or bracket, so an ellipsis is one terminator and
            // He said "Stop." keeps its quote.
            while ($index < $total && (in_array($characters[$index], self::TERMINATORS, true) || in_array($characters[$index], self::CLOSERS, true))) {
                $current .= $characters[$index];
                $index++;
            }

            if ($this->endsSentence($current, $characters, $index, $total)) {
                $sentences[] = trim($current);
                $current = '';
            }
        }

        $last = trim($current);

        if ($last !== '') {
            $sentences[] = $last;
        }

        return $sentences;
    }

    /**
     * @param  list<string>  $characters
     */
    private function endsSentence(string $current, array $characters, int $index, int $total): bool
    {
        // Nothing follows, so the buffer is flushed as the final sentence by
        // the caller rather than split here.
        if ($index >= $total) {
            return false;
        }

        // "section2.Now" is one sentence: a terminator that is not followed by
        // whitespace is part of a token, not a boundary.
        if (trim($characters[$index]) !== '') {
            return false;
        }

        while ($index < $total && trim($characters[$index]) === '') {
            $index++;
        }

        if ($index >= $total || ! self::opensSentence($characters[$index])) {
            return false;
        }

        return ! $this->isAbbreviation($current);
    }

    private static function opensSentence(string $character): bool
    {
        return preg_match('/^[\p{Lu}\p{N}"\'“‘(\[]$/u', $character) === 1;
    }

    /**
     * Whether the word in front of the terminator is one that carries a dot
     * without ending a sentence: a single-letter initial ("J. Doe") or a
     * configured abbreviation.
     */
    private function isAbbreviation(string $current): bool
    {
        if (preg_match('/([\p{L}\p{N}]+)[.!?…\'")\]}]*$/u', $current, $matches) !== 1) {
            return false;
        }

        $word = $matches[1];

        if (mb_strlen($word) === 1 && preg_match('/^\p{L}$/u', $word) === 1) {
            return true;
        }

        return in_array(mb_strtolower($word), $this->abbreviations, true);
    }
}
