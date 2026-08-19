<?php

namespace TwillSeo\Analysis\Language\Nl;

use TwillSeo\Analysis\Html\TextNormalizer;
use TwillSeo\Analysis\Language\SyllableCounter;

/**
 * Counts Dutch syllables from the vowel groups a word is written with.
 *
 * Dutch spelling is far more honest about its vowels than English is, so there
 * is no silent-e rule to undo: the -e of "mode" and "gemeente" is spoken, and
 * counting it is simply right. Two things do need saying, and both are rules
 * rather than word lists:
 *
 *  - a run of vowels is only one syllable when Dutch really spells that run as
 *    one sound. "oe", "ij", "aai" and "eeu" are single sounds; "eo", "ea",
 *    "ua" and "io" never are, so "the-a-ter", "ja-nu-a-ri" and "vi-de-o" break
 *    where the spelling suggests they do not;
 *  - the diaeresis is Dutch orthography saying "a new syllable starts here",
 *    which is the whole reason "ideeën" and "ruïne" are written with one. The
 *    counter reads it exactly that way.
 *
 * Everything is done with preg and mb_* on whole strings; there is no byte
 * indexing anywhere, because "ruïne" and "coördinatie" have to survive.
 */
final readonly class DutchSyllableCounter implements SyllableCounter
{
    /**
     * The diaeresis vowels. Dutch writes them to break a vowel group apart, so
     * they are both vowels and syllable boundaries.
     */
    private const DIAERESIS = ['ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u'];

    /**
     * Accents that carry stress or length rather than a syllable break: "één"
     * is one beat, "café" two. Folded onto the plain letter before counting,
     * so the vowel-cluster rules below can be written in plain letters.
     */
    private const STRESS_ACCENTS = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u',
    ];

    private const VOWELS = 'aeiouyëïöü';

    /**
     * The vowel runs Dutch spells as a single sound, longest first: the counter
     * takes the longest match at each step, so "mooie" reads as ooi + e rather
     * than oo + ie, and "koeien" as oei + e.
     *
     * Anything not here is a hiatus — two vowels that are simply said one after
     * the other — and counts as one syllable per letter. That is what makes
     * "chaos", "duo", "video" and "situatie" come out right with no word list.
     *
     * "ij" needs no entry: j is a consonant here, so "vrij" is already one
     * group and "bijzonder" three.
     */
    private const CLUSTERS = [
        'aai', 'ooi', 'oei', 'eeu', 'ieu', 'eau',
        'aa', 'ee', 'oo', 'uu', 'ie', 'oe', 'eu', 'ui', 'ei', 'au', 'ou', 'ai',
    ];

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
            $syllables += self::countRun($run);
        }

        // Never zero: a word written without vowels ("tsj", "brr") is still
        // said out loud.
        return max(1, $syllables);
    }

    /**
     * The word rewritten the way it sounds, as far as spelling alone can tell:
     * stress accents dropped, the u of "qu" folded into the consonant it
     * belongs to (kwa-li-teit, not qu-a), and a y in front of a vowel turned
     * into the consonant it is there ("yoga" is jo-ga, not y-o-ga).
     */
    private static function spelling(string $letters): string
    {
        $folded = strtr($letters, self::STRESS_ACCENTS);
        $folded = str_replace('qu', 'q', $folded);

        return (string) preg_replace('/y(?=['.self::VOWELS.'])/u', 'j', $folded);
    }

    /**
     * How many syllables one unbroken run of vowels is worth.
     */
    private static function countRun(string $run): int
    {
        $syllables = 0;

        foreach (self::splitOnDiaeresis($run) as $part) {
            $syllables += self::countClusters($part);
        }

        return $syllables;
    }

    /**
     * Splits a vowel run wherever a diaeresis says a new syllable starts, and
     * drops the diaeresis afterwards: what "ëi" of "beëindigen" spells is the
     * ordinary "ei" of a new syllable.
     *
     * @return list<string>
     */
    private static function splitOnDiaeresis(string $run): array
    {
        $marked = (string) preg_replace('/(?<=.)(['.implode('', array_keys(self::DIAERESIS)).'])/u', '|$1', $run);

        return array_values(array_filter(
            explode('|', strtr($marked, self::DIAERESIS)),
            fn (string $part) => $part !== '',
        ));
    }

    /**
     * Walks a run of plain vowels, taking the longest Dutch vowel cluster it
     * can at each step. Every match is one syllable, and so is every leftover
     * vowel that no cluster claimed.
     */
    private static function countClusters(string $part): int
    {
        $syllables = 0;
        $length = mb_strlen($part);
        $index = 0;

        while ($index < $length) {
            $index += self::clusterLengthAt($part, $index, $length);
            $syllables++;
        }

        return $syllables;
    }

    private static function clusterLengthAt(string $part, int $index, int $length): int
    {
        foreach (self::CLUSTERS as $cluster) {
            $size = mb_strlen($cluster);

            if ($index + $size <= $length && mb_substr($part, $index, $size) === $cluster) {
                return $size;
            }
        }

        return 1;
    }

    /** The word reduced to its letters, so "auto's." counts as "autos". */
    private static function letters(string $word): string
    {
        return (string) preg_replace('/[^\p{L}]+/u', '', mb_strtolower(TextNormalizer::fold($word)));
    }
}
