<?php

namespace TwillSeo\Analysis\Language;

/**
 * Resolves a paper's locale to the pack that analyses it. Locales arrive in
 * every spelling a CMS uses (nl, nl_NL, nl-NL, NL) and all of them mean the
 * same language, so only the language subtag decides.
 */
final class LanguagePackRegistry
{
    /** @var array<string,LanguagePack> keyed by language code */
    private array $packs = [];

    public function __construct(private readonly LanguagePack $default = new DefaultLanguagePack) {}

    public function register(LanguagePack $pack): void
    {
        $this->packs[self::languageCode($pack->code())] = $pack;
    }

    public function forLocale(string $locale): LanguagePack
    {
        return $this->packs[self::languageCode($locale)] ?? $this->default;
    }

    /**
     * The language subtag, lowercased. Paper::languageCode() applies the same
     * rule to its own locale; the two are kept independent so a Paper stays a
     * plain input object with no dependency on the language layer.
     */
    public static function languageCode(string $locale): string
    {
        return strtolower(strtok(trim($locale), '_-') ?: '');
    }
}
