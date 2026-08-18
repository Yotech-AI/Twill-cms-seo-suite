<?php

namespace TwillSeo\Analysis\Language;

use TwillSeo\Analysis\Language\En\EnglishLanguagePack;

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

    /**
     * The registry a host gets when it does not build one itself: every
     * language pack this package ships, with the generic pack behind them.
     *
     * A bare `new LanguagePackRegistry` stays empty on purpose — it is what a
     * test uses to see how the engine behaves for a language it knows nothing
     * about.
     */
    public static function withDefaults(): self
    {
        $registry = new self;
        $registry->register(new EnglishLanguagePack);

        return $registry;
    }

    public function register(LanguagePack $pack): void
    {
        $this->packs[self::languageCode($pack->code())] = $pack;
    }

    public function forLocale(string $locale): LanguagePack
    {
        return $this->packs[self::languageCode($locale)] ?? $this->default;
    }

    /**
     * The language subtag, lowercased. Public because AnalysisRunner reports
     * the key a pack was looked up under rather than deriving the locale a
     * second time — the report and the pack that produced it must agree.
     *
     * Paper::languageCode() applies the same rule to its own locale; the two
     * are kept independent so a Paper stays a plain input object with no
     * dependency on the language layer, and PaperTest pins them together.
     */
    public static function languageCode(string $locale): string
    {
        return strtolower(strtok(trim($locale), '_-') ?: '');
    }
}
