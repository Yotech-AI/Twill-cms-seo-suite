<?php

namespace TwillSeo\Analysis\Language;

/**
 * Everything the analysis needs to know about one language.
 *
 * The nullable capabilities are the ones a language either has data for or
 * does not: a pack without a syllable counter simply cannot produce a Flesch
 * score, and the assessment that needs it declares itself inapplicable rather
 * than guessing. They are typed as ?object until the classes behind them land
 * with the real language packs.
 */
interface LanguagePack
{
    /** The ISO 639-1 language code this pack answers to, e.g. 'nl'. */
    public function code(): string;

    public function sentenceTokenizer(): SentenceTokenizer;

    public function wordTokenizer(): WordTokenizer;

    /** May be empty, in which case keyphrase matching uses every word. */
    public function functionWords(): WordList;

    public function transitionWords(): ?object;

    public function firstWordExceptions(): ?WordList;

    public function passiveVoice(): ?object;

    public function syllableCounter(): ?object;

    public function fleschFormula(): ?object;

    /** Words above which a sentence counts as too long. */
    public function sentenceLengthLimit(): int;

    /** False when the pack lacks the data the readability assessments need. */
    public function supportsFullReadability(): bool;
}
