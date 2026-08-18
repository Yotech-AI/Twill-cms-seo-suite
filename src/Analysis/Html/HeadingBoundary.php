<?php

namespace TwillSeo\Analysis\Html;

/**
 * Where a heading interrupts the run of paragraphs.
 *
 * ParsedContent keeps paragraphs and headings in separate lists, which is what
 * every assessment but one wants. The exception is the section length check,
 * which needs to know which paragraphs belong to which heading — so rather than
 * interleaving the two lists, the parser records the seams between them.
 */
final readonly class HeadingBoundary
{
    /**
     * @param  int  $level  1-6, as in h1..h6
     * @param  int  $paragraphIndex  the index in ParsedContent::$paragraphs of the first
     *                               paragraph that follows this heading; equal to the
     *                               paragraph count when the heading is last
     */
    public function __construct(public int $level, public int $paragraphIndex) {}
}
