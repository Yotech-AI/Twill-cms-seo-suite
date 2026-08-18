<?php

namespace TwillSeo\Analysis\Research\Support;

use Normalizer;
use TwillSeo\Analysis\Html\TextNormalizer;
use TwillSeo\Analysis\Language\LanguagePack;
use TwillSeo\Analysis\Language\WordTokenizer;

/**
 * Decides whether a keyphrase appears in a piece of text, and how often.
 *
 * Every keyphrase assessment leans on this, so all of them are only as good as
 * the normalisation here: a keyphrase an author can plainly see in the copy
 * must never fail to match because of a curly apostrophe, a capital letter or
 * a hyphen the editor inserted.
 */
final class KeyphraseMatcher
{
    /**
     * @param  WordTokenizer  $tokenizer  the language pack's tokenizer, so a language with its own
     *                                    word rules matches by those rules
     */
    public function __construct(private readonly WordTokenizer $tokenizer = new WordTokenizer) {}

    /**
     * The words of a keyphrase that carry meaning.
     *
     * @return list<string>
     */
    public function contentWords(string $keyphrase, LanguagePack $language): array
    {
        $words = $this->tokenize($keyphrase);
        $content = $language->functionWords()->filter($words);

        // A keyphrase like "best of the best" would otherwise be reduced to
        // "best best"; one made only of function words would be reduced to
        // nothing and then match every text ever written.
        return $content === [] ? $words : $content;
    }

    public function isOnlyFunctionWords(string $keyphrase, LanguagePack $language): bool
    {
        $words = $this->tokenize($keyphrase);

        return $words !== [] && $language->functionWords()->filter($words) === [];
    }

    /**
     * @param  list<string>  $contentWords
     */
    public function allWordsInText(array $contentWords, string $text): bool
    {
        $tokens = $this->tokenSet($text);

        foreach ($contentWords as $word) {
            if (! isset($tokens[$this->normalize($word)])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $contentWords
     * @param  list<string>  $sentences
     */
    public function allWordsInOneSentence(array $contentWords, array $sentences): bool
    {
        foreach ($sentences as $sentence) {
            if ($this->allWordsInText($contentWords, $sentence)) {
                return true;
            }
        }

        return false;
    }

    /**
     * How often the keyphrase occurs, counted per sentence.
     *
     * Within a sentence the phrase can only appear as often as its rarest
     * content word does — "best dog" is in "best best dog" once, not twice.
     * Counting per sentence stops a phrase from being counted across a full
     * stop, where the words are not really together.
     *
     * @param  list<string>  $sentences
     */
    public function countOccurrences(string $keyphrase, array $sentences, LanguagePack $language): int
    {
        $contentWords = $this->contentWords($keyphrase, $language);

        if ($contentWords === []) {
            return 0;
        }

        $total = 0;

        foreach ($sentences as $sentence) {
            $counts = $this->tokenCounts($sentence);
            $occurrences = null;

            foreach ($contentWords as $word) {
                $found = $counts[$this->normalize($word)] ?? 0;

                if ($found === 0) {
                    $occurrences = 0;

                    break;
                }

                $occurrences = $occurrences === null ? $found : min($occurrences, $found);
            }

            $total += $occurrences ?? 0;
        }

        return $total;
    }

    public function containsExactPhrase(string $haystack, string $keyphrase): bool
    {
        return $this->exactPhrasePosition($haystack, $keyphrase) !== null;
    }

    public function exactPhrasePosition(string $haystack, string $keyphrase): ?int
    {
        $needle = $this->normalize($keyphrase);

        if ($needle === '') {
            return null;
        }

        $position = mb_stripos($this->normalize($haystack), $needle);

        return $position === false ? null : $position;
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        return $this->tokenizer->tokenize($this->normalize($text));
    }

    /**
     * Every token of $text, plus the parts of every hyphenated token.
     *
     * The parts are what makes "well known" match copy that says
     * "well-known": tokens stay whole so a hyphenated keyphrase still matches
     * a hyphenated word, and the split parts are only an extra way in.
     *
     * @return array<string,true>
     */
    private function tokenSet(string $text): array
    {
        $set = [];

        foreach ($this->tokenize($text) as $token) {
            $set[$token] = true;

            if (str_contains($token, '-')) {
                foreach (explode('-', $token) as $part) {
                    $set[$part] = true;
                }
            }
        }

        return $set;
    }

    /**
     * @return array<string,int>
     */
    private function tokenCounts(string $text): array
    {
        $counts = [];

        foreach ($this->tokenize($text) as $token) {
            $counts[$token] = ($counts[$token] ?? 0) + 1;

            if (str_contains($token, '-')) {
                foreach (explode('-', $token) as $part) {
                    $counts[$part] = ($counts[$part] ?? 0) + 1;
                }
            }
        }

        return $counts;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(TextNormalizer::fold($text));

        // Composed and decomposed forms of the same accented character look
        // identical on screen but are different bytes. intl is optional, so
        // this is a best effort rather than a guarantee.
        if (class_exists(Normalizer::class)) {
            $normalized = Normalizer::normalize($text, Normalizer::FORM_C);

            if (is_string($normalized)) {
                $text = $normalized;
            }
        }

        return $text;
    }
}
