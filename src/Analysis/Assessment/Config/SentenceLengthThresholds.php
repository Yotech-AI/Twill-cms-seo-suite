<?php

namespace TwillSeo\Analysis\Assessment\Config;

/**
 * How much of a text may be written in long sentences.
 *
 * Two scales, because cornerstone content is held to a higher standard. The
 * bands are not merely shifted: the ordinary scale lets a text sit anywhere
 * below thirty percent, while the cornerstone scale closes at twenty-five, so
 * the upper bound is inclusive there and exclusive here. That asymmetry is in
 * the published thresholds rather than in this implementation, which is why it
 * is spelled out as a flag instead of being smoothed over.
 */
final readonly class SentenceLengthThresholds
{
    private function __construct(
        private float $goodMaximum,
        private float $acceptableMaximum,
        private bool $acceptableMaximumIncluded,
    ) {}

    public static function default(): self
    {
        return new self(25.0, 30.0, false);
    }

    public static function cornerstone(): self
    {
        return new self(20.0, 25.0, true);
    }

    public function isGood(float $percentage): bool
    {
        return $percentage <= $this->goodMaximum;
    }

    public function isAcceptable(float $percentage): bool
    {
        return $this->acceptableMaximumIncluded
            ? $percentage <= $this->acceptableMaximum
            : $percentage < $this->acceptableMaximum;
    }
}
