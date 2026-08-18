<?php

namespace TwillSeo\Analysis\Report;

use JsonSerializable;

/**
 * The result of one analysis run.
 *
 * This shape is the contract the storage layer and the editor panel are built
 * against: keys, their order and their types are fixed. Sections that were
 * switched off are reported empty rather than omitted, so a consumer never has
 * to check whether a key is there.
 */
final readonly class AnalysisReport implements JsonSerializable
{
    /**
     * @param  string  $locale  the language subtag, not the full locale
     * @param  bool  $languageSupported  whether the language has the data the readability
     *                                   assessments need
     * @param  Insights|null  $insights  null only when insights were switched off
     */
    public function __construct(
        public string $locale,
        public bool $languageSupported,
        public ScoreSection $seo,
        public ScoreSection $readability,
        public ?Insights $insights,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'locale' => $this->locale,
            'languageSupported' => $this->languageSupported,
            'seo' => $this->seo->jsonSerialize(),
            'readability' => $this->readability->jsonSerialize(),
            'insights' => $this->insights?->jsonSerialize(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
