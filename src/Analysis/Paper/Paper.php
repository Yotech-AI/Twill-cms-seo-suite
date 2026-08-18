<?php

namespace TwillSeo\Analysis\Paper;

use DateTimeImmutable;

/**
 * Everything the analysis is given about one piece of content. A plain input
 * object: it holds no framework type, knows nothing about how it was loaded,
 * and never reaches back into storage.
 *
 * @internal build one with Paper::builder(); the constructor is positional and
 *           will grow as the engine learns about more fields.
 */
final readonly class Paper
{
    /**
     * @param  string  $text  HTML fragment as the editor stored it
     * @param  list<string>  $synonyms
     * @param  int|null  $titleWidth  pixels measured in the browser; null means measure
     *                                it server-side instead
     * @param  string  $permalink  full URL, used to tell an internal link from an
     *                             external one
     * @param  array<string,mixed>  $customData
     */
    public function __construct(
        public string $text = '',
        public string $keyword = '',
        public array $synonyms = [],
        public string $title = '',
        public ?int $titleWidth = null,
        public string $description = '',
        public string $slug = '',
        public string $permalink = '',
        public string $locale = 'en',
        public ?DateTimeImmutable $date = null,
        public array $customData = [],
    ) {}

    public static function builder(): PaperBuilder
    {
        return new PaperBuilder;
    }

    /**
     * The language subtag: nl_NL and nl-NL are both Dutch. Kept here rather
     * than taken from the language layer so a Paper depends on nothing.
     *
     * A paper with no locale gets an empty code rather than a guess at
     * English. LanguagePackRegistry::languageCode() has to derive exactly the
     * same string from the same locale — a paper reported as English but
     * analysed with the fallback pack is worse than one reported as unknown,
     * and PaperTest pins the two derivations together.
     */
    public function languageCode(): string
    {
        return strtolower(strtok(trim($this->locale), '_-') ?: '');
    }

    public function hasText(): bool
    {
        return trim($this->text) !== '';
    }

    public function hasKeyword(): bool
    {
        return trim($this->keyword) !== '';
    }

    public function hasDescription(): bool
    {
        return trim($this->description) !== '';
    }

    public function hasTitle(): bool
    {
        return trim($this->title) !== '';
    }

    public function hasSlug(): bool
    {
        return trim($this->slug) !== '';
    }
}
