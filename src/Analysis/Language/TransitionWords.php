<?php

namespace TwillSeo\Analysis\Language;

use TwillSeo\Analysis\Html\TextNormalizer;

/**
 * One language's transition words, and the question the analysis asks of them:
 * does this sentence signal how it relates to the one before it?
 *
 * Two shapes, because English (and Dutch, and German) signal a relation in two
 * ways: a word or phrase that stands on its own ("however", "in addition"), and
 * a pair whose halves sit apart ("either … or"). A pair only counts when both
 * halves are there in order, or every "or" in the language would read as one.
 */
final readonly class TransitionWords
{
    /** @var list<string> normalized, space delimited */
    private array $singles;

    /** @var list<array{0:string,1:string}> normalized, space delimited */
    private array $twoPart;

    /**
     * @param  list<string>  $singles  single words and whole phrases alike
     * @param  list<array{0:string,1:string}>  $twoPart  correlative pairs, first half first
     */
    public function __construct(array $singles, array $twoPart = [])
    {
        $this->singles = array_values(array_filter(array_map(self::normalize(...), $singles), fn (string $entry) => $entry !== ''));

        $this->twoPart = array_values(array_filter(
            array_map(fn (array $pair) => [self::normalize($pair[0]), self::normalize($pair[1])], $twoPart),
            fn (array $pair) => $pair[0] !== '' && $pair[1] !== '',
        ));
    }

    public function occursIn(string $sentence): bool
    {
        // Padded with spaces so an entry at either end of the sentence is
        // still surrounded by the separators the search looks for. Without it
        // "However, we left" would only match an entry that is not at the start.
        $haystack = ' '.self::normalize($sentence).' ';

        foreach ($this->singles as $entry) {
            if (str_contains($haystack, ' '.$entry.' ')) {
                return true;
            }
        }

        foreach ($this->twoPart as [$first, $second]) {
            $start = strpos($haystack, ' '.$first.' ');

            // The second half has to follow the first: "dogs and cats are both
            // welcome" is not the "both … and" construction.
            if ($start !== false && strpos($haystack, ' '.$second.' ', $start + strlen($first)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Words separated by single spaces and nothing else, so punctuation between
     * two words of a phrase ("in short: it worked") cannot hide it, and no
     * entry can match the middle of a longer word.
     */
    private static function normalize(string $text): string
    {
        $folded = mb_strtolower(TextNormalizer::fold($text));

        return trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $folded));
    }
}
