<?php

namespace TwillSeo\Analysis\Assessment;

/**
 * The traffic light shown next to a single assessment result.
 *
 * Scores are not a plain 0-9 scale: two values are sentinels (-1 signals the
 * assessment could not run, 0 signals it has feedback rather than a verdict)
 * and assessments may return large negative penalties to drag the aggregate
 * down. fromScore() therefore has to match the sentinels before it applies any
 * range test.
 */
enum Rating: string
{
    case Error = 'error';
    case Feedback = 'feedback';
    case Bad = 'bad';
    case Ok = 'ok';
    case Good = 'good';

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score === -1 => self::Error,
            $score === 0 => self::Feedback,
            // Catches every penalty score (-999, -50, -20, -10) as well as 1-4.
            $score <= 4 => self::Bad,
            $score <= 7 => self::Ok,
            default => self::Good,
        };
    }
}
