<?php

namespace TwillSeo\Tests\Unit\Analysis\Support;

use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Contracts\KeyphraseUsageProvider;
use TwillSeo\Analysis\Html\HtmlParser;
use TwillSeo\Analysis\Language\DefaultLanguagePack;
use TwillSeo\Analysis\Language\FleschFormula;
use TwillSeo\Analysis\Language\LanguagePack;
use TwillSeo\Analysis\Language\PassiveVoiceDetector;
use TwillSeo\Analysis\Language\SentenceTokenizer;
use TwillSeo\Analysis\Language\SyllableCounter;
use TwillSeo\Analysis\Language\TransitionWords;
use TwillSeo\Analysis\Language\WordList;
use TwillSeo\Analysis\Language\WordTokenizer;
use TwillSeo\Analysis\Messages\ArrayMessageRenderer;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Analysis\Support\NullKeyphraseUsageProvider;

/**
 * Builders for the unit tests. Deliberately assembles the real parser,
 * renderer and language pack rather than doubles: an assessment test that
 * stubbed the renderer would pass with a message file full of missing keys.
 *
 * Not a test file — PHPUnit only collects *Test.php.
 */
final class AnalysisFactory
{
    public static function context(
        Paper $paper,
        ?LanguagePack $language = null,
        ?KeyphraseUsageProvider $usage = null,
    ): AnalysisContext {
        return new AnalysisContext(
            $paper,
            (new HtmlParser)->parse($paper->text, $paper->permalink),
            $language ?? new DefaultLanguagePack,
            new ArrayMessageRenderer,
            // The null provider by default, because that is what a host that
            // wired nothing up gets.
            $usage ?? new NullKeyphraseUsageProvider,
        );
    }

    /**
     * A pack that behaves like the default one but knows some function words,
     * which the default pack deliberately does not, and can claim a real
     * language code and full readability support the way Task 4's packs will.
     *
     * @param  list<string>  $functionWords
     * @param  list<string>  $abbreviations
     */
    public static function languagePack(
        array $functionWords = [],
        array $abbreviations = [],
        string $code = 'test',
        bool $supportsFullReadability = false,
    ): LanguagePack {
        return new class($functionWords, $abbreviations, $code, $supportsFullReadability) implements LanguagePack
        {
            private readonly WordList $words;

            private readonly SentenceTokenizer $sentences;

            private readonly WordTokenizer $tokenizer;

            /**
             * @param  list<string>  $functionWords
             * @param  list<string>  $abbreviations
             */
            public function __construct(
                array $functionWords,
                array $abbreviations,
                private readonly string $languageCode,
                private readonly bool $fullReadability,
            ) {
                $this->words = WordList::fromArray($functionWords);
                $this->sentences = new SentenceTokenizer($abbreviations);
                $this->tokenizer = new WordTokenizer;
            }

            public function code(): string
            {
                return $this->languageCode;
            }

            public function sentenceTokenizer(): SentenceTokenizer
            {
                return $this->sentences;
            }

            public function wordTokenizer(): WordTokenizer
            {
                return $this->tokenizer;
            }

            public function functionWords(): WordList
            {
                return $this->words;
            }

            // A pack with no readability data of its own: the tests that need
            // the real thing use EnglishLanguagePack, and the ones that need a
            // pack without a capability need exactly this.
            public function transitionWords(): ?TransitionWords
            {
                return null;
            }

            public function firstWordExceptions(): ?WordList
            {
                return null;
            }

            public function passiveVoice(): ?PassiveVoiceDetector
            {
                return null;
            }

            public function syllableCounter(): ?SyllableCounter
            {
                return null;
            }

            public function fleschFormula(): ?FleschFormula
            {
                return null;
            }

            public function sentenceLengthLimit(): int
            {
                return 20;
            }

            public function supportsFullReadability(): bool
            {
                return $this->fullReadability;
            }
        };
    }
}
