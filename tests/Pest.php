<?php

use TwillSeo\Tests\TestCase;

// Feature only: tests/Unit stays containerless (pure PHP engine tests land in
// a later task) so it never pays for booting Testbench + Twill + sqlite.
uses(TestCase::class)->in('Feature');

/**
 * Twill's configured admin base path (e.g. "/admin"), read from config rather
 * than hardcoded. Bare path, not a package URL builder — PluginsPageTest uses
 * it directly to reach the shared /plugins page, which lives outside this
 * package's own /seo prefix; twillSeoUrl() below builds on top of it for ours.
 */
function twillAdmin(): string
{
    return '/'.trim((string) config('twill.admin_app_path', 'admin'), '/');
}

/**
 * Build a package admin URL from Twill's configured admin path rather than
 * hardcoding "/admin/seo". A host is free to rename that path, and the
 * package follows it — so the tests have to as well.
 */
function twillSeoUrl(string $path = ''): string
{
    return rtrim(twillAdmin().'/seo/'.ltrim($path, '/'), '/');
}
