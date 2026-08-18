<?php

namespace TwillSeo\Analysis\Score;

/**
 * A section's aggregate: a 0-100 score plus its band. Score 0 is reserved for
 * NotAvailable, so a judged section never scores below 1.
 */
final readonly class OverallScore
{
    public function __construct(
        public int $score,
        public OverallRating $rating,
    ) {}

    public static function notAvailable(): self
    {
        return new self(0, OverallRating::NotAvailable);
    }
}
