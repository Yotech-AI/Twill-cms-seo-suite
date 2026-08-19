<?php

namespace TwillSeo\Analysis\Language\De;

use TwillSeo\Analysis\Language\PassiveVoiceDetector;
use TwillSeo\Analysis\Language\WordList;
use TwillSeo\Analysis\Language\WordTokenizer;

/**
 * Finds the German passive: a form of "werden" or "sein" together with a
 * Partizip II — "der Brief wird geschrieben", "die Tür ist verschlossen".
 *
 * The trap that makes German unlike English and Dutch is that werden is also
 * the future auxiliary. "Er wird kommen" has the auxiliary of a passive and is
 * not one, and neither is "er wird bezahlen" or "sie wird uns verstehen". What
 * separates those from "er wird bezahlt" is the shape of the second verb, so
 * the participle rule is deliberately narrow about -en: it counts only with a
 * ge- (which no infinitive has) or from the list of strong participles the
 * inseparable verbs build. Everything ending in -en that is neither is an
 * infinitive, and an infinitive is a future, not a passive.
 *
 * Clause handling follows the Dutch pack for the same reason: German puts the
 * participle at the end of its clause and moves the finite verb behind it in a
 * subordinate clause, so no look-ahead window can describe where the two sit.
 * A clause is passive when it holds both an auxiliary and a participle, and the
 * clause is cut small first — on punctuation (German requires a comma before a
 * subordinate clause, which does most of the work), on the conjunctions that
 * open one, and on the coordinating ones that join two whole sentences.
 *
 * Three guards keep the count honest, each about a whole class of sentence:
 *
 *  - a candidate directly behind a determiner is a noun: "das Gebiet", "in den
 *    Gebäuden";
 *  - a candidate directly behind a degree adverb is an adjective — nothing is
 *    "sehr gebaut" — so "er war sehr begeistert" describes a mood rather than
 *    something done to him;
 *  - a participle that only ever builds a perfect has no passive at all. "sein"
 *    is both the auxiliary of the Zustandspassiv ("die Tür ist verschlossen")
 *    and the perfect auxiliary of "er ist gekommen", and nothing but the verb
 *    itself tells them apart, so the verbs that describe a change rather than a
 *    deed are listed.
 *
 * A bare adjectival participle ("er war überrascht") still counts as passive,
 * the same deliberate ruling the English detector makes — see docs/analysis.md.
 */
