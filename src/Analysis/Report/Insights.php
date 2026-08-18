<?php

namespace TwillSeo\Analysis\Report;

use JsonSerializable;

/**
 * Facts about the content that are not judgements: the panel shows them next
 * to the score without a traffic light.
 */
final readonly class Insights implements JsonSerializable
{
    /** An average adult reads prose at roughly this rate. */
    private const WORDS_PER_MINUTE = 200;

    /**
     * @param  float|null  $fleschScore  null until a language pack can compute it; the key
     *                                   is emitted from the start so the panel never has to
     *                                   handle its absence
     */
    public function __construct(
        public int $wordCount,
        public int $readingTimeMinutes,
        public ?float $fleschScore = null,
        public ?string $fleschBand = null,
    ) {}

    public static function forWordCount(int $wordCount): self
    {
        // Never zero: "0 min read" reads like a bug, and a page with no words
        // still costs the reader the click.
        return new self($wordCount, max(1, (int) ceil($wordCount / self::WORDS_PER_MINUTE)));
    }

    /**
     * @return array{wordCount:int,readingTimeMinutes:int,fleschScore:float|null,fleschBand:string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'wordCount' => $this->wordCount,
            'readingTimeMinutes' => $this->readingTimeMinutes,
            'fleschScore' => $this->fleschScore,
            'fleschBand' => $this->fleschBand,
        ];
    }
}
