<?php

namespace TwillSeo\Analysis\Html;

/**
 * Where a link points relative to the paper being analysed. "Other" covers
 * links that go nowhere on the web — fragments, mail, phone, script — which
 * count as neither an internal nor an outbound link.
 */
enum LinkScope: string
{
    case Internal = 'internal';
    case External = 'external';
    case Other = 'other';

    /**
     * Schemes that never address another web page, so they are neither an
     * internal nor an external link.
     */
    private const NON_PAGE_SCHEMES = ['mailto', 'tel', 'sms', 'javascript', 'data'];

    public static function forHref(string $href, string $permalink = ''): self
    {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '#')) {
            return self::Other;
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);

        if (is_string($scheme) && in_array(strtolower($scheme), self::NON_PAGE_SCHEMES, true)) {
            return self::Other;
        }

        $host = parse_url($href, PHP_URL_HOST);

        // No host means a relative href, which can only resolve against this
        // same site.
        if (! is_string($host) || $host === '') {
            return self::Internal;
        }

        $ownHost = parse_url($permalink, PHP_URL_HOST);

        // With no permalink to compare against there is no "own host", so an
        // absolute URL is treated as leaving the site.
        if (! is_string($ownHost) || $ownHost === '') {
            return self::External;
        }

        return self::normalizeHost($host) === self::normalizeHost($ownHost) ? self::Internal : self::External;
    }

    /**
     * Hosts are compared without case and without the www prefix: a link to
     * www.example.com from example.com is still a link to your own site.
     */
    private static function normalizeHost(string $host): string
    {
        $host = strtolower($host);

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
