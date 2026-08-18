<?php

namespace TwillSeo\Contracts;

/**
 * What SeoContentResolver::resolve() hands back: the HTML plus where it came
 * from. The source travels with the html because the endpoint's `meta`
 * response and ScoreCache's own bookkeeping both want to say which content
 * a report was built from, and re-deriving that after the fact (did the
 * blocks or the content_fields actually contribute anything?) would just be
 * re-running the same decision a second time.
 */
final readonly class ResolvedContent
{
    /**
     * @param  string  $source  one of: rendered_blocks | content_fields | mixed | empty
     */
    public function __construct(
        public string $html,
        public string $source,
    ) {}
}