final readonly class GermanPassiveVoiceDetector implements PassiveVoiceDetector
{
    /**
     * The shortest a participle can be: "getan" and "geübt" are five letters,
     * and below that "geht", "gebt" and "erst" would all qualify.
     */
    private const MINIMUM_PARTICIPLE_LENGTH = 5;

    /**
     * The prefixes a separable verb puts in front of the ge-: "durch-ge-führt",
     * "ein-ge-laden", "ab-ge-holt". Most real passives in German copy are built
     * this way, so a rule that only looked at the start of the word would miss
     * them all.
     */
    private const SEPARABLE_PREFIXES = [
        'ab', 'an', 'auf', 'aus', 'bei', 'durch', 'ein', 'empor', 'entgegen',
        'fest', 'fort', 'frei', 'gegenüber', 'heim', 'her', 'hin', 'hoch', 'los',
        'mit', 'nach', 'nieder', 'statt', 'teil', 'über', 'um', 'unter', 'voran',
        'vor', 'weg', 'weiter', 'wieder', 'zu', 'zurück', 'zusammen',
    ];

    /** The prefixes that replace the ge- rather than sit in front of it. */
    private const INSEPARABLE_PREFIXES = ['be', 'ver', 'er', 'ent', 'zer', 'emp', 'miss', 'über', 'unter'];

    /** A participle never follows one of these; a noun does. */
    private const DETERMINERS = [
        'der', 'die', 'das', 'den', 'dem', 'des',
        'ein', 'eine', 'einen', 'einem', 'einer', 'eines',
        'kein', 'keine', 'keinen', 'keinem', 'keiner', 'keines',
        'dieser', 'diese', 'dieses', 'diesen', 'diesem',
        'meine', 'meinen', 'meinem', 'meiner', 'seine', 'seinen', 'seinem',
        'seiner', 'ihre', 'ihren', 'ihrem', 'ihrer', 'unsere', 'unseren',
        'jeder', 'jede', 'jedes', 'jeden', 'jedem', 'alle', 'allen', 'viele',
        'vielen', 'manche', 'einige', 'mehrere', 'welche', 'solche', 'andere',
        'anderen',
    ];

    /**
     * Words that grade an adjective. A verbal passive cannot be graded — "das
     * Haus wurde sehr gebaut" is not German — so one of these in front of the
     * candidate settles it as an adjective.
     *
     * Kept to pure intensifiers. "völlig", "komplett" and "total" grade too,
     * but they modify real passives constantly ("wurde völlig zerstört"), so
     * listing them would cost more passives than it saved. "besonders" is left
     * out for the same reason: "wurde besonders gefördert" is a real passive.
     */
    private const DEGREE_ADVERBS = [
        'sehr', 'ganz', 'ziemlich', 'recht', 'äußerst', 'überaus', 'ungemein',
        'höchst', 'allzu', 'echt', 'unglaublich', 'furchtbar', 'schrecklich',
        'wahnsinnig', 'ausgesprochen',
    ];

    /**
     * Participles that build a perfect with "sein" and have no passive at all,
     * because their verb takes no object: nobody can be come, risen or happened.
     *
     * They are a guard rather than a word list entry, because they really are
     * participles — the detector has to recognise them and then decline to
     * count them, which is not the same as pretending they are nouns.
     *
     * The test each entry had to pass is whether the verb can take an object at
     * all. "Versinken", "ausfallen" and "eintreffen" cannot, so "das Schiff ist
     * versunken" and "das Konzert ist ausgefallen" are perfects and not
     * passives. Verbs that are unaccusative in one reading and transitive in
     * another ("zerbrechen") are deliberately absent: the transitive passive
     * they build is real, and a guard is all or nothing.
     */
    private const PERFECT_ONLY_PARTICIPLES = [
        'gewesen', 'geworden', 'geblieben', 'gekommen', 'angekommen',
        'zurückgekommen', 'hereingekommen', 'gegangen', 'ausgegangen',
        'weggegangen', 'gefahren', 'abgefahren', 'gelaufen', 'geflogen',
        'gereist', 'gestorben', 'gewachsen', 'geschehen', 'passiert',
        'gelungen', 'misslungen', 'aufgestanden', 'eingeschlafen', 'aufgewacht',
        'begegnet', 'gestiegen', 'gefallen', 'gesprungen', 'geschwommen',
        'verschwunden', 'versunken', 'erschienen', 'entstanden', 'entgangen',
        'ausgefallen', 'eingetroffen', 'eskaliert', 'gewandert',
        'geklettert', 'umgezogen', 'gelandet', 'geflohen', 'ertrunken',
        'zerfallen',
    ];

    /**
     * Subordinating conjunctions and relative pronouns that open a new clause,
     * plus the coordinating conjunctions that join two of them.
     *
     * Articles and subject pronouns are deliberately absent, unlike in the
     * English detector. German puts the verb second, so the subject regularly
     * follows its auxiliary — "gestern wurde das Haus verkauft" — and breaking
     * there would throw away the passive rather than find it.
     */
    private const CLAUSE_STARTERS = [
        'und', 'oder', 'aber', 'sondern', 'denn',
        'dass', 'ob', 'weil', 'obwohl', 'obgleich', 'wenn', 'falls', 'damit',
        'sodass', 'bevor', 'nachdem', 'während', 'sobald', 'solange', 'seitdem',
        'indem', 'sofern', 'warum', 'wieso', 'weshalb', 'welcher', 'welches',
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
     * Whether the word is spelled the way a German participle is. Three shapes,
     * because German builds its participle three ways:
     *
     *  - an optional separable prefix, then ge-, then -t or -en: "gebaut",
     *    "geschrieben", "durchgeführt", "eingeladen";
     *  - an inseparable prefix and -t: "bezahlt", "verkauft", "erhöht";
     *  - a verb borrowed into -ieren, which takes no ge- at all: "informiert",
     *    "organisiert", "dokumentiert". That last group is easy to forget and
     *    covers a great deal of modern German business copy, where a text that
     *    says "wurde dokumentiert" would otherwise read as active.
     *
     * The inseparable prefixes deliberately do not take -en. That ending is the
     * whole future-tense trap: "wird bezahlen" and "wird verstehen" are shaped
     * exactly like a participle would be if -en were allowed there. The strong
     * participles that really do end that way are listed in
     * passive/irregular-participles.php instead, where each is a decision
     * rather than a side effect. The -ieren infinitive is safe for the same
     * reason: it ends in -en, not -iert.
     */
    private static function hasParticipleShape(string $token): bool
    {
        $separable = implode('|', self::SEPARABLE_PREFIXES);
        $inseparable = implode('|', self::INSEPARABLE_PREFIXES);

        return preg_match(
            '/^(?:(?:'.$separable.')?(?:ge\p{L}{2,}(?:t|en)|(?:'.$inseparable.')\p{L}{2,}t)|\p{L}{3,}iert)$/u',
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
