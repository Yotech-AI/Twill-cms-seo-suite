<?php

namespace TwillSeo\Support;

/**
 * The traffic-light color and label for a cached 0-100 score. One place both
 * listing columns read from, so the boundaries can never drift between the
 * SEO column and the readability column — and the Vue panel (Task 6) mirrors
 * these same numbers rather than inventing its own.
 *
 * The bounds match SeoScoreAggregator's own bad/ok/good split exactly
 * (<=40, <=70, >70): a dot that disagreed with the score it sits next to
 * would be worse than no dot at all. 0 is deliberately carved out of that
 * range rather than falling into "<=40" — see color()'s own doc comment.
 */
final class ScoreRating
{
    private const BAD_UPPER_BOUND = 40;

    private const OK_UPPER_BOUND = 70;

    public const COLOR_GREY = '#b0b0b0';

    public const COLOR_RED = '#dc3232';

    public const COLOR_ORANGE = '#ee7c1b';

    public const COLOR_GREEN = '#7ad03a';

    /**
     * Null is not a bad score — it is the absence of one, and gets its own
     * neutral color rather than reading as a failing grade.
     *
     * Neither is 0: it is reserved by the engine for "not available", never a
     * real verdict. OverallScore::notAvailable() is the ONLY place a 0 is
     * ever constructed — SeoScoreAggregator floors every real score at 1
     * specifically so 0 stays unreachable there, and
     * ReadabilityPenaltyAggregator returns notAvailable() outright whenever
     * fewer than two assessments counted (a title with no body content is
     * the ordinary, everyday way to land here — not a rare edge case).
     * Coloring a 0 red would tell a brand new, not-yet-written page it has
     * already failed.
     */
    public static function color(?int $score): string
    {
        return match (true) {
            $score === null || $score === 0 => self::COLOR_GREY,
            $score <= self::BAD_UPPER_BOUND => self::COLOR_RED,
            $score <= self::OK_UPPER_BOUND => self::COLOR_ORANGE,
            default => self::COLOR_GREEN,
        };
    }

    /**
     * Null and 0 are both grey (see color()) but are worded differently: null
     * means the analysis has never run at all, while 0 means it ran and
     * explicitly had too little to judge — "not available" says that, "not
     * analyzed" would not.
     */
    public static function label(?int $score): string
    {
        return match (true) {
            $score === null => 'Not analyzed',
            $score === 0 => 'Not available',
            default => "{$score}/100",
        };
    }

    /**
     * The full listing-column dot: inline styles because the asset bundle
     * that would let a bare class render doesn't exist until Task 6.
     */
    public static function dot(?int $score): string
    {
        return sprintf(
            '<span style="display:inline-block;width:12px;height:12px;border-radius:50%%;background:%s" title="%s"></span>',
            self::color($score),
            self::label($score),
        );
    }
}
