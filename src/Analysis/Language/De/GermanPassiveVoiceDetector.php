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

    /**
     * The auxiliaries that build the Vorgangspassiv and nothing else a
     * participle could belong to. Kept apart from the sein forms because the
     * perfect-only guard below must not fire behind one of these: werden has no
     * perfect, so there is no perfect reading to suppress.
     *
     * A heuristic rather than a data file, for the same reason the guards are:
     * passive/auxiliaries.php describes the language, this splits that list for
     * one decision the detector makes.
     */
    private const PROCESS_AUXILIARIES = [
        'werde', 'wirst', 'wird', 'werden', 'werdet', 'wurde', 'wurdest',
        'wurden', 'wurdet', 'worden', 'geworden', 'würde', 'würdest', 'würden',
        'würdet',
    ];

    /**
     * The particle that marks what follows as an infinitive rather than a
     * finite verb. "Zu werden" heads a phrase of its own and governs nothing
     * outside it.
     */
    private const INFINITIVE_MARKER = 'zu';

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
     * The guard applies ONLY behind a sein form, never behind a werden form —
     * see clauseIsPassive(). That is what its name says: it suppresses the
     * *perfect* reading, and only sein has one. A werden form has no perfect to
     * be confused with, so "das Problem wurde eskaliert" stays the passive it
     * is while "die Lage ist eskaliert" stays the perfect it is.
     *
     * Being auxiliary-aware is also what lets an ambitransitive verb sit here
     * at all: the entry costs nothing on the werden side. Verbs whose two
     * readings are both common behind *sein* ("zerbrechen") are still
     * deliberately absent, because there the guard would have to choose.
     *
     * The test each entry had to pass is whether the verb has a perfect with
     * sein that would otherwise read as a Zustandspassiv. "Versinken",
     * "ausfallen" and "eintreffen" do.
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
     * Whether one clause is passive.
     *
     * The question is asked once per participle rather than once per clause,
     * because which auxiliary a participle belongs to decides how it reads. A
     * werden form only ever builds a passive or a future, and the future is
     * already ruled out by the participle rule — so the participle a werden
     * form governs is a passive even when the verb is usually intransitive.
     * "Das Problem wurde eskaliert" is one: German lets "ein Ticket
     * eskalieren" take an object, and business copy says so daily.
     *
     * A sein form is the ambiguous one — it builds the Zustandspassiv *and* the
     * perfect of every verb of motion and change — so only there does the
     * perfect-only guard apply.
     *
     * Clause-wide bookkeeping cannot express that. "Die Firma ist gewachsen um
     * Marktführer zu werden" would pair the "werden" of a purpose clause with a
     * "gewachsen" that plainly belongs to "ist", and report a perfect as a
     * passive.
     *
     * @param  list<string>  $tokens
     */
    private function clauseIsPassive(array $tokens): bool
    {
        $auxiliaries = $this->pairableAuxiliaries($tokens);

        if ($auxiliaries === []) {
            return false;
        }

        foreach ($tokens as $index => $token) {
            if ($this->auxiliaries->contains($token) || ! $this->isParticiple($token)) {
                continue;
            }

            if (self::isNounOrAdjectiveHere($tokens, $index)) {
                continue;
            }

            if (! in_array($token, self::PERFECT_ONLY_PARTICIPLES, true) || self::governedByProcess($auxiliaries, $index)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The auxiliaries a participle in this clause may pair with, as position =>
     * whether it is a werden form.
     *
     * A zu-marked infinitive is deliberately left out. "Um Marktführer zu
     * werden" is a purpose clause: an infinitive behind "zu" heads its own
     * phrase and cannot govern a participle sitting outside it, so it is not an
     * auxiliary for this purpose at all. German usually writes a comma in front
     * of such a clause, which would have split it off anyway — but headline and
     * bullet copy routinely leaves the comma out, and that is exactly the
     * register a CMS holds.
     *
     * @param  list<string>  $tokens
     * @return array<int,bool>
     */
    private function pairableAuxiliaries(array $tokens): array
    {
        $auxiliaries = [];

        foreach ($tokens as $index => $token) {
            if (! $this->auxiliaries->contains($token)) {
                continue;
            }

            if ($index > 0 && $tokens[$index - 1] === self::INFINITIVE_MARKER) {
                continue;
            }

            $auxiliaries[$index] = in_array($token, self::PROCESS_AUXILIARIES, true);
        }

        return $auxiliaries;
    }

    /**
     * Whether the auxiliary that governs the participle at $index is a werden
     * form.
     *
     * The governing auxiliary is taken to be the nearest one in the clause,
     * which is the best a detector can do without parsing: German puts the
     * finite verb before its participle in a main clause and after it in a
     * subordinate one, so distance says more than direction does. Ties go to
     * the auxiliary in front, which is where a main clause puts it — the
     * ascending scan below settles that on its own.
     *
     * @param  array<int,bool>  $auxiliaries
     */
    private static function governedByProcess(array $auxiliaries, int $index): bool
    {
        $governing = false;
        $nearest = PHP_INT_MAX;

        foreach ($auxiliaries as $position => $isProcess) {
            $distance = abs($position - $index);

            if ($distance < $nearest) {
                $nearest = $distance;
                $governing = $isProcess;
            }
        }

        return $governing;
    }

    private function isParticiple(string $token): bool
    {
        if (mb_strlen($token) < self::MINIMUM_PARTICIPLE_LENGTH) {
            return false;
        }

        if ($this->nonParticiples->contains($token)) {
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
