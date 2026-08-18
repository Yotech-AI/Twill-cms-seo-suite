<?php

namespace TwillSeo\Analysis\Language;

/**
 * The pack used for any language with no pack of its own: generic tokenizers,
 * no word lists, no readability. It still analyses everything that is
 * language-free, so an unsupported locale gets an SEO score rather than an
 * error.
 *
 * The code is a constructor argument so a language with no refinements beyond
 * the generic behaviour can be registered without a class of its own.
 */
final readonly class DefaultLanguagePack implements LanguagePack
{
    private SentenceTokenizer $sentences;

    private WordTokenizer $words;

    public function __construct(private string $code = 'default')
    {
        $this->sentences = new SentenceTokenizer;
        $this->words = new WordTokenizer;
    }

    public function code(): string
    {
        return $this->code;
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
        return WordList::empty();
    }

    public function transitionWords(): ?object
    {
        return null;
    }

    public function firstWordExceptions(): ?WordList
    {
        return null;
    }

    public function passiveVoice(): ?object
    {
        return null;
    }

    public function syllableCounter(): ?object
    {
        return null;
    }

    public function fleschFormula(): ?object
    {
        return null;
    }

    public function sentenceLengthLimit(): int
    {
        return 20;
    }

    public function supportsFullReadability(): bool
    {
        return false;
    }
}
