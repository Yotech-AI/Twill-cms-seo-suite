<?php

namespace TwillSeo\Services\Schema;

/**
 * The @id scheme every built-in GraphPiece uses, centralized so a piece that
 * REFERENCES another node (e.g. WebSitePiece's publisher, ArticlePiece's
 * isPartOf) can never quietly drift from how that other piece computed its
 * own @id — the flat, cross-reference-by-@id graph the brief calls for only
 * holds together if every piece agrees on these strings byte for byte.
 */
final class SchemaIds
{
    public static function entity(SchemaContext $context): string
    {
        $suffix = $context->settings->entityType() === 'person' ? 'person' : 'organization';

        return rtrim($context->siteUrl, '/').'#'.$suffix;
    }

    public static function website(SchemaContext $context): string
    {
        return rtrim($context->siteUrl, '/').'#website';
    }

    /**
     * Every page-scoped id below is keyed off the CURRENT page's own URL
     * (canonical, falling back to the resolved url) rather than the site
     * root — WebPage, Article, BreadcrumbList and PrimaryImage are all
     * per-page nodes. A page with no resolvable URL at all degrades to just
     * the bare fragment ('#webpage' etc.) — a valid, if less useful,
     * relative id rather than an error.
     */
    public static function pageUrl(SchemaContext $context): string
    {
        return $context->pageSeo->canonicalUrl ?? $context->pageSeo->url ?? '';
    }

    public static function webpage(SchemaContext $context): string
    {
        return self::pageUrl($context).'#webpage';
    }

    public static function article(SchemaContext $context): string
    {
        return self::pageUrl($context).'#article';
    }

    public static function breadcrumb(SchemaContext $context): string
    {
        return self::pageUrl($context).'#breadcrumb';
    }

    public static function primaryImage(SchemaContext $context): string
    {
        return self::pageUrl($context).'#primaryimage';
    }
}
