<?php

namespace TwillSeo\Analysis\Language\En;

use TwillSeo\Analysis\Language\PassiveVoiceDetector;
use TwillSeo\Analysis\Language\WordList;
use TwillSeo\Analysis\Language\WordTokenizer;

/**
 * Finds the English periphrastic passive: an auxiliary ("was", "is being",
 * "got") followed closely by a past participle ("thrown", "published").
 *
 * The naive form of that rule fires on half of ordinary English prose, so
 * three guards narrow it down. Each exists because of a whole class of
 * sentence, not because of one example:
 *
 *  - clauses are scanned separately, so "Although he was late, we finished the
 *    work" cannot pair an auxiliary in one clause with a participle in another;
 *  - a candidate directly behind a determiner is a noun, not a verb: "there was
 *    a wound", "it is a mixed bag";
 *  - a candidate directly behind a degree adverb is an adjective: nothing is
 *    "very built", so "he was very excited" describes a mood rather than
 *    something done to him.
 *
 * A bare adjectival participle ("he was tired") still counts as passive. That
 * is a deliberate ruling, documented in docs/analysis.md: the wording is what
 * the assessment asks the author to reconsider, and "he was tired" reads
 * exactly like a passive to a reader.
 */
final readonly class EnglishPassiveVoiceDetector implements PassiveVoiceDetector
{
    /**
     * How far behind the auxiliary a participle may sit. Four tokens covers
     * "has been quietly and repeatedly ignored" without reaching into the next
     * verb phrase.
     */
    private const SEARCH_WINDOW = 4;

    /** A participle never follows one of these; a noun does. */
    private const DETERMINERS = [
        'a', 'an', 'the', 'this', 'that', 'these', 'those', 'my', 'your', 'his', 'her', 'its',
        'our', 'their', 'some', 'any', 'no', 'every', 'each', 'another', 'other', 'such',
    ];

    /**
     * Words that grade an adjective. A verbal passive cannot be graded — "the
     * house was very built" is not English — so one of these in front of the
     * candidate settles it as an adjective.
     *
     * Kept to pure intensifiers. "completely", "entirely" and "totally" grade
     * too, but they modify real passives constantly ("was completely
     * rebuilt"), so listing them would cost more passives than it saved.
     */
    private const DEGREE_ADVERBS = [
        'very', 'quite', 'rather', 'too', 'so', 'extremely', 'incredibly', 'remarkably',
        'awfully', 'terribly', 'somewhat', 'fairly', 'really', 'pretty', 'unusually',
    ];

    /**
     * A subject or a complementizer opens a new clause even without a comma, so
     * the auxiliary before it cannot govern what follows: "the problem is that
     * nobody checked" is not passive.
     */
    private const CLAUSE_STARTERS = [
        'that', 'which', 'who', 'whom', 'whose', 'what', 'when', 'where', 'why', 'how',
        'because', 'i', 'he', 'she', 'we', 'they', 'you',
    ];

    /** The shortest a regular participle can be; below it, "bed" and "red" qualify. */
    private const MINIMUM_REGULAR_PARTICIPLE_LENGTH = 4;

    public function __construct(
        private WordTokenizer $tokenizer,
        private WordList $auxiliaries,
        private WordList $irregularParticiples,
        private WordList $nonParticiples,
    ) {}

    public function isPassive(string $sentence): bool
    {
        foreach (self::clauses($sentence) as $clause) {
            if ($this->clauseIsPassive($clause)) {
                return true;
            }
        }

        return false;
    }

    private function clauseIsPassive(string $clause): bool
    {
        $tokens = array_map(mb_strtolower(...), $this->tokenizer->tokenize($clause));
        $total = count($tokens);

        foreach ($tokens as $position => $token) {
            if (! $this->auxiliaries->contains($token)) {
                continue;
            }

            $last = min($position + self::SEARCH_WINDOW, $total - 1);

            for ($index = $position + 1; $index <= $last; $index++) {
                if (in_array($tokens[$index], self::CLAUSE_STARTERS, true)) {
                    break;
                }

                if ($this->isParticiple($tokens[$index]) && ! self::isNounOrAdjectiveHere($tokens, $index)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isParticiple(string $token): bool
    {
        if ($this->irregularParticiples->contains($token)) {
            return true;
        }

        return str_ends_with($token, 'ed')
            && mb_strlen($token) >= self::MINIMUM_REGULAR_PARTICIPLE_LENGTH
            && ! $this->nonParticiples->contains($token);
    }

    /**
     * @param  list<string>  $tokens
     */
    private static function isNounOrAdjectiveHere(array $tokens, int $index): bool
    {
        $previous = $tokens[$index - 1];

        return in_array($previous, self::DETERMINERS, true) || in_array($previous, self::DEGREE_ADVERBS, true);
    }

    /**
     * @return list<string> the clauses a comma, semicolon or colon separates
     */
    private static function clauses(string $sentence): array
    {
        return preg_split('/[,;:]+/u', $sentence, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
