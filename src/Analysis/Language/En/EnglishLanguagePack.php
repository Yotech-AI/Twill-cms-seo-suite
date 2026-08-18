<?php

namespace TwillSeo\Analysis\Language\En;

use TwillSeo\Analysis\Language\Data\DataFileLoader;
use TwillSeo\Analysis\Language\FleschFormula;
use TwillSeo\Analysis\Language\LanguagePack;
use TwillSeo\Analysis\Language\PassiveVoiceDetector;
use TwillSeo\Analysis\Language\SentenceTokenizer;
use TwillSeo\Analysis\Language\SyllableCounter;
use TwillSeo\Analysis\Language\TransitionWords;
use TwillSeo\Analysis\Language\WordList;
use TwillSeo\Analysis\Language\WordTokenizer;

/**
 * English: the one language this engine knows completely.
 *
 * The pack is the seam between the language-free engine and the hand-compiled
 * word lists under resources/lang-data/en. Everything it exposes is built once
 * here, because a pack is registered once and then asked the same questions for
 * every paper the site has.
 */
final readonly class EnglishLanguagePack implements LanguagePack
{
    /** Above 20 words a sentence starts asking the reader to hold too much at once. */
    private const SENTENCE_LENGTH_LIMIT = 20;

    private SentenceTokenizer $sentences;

    private WordTokenizer $words;

    private WordList $functionWords;

    private TransitionWords $transitions;

    private WordList $firstWordExceptions;

    private PassiveVoiceDetector $passive;

    private SyllableCounter $syllables;

    private FleschFormula $flesch;

    public function __construct()
    {
        $this->sentences = new SentenceTokenizer(self::data('abbreviations'));
        $this->words = new WordTokenizer;
        $this->functionWords = WordList::fromArray(self::data('function-words'));
        $this->transitions = new TransitionWords(self::data('transition-words'), self::data('two-part-transitions'));
        $this->firstWordExceptions = WordList::fromArray(self::data('first-word-exceptions'));

        $this->passive = new EnglishPassiveVoiceDetector(
            $this->words,
            WordList::fromArray(self::data('passive/auxiliaries')),
            WordList::fromArray(self::data('passive/irregular-participles')),
            WordList::fromArray(self::data('passive/non-participles')),
        );

        $syllables = self::data('syllables');
        $this->syllables = new EnglishSyllableCounter(is_array($syllables['deviations'] ?? null) ? $syllables['deviations'] : []);
        $this->flesch = new EnglishFleschFormula;
    }

    public function code(): string
    {
        return 'en';
    }

    public function sentenceTokenizer(): SentenceTokenizer
    {
        return $this->sentences;
    }

    public function wordTokenizer(): WordTokenizer
    {
        return $this->words;
    }

    public function functionWords(): WordList
    {
        return $this->functionWords;
    }

    public function transitionWords(): TransitionWords
    {
        return $this->transitions;
    }

    public function firstWordExceptions(): WordList
    {
        return $this->firstWordExceptions;
    }

    public function passiveVoice(): PassiveVoiceDetector
    {
        return $this->passive;
    }

    public function syllableCounter(): SyllableCounter
    {
        return $this->syllables;
    }

    public function fleschFormula(): FleschFormula
    {
        return $this->flesch;
    }

    public function sentenceLengthLimit(): int
    {
        return self::SENTENCE_LENGTH_LIMIT;
    }

    public function supportsFullReadability(): bool
    {
        return true;
    }

    /**
     * @return array<mixed>
     */
    private static function data(string $name): array
    {
        return DataFileLoader::load(__DIR__.'/../../../../resources/lang-data/en/'.$name.'.php');
    }
}
