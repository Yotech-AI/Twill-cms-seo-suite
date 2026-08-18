<?php

namespace TwillSeo\Analysis\Language\En;

use TwillSeo\Analysis\Html\TextNormalizer;
use TwillSeo\Analysis\Language\SyllableCounter;

/**
 * Counts English syllables by counting the vowel groups a word is written
 * with, then undoing the one systematic lie English spelling tells: the silent
 * final e.
 *
 * The result is an estimate. It feeds a readability band, not a dictionary, so
 * being within a syllable on an odd word costs nothing — being wrong on
 * "the", "makes" and "walked" would cost a great deal, because those are the
 * words a text is mostly made of.
 */
final readonly class EnglishSyllableCounter implements SyllableCounter
{
    /**
     * y counts as a vowel: it carries the only syllable in "gym" and the
     * second in "happy". Accented vowels are included so a borrowed word
     * ("café") is not counted as one beat short.
     */
    private const VOWELS = 'aeiouyáàâäãåéèêëíìîïóòôöõúùûüý';

    /**
     * A final -es keeps its e after these, because the ending has to be
     * pronounced to be heard at all: houses, boxes, buzzes, pages, places,
     * watches, wishes.
     */
    private const SIBILANT_STEM_ENDINGS = ['s', 'x', 'z', 'g', 'c', 'ch', 'sh'];

    /**
     * @param  array<string,int>  $deviations  lowercased word => spoken syllables, for the
     *                                         words the vowel groups get wrong
     */
    public function __construct(private array $deviations = []) {}

    public function count(string $word): int
    {
        $letters = self::letters($word);

        // Not a word at all — a number, a dash, an empty cell. It contributes
        // no syllables rather than a minimum of one.
        if ($letters === '') {
            return 0;
        }

        if (isset($this->deviations[$letters])) {
            return $this->deviations[$letters];
        }

        $syllables = preg_match_all('/['.self::VOWELS.']+/u', $letters);
        $syllables -= self::endsInSilentE($letters) ? 1 : 0;
        $syllables += self::breaksBeforeIng($letters) ? 1 : 0;

        // Never zero: a word written without vowels ("tsk", "hmm") is still
        // said out loud.
        return max(1, $syllables);
    }

    /**
     * Whether the -ing ending starts a syllable of its own that the vowel
     * groups missed.
     *
     * "writing" spells its two beats with two vowel groups, but "being",
     * "going" and "playing" run the stem vowel into the i of -ing, so both
     * beats end up in a single group. A vowel directly in front of that i is
     * exactly where that happens.
     */
    private static function breaksBeforeIng(string $word): bool
    {
        $position = strlen($word) - 4;

        return $position >= 1 && str_ends_with($word, 'ing') && str_contains(self::VOWELS, $word[$position]);
    }

    /**
     * Whether the word ends in an e that is written but not spoken, including
     * the e that a plural or a past tense hides in the middle of -es and -ed.
     */
    private static function endsInSilentE(string $word): bool
    {
        $suffix = match (true) {
            str_ends_with($word, 'es') => 's',
            str_ends_with($word, 'ed') => 'd',
            str_ends_with($word, 'e') => '',
            default => null,
        };

        if ($suffix === null) {
            return false;
        }

        $position = strlen($word) - strlen($suffix) - 1;

        if ($position < 1) {
            return false;
        }

        $before = $word[$position - 1];

        // The e is part of a bigger vowel group and was never counted on its
        // own: agreed, freed, played, died.
        if (str_contains(self::VOWELS, $before)) {
            return false;
        }

        // A consonant plus -le is a syllable of its own: table, little,
        // articles. Not so when a vowel precedes the l — smiles, styles.
        if ($before === 'l' && $position >= 2 && ! str_contains(self::VOWELS, $word[$position - 2])) {
            return false;
        }

        $stem = substr($word, 0, $position);

        if ($suffix === 's' && self::endsWithAny($stem, self::SIBILANT_STEM_ENDINGS)) {
            return false;
        }

        // -ted and -ded are the two endings that keep the e audible: wanted,
        // needed. Everything else swallows it: walked, watched, used.
        return ! ($suffix === 'd' && ($stem !== '' && str_contains('td', $stem[strlen($stem) - 1])));
    }

    /**
     * @param  list<string>  $endings
     */
    private static function endsWithAny(string $stem, array $endings): bool
    {
        foreach ($endings as $ending) {
            if (str_ends_with($stem, $ending)) {
                return true;
            }
        }

        return false;
    }

    /** The word reduced to its letters, so "Don't." counts as "dont". */
    private static function letters(string $word): string
    {
        return (string) preg_replace('/[^\p{L}]+/u', '', mb_strtolower(TextNormalizer::fold($word)));
    }
}
