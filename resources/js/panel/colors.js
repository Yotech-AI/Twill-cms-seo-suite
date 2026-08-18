/**
 * Mirrors TwillSeo\Support\ScoreRating exactly — the one place both the
 * listing-column dots (PHP) and this panel (JS) read their traffic-light
 * boundaries from conceptually. Keep the numbers and hex values below in
 * sync with src/Support/ScoreRating.php if either ever changes; a dot here
 * that disagreed with the score it sits next to would be worse than no dot
 * at all.
 *
 * Note the boundary a first read of the brief could get backwards: a score
 * of exactly 0 is BAD (red, 0 <= 40), not "not available" — only a null/
 * undefined score (never analyzed yet) is the neutral grey.
 */
export const DEFAULT_COLORS = Object.freeze({
    red: '#dc3232',
    orange: '#ee7c1b',
    green: '#7ad03a',
    grey: '#b0b0b0',
});

const BAD_UPPER_BOUND = 40;
const OK_UPPER_BOUND = 70;

/** Resolves the panel's color set: config.colors overrides fall back individually to the defaults above. */
export function resolveColors(config) {
    const overrides = (config && config.colors) || {};
    return {
        red: overrides.red || DEFAULT_COLORS.red,
        orange: overrides.orange || DEFAULT_COLORS.orange,
        green: overrides.green || DEFAULT_COLORS.green,
        grey: overrides.grey || DEFAULT_COLORS.grey,
    };
}

/** Mirrors ScoreRating::color(): null is "not analyzed" (grey), never a failing grade. */
export function colorForScore(score, colors = DEFAULT_COLORS) {
    if (score === null || score === undefined) return colors.grey;
    if (score <= BAD_UPPER_BOUND) return colors.red;
    if (score <= OK_UPPER_BOUND) return colors.orange;
    return colors.green;
}

/** Mirrors ScoreRating::label(): "Not analyzed" for null, else "N/100". */
export function labelForScore(score) {
    return score === null || score === undefined ? 'Not analyzed' : `${score}/100`;
}

/**
 * Mirrors TwillSeo\Analysis\Assessment\Rating's four judged values (bad/ok/
 * good) plus feedback/error, which both read as neutral grey — a verdict
 * about the analysis itself, not a judgement on the content (see
 * AssessmentResult's own doc comment on countsTowardScore).
 */
export function colorForRating(rating, colors = DEFAULT_COLORS) {
    switch (rating) {
        case 'bad':
            return colors.red;
        case 'ok':
            return colors.orange;
        case 'good':
            return colors.green;
        default:
            return colors.grey; // 'feedback' | 'error' | anything unrecognized
    }
}
