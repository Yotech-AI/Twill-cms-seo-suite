<?php

namespace TwillSeo\Analysis\Language\Nl;

use TwillSeo\Analysis\Language\PassiveVoiceDetector;
use TwillSeo\Analysis\Language\WordList;
use TwillSeo\Analysis\Language\WordTokenizer;

/**
 * Finds the Dutch passive: a form of "worden" or "zijn" together with a
 * voltooid deelwoord — "de brief wordt geschreven", "het huis is verkocht".
 *
 * The English detector looks a few words ahead of its auxiliary, which works
 * because English keeps the two together. Dutch does not: the participle goes
 * to the end of its clause ("de brief werd gisteren door de secretaresse
 * geschreven") and in a subordinate clause it moves in front of the auxiliary
 * again ("… dat het huis verkocht is"). Neither a window nor a direction can
 * describe that, so this detector asks a different question: does one clause
 * hold both an auxiliary and a participle?
 *
 * That only works because the clause is cut small first. A sentence is split on
 * punctuation, on the subordinating conjunctions that open a clause without one
 * (Dutch writes "ik denk dat het klaar is" with no comma at all), and on the
 * coordinating conjunctions that join two full sentences. Without that last
 * cut, "het gebouw is groot en veel mensen hebben het bezocht" would pair an
 * auxiliary in the first half with a participle in the second.
 *
 * Three guards keep the count honest, each about a whole class of sentence:
 *
 *  - a candidate directly behind a determiner is a noun: "het gewicht", "de
 *    gebouwen";
 *  - a candidate directly behind a degree adverb is an adjective — nothing is
 *    "erg gebouwd" — so "ze was erg verbaasd" describes a mood rather than
 *    something done to her;
 *  - a participle that only ever builds a perfect has no passive at all. "zijn"
 *    is both the passive auxiliary of "de brief is geschreven" and the perfect
 *    auxiliary of "hij is gekomen", and nothing but the verb itself tells them
 *    apart, so the verbs that describe a change rather than a deed are listed.
 *
 * A bare adjectival participle ("ze was verbaasd") still counts as passive,
 * the same deliberate ruling the English detector makes — see docs/analysis.md.
 */
