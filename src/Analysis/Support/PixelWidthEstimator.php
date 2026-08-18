<?php

namespace TwillSeo\Analysis\Support;

/**
 * Approximates how wide a string renders in a search result title.
 *
 * A fallback, not a measurement. When the editor is open the browser measures
 * the real title and that number is authoritative; this exists for the times
 * there is no browser — an API call, a queued re-analysis, a CLI run. Character
 * classes rather than a real font metric table: the classes below reproduce
 * Arial/Arimo closely enough for a 600px budget, and a full metric table would
 * be a lot of data for an answer that is about to be overwritten by a real
 * measurement anyway.
 */
final class PixelWidthEstimator
{
    /** Search results render titles in Arial (Arimo on Linux). */
    public const REFERENCE_FONT = 'Arial';

    public const REFERENCE_FONT_SIZE_PX = 18;

    /** How close this is meant to get to a real measurement. */
    public const ACCURACY_TARGET_PERCENT = 10;

    private const NARROW = ['i', 'j', 'l', '!', '|', "'", '.', ',', ':', ';'];

    private const SEMI_NARROW = ['f', 't', 'r', '(', ')', '[', ']', '-'];

    /** The lower half of the "most lowercase" band. */
    private const NARROW_LOWERCASE = ['c', 'k', 's', 'v', 'x', 'z'];

    private const WIDE_LOWERCASE = ['m', 'w'];

    private const WIDE_UPPERCASE = ['M', 'W'];

    /**
     * Blocks whose characters render at full width — roughly one em rather
     * than the half-em a latin letter takes.
     */
    private const FULL_WIDTH_RANGES = [
        [0x1100, 0x115F],
        [0x2E80, 0xA4CF],
        [0xAC00, 0xD7A3],
        [0xF900, 0xFAFF],
        [0xFE30, 0xFE6F],
        [0xFF00, 0xFF60],
        [0xFFE0, 0xFFE6],
        [0x20000, 0x3FFFD],
    ];

    public static function estimate(string $text): int
    {
        $width = 0.0;

        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $character) {
            $width += self::widthOf($character);
        }

        return (int) round($width);
    }

    private static function widthOf(string $character): float
    {
        return match (true) {
            $character === ' ' => 5.0,
            in_array($character, self::NARROW, true) => 5.0,
            in_array($character, self::SEMI_NARROW, true) => 6.0,
            in_array($character, self::WIDE_LOWERCASE, true) => 15.0,
            in_array($character, self::WIDE_UPPERCASE, true) => 17.0,
            in_array($character, self::NARROW_LOWERCASE, true) => 9.0,
            preg_match('/^\p{Nd}$/u', $character) === 1 => 10.0,
            // An accent renders on top of the letter before it.
            preg_match('/^\p{Mn}$/u', $character) === 1 => 0.0,
            self::isFullWidth($character) => 18.0,
            preg_match('/^\p{Ll}$/u', $character) === 1 => 10.0,
            preg_match('/^\p{Lu}$/u', $character) === 1 => 12.0,
            default => 10.0,
        };
    }

    private static function isFullWidth(string $character): bool
    {
        $codepoint = mb_ord($character, 'UTF-8');

        if ($codepoint === false) {
            return false;
        }

        foreach (self::FULL_WIDTH_RANGES as [$from, $to]) {
            if ($codepoint >= $from && $codepoint <= $to) {
                return true;
            }
        }

        return false;
    }
}
