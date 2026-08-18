<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Html\HeadingBoundary;

/**
 * How many words the reader gets between one subheading and the next.
 *
 * A section is a run of paragraphs, and the run *before* the first subheading
 * is one of them. That is a deliberate divergence from judging only what
 * follows a heading — an unbroken opening is exactly as hard to read as an
 * unbroken middle. See docs/analysis.md.
 *
 * @implements Research<list<int>>
 */
final class SubheadingSectionLengths implements Research
{
    /** H1 titles the page and H4 and below sit inside a section rather than starting one. */
    private const SUBHEADING_LEVELS = [2, 3];

    /**
     * @return list<int>
     */
    public function run(AnalysisContext $context): array
    {
        $lengths = $context->research(ParagraphLengths::class);

        $starts = array_map(
            fn (HeadingBoundary $boundary) => $boundary->paragraphIndex,
            array_filter(
                $context->content->headingBoundaries,
                fn (HeadingBoundary $boundary) => in_array($boundary->level, self::SUBHEADING_LEVELS, true),
            ),
        );

        $sections = [];
        $start = 0;

        foreach ([...array_values($starts), count($lengths)] as $end) {
            $section = array_slice($lengths, $start, $end - $start);
            $start = $end;

            // Two headings in a row leave an empty run between them, which is
            // not a section anyone reads.
            if ($section !== []) {
                $sections[] = array_sum($section);
            }
        }

        return $sections;
    }
}
