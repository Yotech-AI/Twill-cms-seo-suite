<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Html\Link;
use TwillSeo\Analysis\Html\LinkScope;

/**
 * @implements Research<LinkStatisticsResult>
 */
final class LinkStatistics implements Research
{
    public function run(AnalysisContext $context): LinkStatisticsResult
    {
        $internal = $context->content->linksInScope(LinkScope::Internal);
        $external = $context->content->linksInScope(LinkScope::External);

        return new LinkStatisticsResult(
            count($internal),
            self::countNofollow($internal),
            count($external),
            self::countNofollow($external),
        );
    }

    /**
     * @param  list<Link>  $links
     */
    private static function countNofollow(array $links): int
    {
        return count(array_filter($links, fn (Link $link) => $link->isNofollow));
    }
}
