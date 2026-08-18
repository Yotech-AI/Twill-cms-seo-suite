/**
 * Groups a ScoreSection's flat `results` array (report.seo.results or
 * report.readability.results — see AnalyzeController's response contract)
 * into the panel's category buckets, in a fixed display order.
 *
 * Mirrors TwillSeo\Analysis\Assessment\ResultCategory's five values. The
 * brief spells out problems/improvements/good/feedback explicitly; `errors`
 * is a fifth value the enum defines (ResultCategory::fromRating maps
 * Rating::Error to it) but that no assessment currently produces — it is
 * included anyway, styled like `feedback`, purely so a future assessment
 * that DOES fail outright degrades to a visible (if quietly labelled) group
 * instead of silently vanishing from the panel.
 *
 * Deliberate reading of "only when present": the brief says this explicitly
 * for `feedback` only, but this file applies it to every category — an empty
 * "Problems (0)" group on a page with nothing wrong would read as a bug, not
 * a compliment. A category with zero results for this report simply isn't
 * rendered.
 */
export const RESULT_GROUPS = Object.freeze([
    { category: 'problems', label: 'Problems', colorKey: 'red', defaultOpen: true },
    { category: 'improvements', label: 'Improvements', colorKey: 'orange', defaultOpen: true },
    { category: 'good', label: 'Good', colorKey: 'green', defaultOpen: false },
    { category: 'feedback', label: 'Feedback', colorKey: 'grey', defaultOpen: false },
    { category: 'errors', label: 'Not checked', colorKey: 'grey', defaultOpen: false },
]);

/**
 * @param {Array<{category: string}>} results
 * @returns {Array<{category: string, label: string, colorKey: string, defaultOpen: boolean, results: Array}>}
 */
export function groupResults(results) {
    const list = Array.isArray(results) ? results : [];

    return RESULT_GROUPS.map((group) => ({
        ...group,
        results: list.filter((result) => result && result.category === group.category),
    })).filter((group) => group.results.length > 0);
}
