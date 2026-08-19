<?php

namespace TwillSeo\Analysis\Language\De;

use TwillSeo\Analysis\Html\TextNormalizer;
use TwillSeo\Analysis\Language\SyllableCounter;

/**
 * Counts German syllables from the vowel groups a word is written with.
 *
 * German spelling is regular enough that no silent-e rule is needed: the -e of
 * "Katze" and "Sprache" is spoken, and counting it is simply right. Three
 * things do need saying, and all three are rules rather than word lists:
 *
 *  - a run of vowels is only one syllable when German really spells that run as
 *    one sound. "ei", "au", "eu", "äu" and "ie" are single sounds; "io", "ea"
 *    and "ua" never are, so "Na-ti-on", "The-a-ter" and "Si-tu-a-ti-on" break
 *    where the spelling suggests they do not. That one rule is what makes the
 *    whole -tion family come out right with no entry in any list;
 *  - the u of "qu" belongs to the consonant, not to the vowel that follows it:
 *    "Quel-le" is two beats, not three;
 *  - y in front of a vowel is the consonant it sounds like ("Yo-ga", "Bay-ern")
 *    and a vowel everywhere else ("Sys-tem", "A-na-ly-se").
 *
 * Everything is done with preg and mb_* on whole strings; there is no byte
 * indexing anywhere, because ä, ö, ü and ß have to survive.
 */
final readonly class GermanSyllableCounter implements SyllableCounter
{
    /**
     * The umlauts are ordinary German vowels and are counted as such. The
     * accented letters are there for the loanwords German keeps them in
     * ("Café", "Attaché"), so a borrowed word is not counted a beat short.
     */
    private const VOWELS = 'aeiouyäöüáàéèêíóúô';

    /**
     * The vowel runs German spells as a single sound, longest first: the
     * counter takes the longest match at each step.
     *
     * Anything not here is a hiatus — two vowels that are simply said one after
     * the other — and counts as one syllable per letter.
     *
     * "ui" is deliberately absent: German writes it only where the two vowels
     * really are said apart ("Ru-i-ne"). "ay" and "ey" are absent too, because
     * the y rule below has already turned them into a consonant plus a vowel.
     */
    private const CLUSTERS = ['ei', 'ai', 'au', 'eu', 'äu', 'ie', 'aa', 'ee', 'oo'];

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

        preg_match_all('/['.self::VOWELS.']+/u', self::spelling($letters), $runs);

        $syllables = 0;

        foreach ($runs[0] as $run) {
            $syllables += self::countClusters($run);
        }

        // Never zero: a word written without vowels ("pst", "hm") is still said
        // out loud.
        return max(1, $syllables);
    }

    /**
     * The word rewritten the way it sounds, as far as spelling alone can tell:
     * the u of "qu" folded into the consonant it belongs to, and a y in front
     * of a vowel turned into the consonant it is there.
     */
    private static function spelling(string $letters): string
    {
        return (string) preg_replace('/y(?=['.self::VOWELS.'])/u', 'j', str_replace('qu', 'q', $letters));
    }

    /**
     * Walks a run of vowels, taking the longest German vowel cluster it can at
     * each step. Every match is one syllable, and so is every leftover vowel
     * that no cluster claimed.
     */
    private static function countClusters(string $run): int
    {
        $syllables = 0;
        $length = mb_strlen($run);
        $index = 0;

        while ($index < $length) {
            $index += self::clusterLengthAt($run, $index, $length);
            $syllables++;
        }

        return $syllables;
    }

    private static function clusterLengthAt(string $run, int $index, int $length): int
    {
        foreach (self::CLUSTERS as $cluster) {
            $size = mb_strlen($cluster);

            if ($index + $size <= $length && mb_substr($run, $index, $size) === $cluster) {
                return $size;
            }
        }

        return 1;
    }

    /** The word reduced to its letters, so "Straße." counts as "straße". */
    private static function letters(string $word): string
    {
        return (string) preg_replace('/[^\p{L}]+/u', '', mb_strtolower(TextNormalizer::fold($word)));
    }
}
