<?php

namespace TwillSeo\Analysis\Language;

/**
 * A case-insensitive set of words: function words, transition words, first
 * word exceptions. Kept as a keyed set rather than a list because membership
 * is tested once per word of the copy.
 */
final readonly class WordList
{
    /**
     * @param  array<string,true>  $words  lowercased keys
     */
    private function __construct(private array $words) {}

    /**
     * @param  list<string>  $words
     */
    public static function fromArray(array $words): self
    {
        $set = [];

        foreach ($words as $word) {
            $normalized = mb_strtolower(trim($word));

            if ($normalized !== '') {
                $set[$normalized] = true;
            }
        }

        return new self($set);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function contains(string $word): bool
    {
        return isset($this->words[mb_strtolower(trim($word))]);
    }

    public function isEmpty(): bool
    {
        return $this->words === [];
    }

    /**
     * Removes this list's members from $words.
     *
     * @param  list<string>  $words
     * @return list<string>
     */
    public function filter(array $words): array
    {
        return array_values(array_filter($words, fn (string $word) => ! $this->contains($word)));
    }
}
