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
     * @param  float|null  $fleschScore  null when the language cannot compute one, or the
     *                                   text is too short for it to mean anything; the key
     *                                   is emitted either way so the panel never has to
     *                                   handle its absence
     */
    public function __construct(
        public int $wordCount,
        public int $readingTimeMinutes,
        public ?float $fleschScore = null,
        public ?string $fleschBand = null,
    ) {}

    public static function forWordCount(int $wordCount, ?float $fleschScore = null): self
    {
        return new self(
            $wordCount,
            // Never zero: "0 min read" reads like a bug, and a page with no
            // words still costs the reader the click.
            max(1, (int) ceil($wordCount / self::WORDS_PER_MINUTE)),
            $fleschScore,
            // The band travels with the score or not at all — a band without a
            // score would be a judgement with nothing behind it.
            $fleschScore === null ? null : FleschBand::fromScore($fleschScore)->value,
        );
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