final readonly class DutchPassiveVoiceDetector implements PassiveVoiceDetector
{
    /**
     * The shortest a participle can be: "gered" and "gezet" are five letters,
     * and below that "geld", "geen", "bent" and "best" would all qualify.
     */
    private const MINIMUM_PARTICIPLE_LENGTH = 5;

    /**
     * The prefixes a separable verb puts in front of the ge-: "aan-ge-past",
     * "op-ge-lost", "uit-ge-voerd". Most real passives in Dutch copy are built
     * this way, so a rule that only looked at the start of the word would miss
     * them all.
     *
     * "mis" is deliberately absent: it would read "misverstand" as a
     * participle, and "mislukt" — the word it would have bought — never forms
     * a passive anyway.
     */
    private const SEPARABLE_PREFIXES = [
        'aan', 'achter', 'af', 'bij', 'binnen', 'buiten', 'dicht', 'door', 'in',
        'klaar', 'los', 'mee', 'na', 'neer', 'om', 'onder', 'op', 'open', 'over',
        'samen', 'tegen', 'terug', 'thuis', 'toe', 'uit', 'vast', 'voor', 'weer',
        'weg',
    ];

    /** The prefixes that replace the ge- rather than sit in front of it. */
    private const INSEPARABLE_PREFIXES = ['be', 'ver', 'ont', 'her', 'er'];

    /** A participle never follows one of these; a noun does. */
    private const DETERMINERS = [
        'de', 'het', 'een', 'deze', 'die', 'dit', 'dat', 'mijn', 'jouw', 'uw',
        'haar', 'hun', 'ons', 'onze', 'elke', 'elk', 'iedere', 'ieder', 'alle',
        'sommige', 'enkele', 'enige', 'veel', 'meer', 'meeste', 'geen', 'welke',
        'zulke', 'andere', 'ander', 'beide',
    ];

    /**
     * Words that grade an adjective. A verbal passive cannot be graded — "het
     * huis was erg gebouwd" is not Dutch — so one of these in front of the
     * candidate settles it as an adjective.
     *
     * Kept to pure intensifiers. "volledig", "compleet" and "totaal" grade too,
     * but they modify real passives constantly ("werd volledig herbouwd"), so
     * listing them would cost more passives than it saved.
     */
    private const DEGREE_ADVERBS = [
        'heel', 'erg', 'zeer', 'nogal', 'te', 'behoorlijk', 'ontzettend', 'enorm',
        'bijzonder', 'tamelijk', 'redelijk', 'uiterst', 'vreselijk', 'hartstikke',
    ];

    /**
     * Participles that build a perfect with "zijn" and have no passive at all,
     * because their verb takes no object: nobody can be come, risen or happened.
     *
     * They are a guard rather than a word list entry, because they really are
     * participles — the detector has to recognise them and then decline to
     * count them, which is not the same as pretending they are nouns.
     */
    private const PERFECT_ONLY_PARTICIPLES = [
        'geweest', 'gebleven', 'geworden', 'gekomen', 'aangekomen', 'teruggekomen',
        'thuisgekomen', 'binnengekomen', 'gegaan', 'weggegaan', 'uitgegaan',
        'gevallen', 'gestorven', 'overleden', 'vertrokken', 'gearriveerd',
        'gebeurd', 'ontstaan', 'verdwenen', 'gebleken', 'geslaagd', 'gestegen',
        'gedaald', 'gegroeid', 'begonnen', 'opgestaan', 'gevlucht', 'geschrokken',
        'gereisd', 'gelukt', 'mislukt', 'verhuisd', 'gezakt', 'gevlogen',
    ];

    /**
     * Subordinating conjunctions and relative pronouns that open a new clause,
     * plus the coordinating conjunctions that join two of them.
     *
     * Subject pronouns are deliberately absent, unlike in the English detector.
     * Dutch puts the verb second, so the subject regularly follows its
     * auxiliary — "gisteren werd het huis verkocht" — and breaking there would
     * throw away the passive rather than find it.
     */
    private const CLAUSE_STARTERS = [
        'en', 'maar', 'want', 'of', 'dus', 'noch',
        'dat', 'die', 'wie', 'welke', 'waarin', 'waarbij', 'waardoor', 'waarvan',
        'omdat', 'doordat', 'aangezien', 'terwijl', 'hoewel', 'ofschoon', 'zodat',
        'opdat', 'indien', 'tenzij', 'mits', 'voordat', 'nadat', 'totdat', 'zodra',
        'zoals', 'wanneer', 'waarom',
    ];

    public function __construct(
        private WordTokenizer $tokenizer,
        private WordList $auxiliaries,
        private WordList $irregularParticiples,
        private WordList $nonParticiples,
    ) {}

    public function isPassive(string $sentence): bool
    {
        foreach ($this->clauses($sentence) as $clause) {
            if ($this->clauseIsPassive($clause)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $tokens
     */
    private function clauseIsPassive(array $tokens): bool
    {
        $auxiliary = false;
        $participle = false;

        foreach ($tokens as $index => $token) {
            if ($this->auxiliaries->contains($token)) {
                $auxiliary = true;

                continue;
            }

            if (! $participle && $this->isParticiple($token) && ! self::isNounOrAdjectiveHere($tokens, $index)) {
                $participle = true;
            }
        }

        return $auxiliary && $participle;
    }

    private function isParticiple(string $token): bool
    {
        if (mb_strlen($token) < self::MINIMUM_PARTICIPLE_LENGTH) {
            return false;
        }

        if ($this->nonParticiples->contains($token) || in_array($token, self::PERFECT_ONLY_PARTICIPLES, true)) {
            return false;
        }

        return $this->irregularParticiples->contains($token) || self::hasParticipleShape($token);
    }

    /**
     * Whether the word is spelled the way a Dutch participle is: an optional
     * separable prefix, then ge- with any of -d, -t or -en, or one of the
     * inseparable prefixes with -d or -t.
     *
     * The inseparable prefixes deliberately do not take -en. Their strong
     * participles do end that way ("verloren", "ontvangen"), but so does every
     * infinitive they build ("verkopen", "vertellen", "ontmoeten"), and the
     * infinitives outnumber the participles by far. The strong ones are listed
     * in passive/irregular-participles.php instead, where each is a decision
     * rather than a side effect.
     */
    private static function hasParticipleShape(string $token): bool
    {
        $separable = implode('|', self::SEPARABLE_PREFIXES);
        $inseparable = implode('|', self::INSEPARABLE_PREFIXES);

        return preg_match(
            '/^(?:'.$separable.')?(?:ge\p{L}{2,}(?:d|t|en)|(?:'.$inseparable.')\p{L}{2,}[dt])$/u',
            $token,
        ) === 1;
    }

    /**
     * @param  list<string>  $tokens
     */
    private static function isNounOrAdjectiveHere(array $tokens, int $index): bool
    {
        if ($index === 0) {
            return false;
        }

        $previous = $tokens[$index - 1];

        return in_array($previous, self::DETERMINERS, true) || in_array($previous, self::DEGREE_ADVERBS, true);
    }

    /**
     * The sentence cut into the clauses an auxiliary may govern: first on
     * punctuation, then on every word that opens a clause of its own.
     *
     * @return list<list<string>>
     */
    private function clauses(string $sentence): array
    {
        $clauses = [];

        foreach (preg_split('/[,;:—–]+/u', $sentence, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            $current = [];

            foreach ($this->tokenizer->tokenize($part) as $word) {
                $token = mb_strtolower($word);

                if (in_array($token, self::CLAUSE_STARTERS, true)) {
                    $clauses[] = $current;
                    $current = [];

                    continue;
                }

                $current[] = $token;
            }

            $clauses[] = $current;
        }

        return $clauses;
    }
}
