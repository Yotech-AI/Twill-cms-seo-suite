<?php

namespace TwillSeo\Analysis\Assessment;

/**
 * The list a result is filed under in the editor panel. Distinct from Rating
 * because the panel groups by "what should I do about this" while the rating
 * colours a single bullet.
 */
enum ResultCategory: string
{
    case Problems = 'problems';
    case Improvements = 'improvements';
    case Good = 'good';
    case Feedback = 'feedback';
    case Errors = 'errors';

    public static function fromRating(Rating $rating): self
    {
        return match ($rating) {
            Rating::Bad => self::Problems,
            Rating::Ok => self::Improvements,
            Rating::Good => self::Good,
            Rating::Feedback => self::Feedback,
            Rating::Error => self::Errors,
        };
    }
}
