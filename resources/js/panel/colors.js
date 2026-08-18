/**
 * Mirrors TwillSeo\Support\ScoreRating exactly — the one place both the
 * listing-column dots (PHP) and this panel (JS) read their traffic-light
 * boundaries from conceptually. Keep the numbers and hex values below in
 * sync with src/Support/ScoreRating.php if either ever changes; a dot here
 * that disagreed with the score it sits next to would be worse than no dot
 * at all.
 *
 * A score of exactly 0 is GREY, not red: the engine reserves 0 for "not
 * available", never a real bad-but-scored verdict —
 * OverallScore::notAvailable() is the only place a 0 is ever constructed
 * (SeoScoreAggregator floors every real score at 1 specifically so 0 stays
 * unreachable there; ReadabilityPenaltyAggregator returns notAvailable()
 * outright whenever fewer than two assessments counted, which a title with
 * no body content — the ordinary state of a freshly created item — hits
 * every time). Coloring it red told a brand new, not-yet-written page it
 * had already failed; this was a real bug here until it was corrected
 * alongside ScoreRating::color() itself.
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

/**
 * Mirrors ScoreRating::color(): null (never analyzed) and 0 (analyzed, not
 * available — see this file's header) are both grey; only a real 1-100
 * score bands into red/orange/green.
 */
export function colorForScore(score, colors = DEFAULT_COLORS) {
    if (score === null || score === undefined || score === 0) return colors.grey;
    if (score <= BAD_UPPER_BOUND) return colors.red;
    if (score <= OK_UPPER_BOUND) return colors.orange;
    return colors.green;
}

/**
 * Mirrors ScoreRating::label(): worded differently for null ("Not
 * analyzed" — never ran) versus 0 ("Not available" — ran, nothing to
 * judge), even though both share the same grey dot.
 */
export function labelForScore(score) {
    if (score === null || score === undefined) return 'Not analyzed';
    if (score === 0) return 'Not available';
    return `${score}/100`;
}

/**
 * Mirrors TwillSeo\Analysis\Assessment\Rating's four judged values (bad/ok/
 * good) plus feedback/error, which both read as neutral grey — a verdict
 * about the analysis itself, not a judgement on the content (see
 * AssessmentResult's own doc comment on countsTowardScore).
 *
 * Also correct, unchanged, for a ScoreSection's OverallRating string
 * ('not-available'|'bad'|'ok'|'good' — report.seo.rating /
 * report.readability.rating): the two enums share the same bad/ok/good
 * spelling, and OverallRating's one "no verdict" value, 'not-available',
 * falls through to the same default (grey) branch below as Rating's two do.
 * colorForSection() is what actually calls this for a section; kept as one
 * function rather than two identical switches.
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
            return colors.grey; // 'feedback' | 'error' | 'not-available' | anything unrecognized
    }
}

/**
 * Resolves a ScoreChips dot's color for one section (SEO or readability),
 * preferring the engine's own authoritative OverallRating string —
 * report.seo.rating / report.readability.rating, present on every live
 * analyze response — over re-deriving a color from the bare number whenever
 * one is available. Only a cached `initial` value, shown before the first
 * live response of this page load has arrived, has no rating to prefer:
 * SeoEntryTranslation persists just the raw seo_score/readability_score
 * columns, never the rating alongside them, so that case falls back to
 * colorForScore() — itself 0/null-aware, so it still never reads a
 * not-available 0 as red.
 */
export function colorForSection(score, rating, colors = DEFAULT_COLORS) {
    return rating ? colorForRating(rating, colors) : colorForScore(score, colors);
}
