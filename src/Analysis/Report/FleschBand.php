<?php

namespace TwillSeo\Analysis\Report;

/**
 * What a reading ease score means for a reader.
 *
 * The panel shows the band rather than the number, because "fairly difficult"
 * is advice and "51.3" is trivia. The values are stable strings the UI
 * translates; the boundaries are the published Flesch ones.
 */
enum FleschBand: string
{
    case VeryEasy = 'very_easy';
    case Easy = 'easy';
    case FairlyEasy = 'fairly_easy';
    case Standard = 'standard';
    case FairlyDifficult = 'fairly_difficult';
    case Difficult = 'difficult';
    case VeryDifficult = 'very_difficult';

    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 90 => self::VeryEasy,
            $score >= 80 => self::Easy,
            $score >= 70 => self::FairlyEasy,
            $score >= 60 => self::Standard,
            $score >= 50 => self::FairlyDifficult,
            // The one twenty point band: the published scale skips a "somewhat
            // difficult" step between 30 and 50.
            $score >= 30 => self::Difficult,
            default => self::VeryDifficult,
        };
    }
}
