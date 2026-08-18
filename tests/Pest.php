<?php

use A17\Twill\Models\Media;
use Illuminate\Support\Facades\Blade;
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

/**
 * Shared by HeadRenderTest and SchemaGraphTest (Task 7): renders the real
 * <x-twill-seo::head /> component through Blade::render(), exactly the way a
 * host layout would use it — see resources/views/head.blade.php plus the
 * component namespace registered in TwillSeoServiceProvider::boot().
 */
function renderHeadHtml(string $attributes = '', array $data = []): string
{
    return Blade::render("<x-twill-seo::head {$attributes} />", $data);
}

/**
 * Pulls one <meta> tag's content= value out by its identifying name=/property=
 * attribute, independent of attribute order (the view's own order is an
 * implementation detail no test should be coupled to).
 */
function metaContent(string $html, string $identifyingAttr, string $identifyingValue): ?string
{
    $pattern = '/<meta\s+(?=[^>]*'.preg_quote($identifyingAttr, '/').'="'.preg_quote($identifyingValue, '/').'")(?=[^>]*content="([^"]*)")[^>]*>/i';

    return preg_match($pattern, $html, $m) ? html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5) : null;
}

/**
 * Pulls one <link> tag's href= value out by rel= (and optionally hreflang=).
 */
function linkHref(string $html, string $rel, ?string $hreflang = null): ?string
{
    $pattern = $hreflang === null
        ? '/<link\s+(?=[^>]*rel="'.preg_quote($rel, '/').'")(?=[^>]*href="([^"]*)")[^>]*>/i'
        : '/<link\s+(?=[^>]*rel="'.preg_quote($rel, '/').'")(?=[^>]*hreflang="'.preg_quote($hreflang, '/').'")(?=[^>]*href="([^"]*)")[^>]*>/i';

    return preg_match($pattern, $html, $m) ? html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5) : null;
}

function titleTagContent(string $html): ?string
{
    return preg_match('#<title>(.*?)</title>#s', $html, $m) ? html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5) : null;
}

/**
 * Attaches a real Twill Media row to $model under $role/crop "default" — the
 * exact shape HasMedias::imageAsArray() reads (uuid/width/height on Media,
 * role/crop/crop_* on the mediables pivot). 'metadatas' is NOT NULL on the
 * pivot table (see the Twill default migration).
 *
 * A bare '{}' is not enough: imageAsArray() also calls imageAltText()/
 * imageCaption()/imageVideo(), each of which reads $metadatas-><field>-
 * >$locale off this same JSON (Media::getMetadata()) — with '{}', that
 * top-level <field> key does not exist, and PHP 8's "Undefined property"
 * warning on the resulting stdClass access gets converted into a real
 * exception under this suite's failOnWarning=true (verified empirically:
 * imageVideo() threw exactly that for a role otherwise resolved correctly).
 * Every locale this suite might render under needs a real, if empty, entry.
 */
function attachMedia(object $model, string $role, int $width, int $height): Media
{
    $media = Media::query()->create([
        'uuid' => 'media-'.$role.'-'.$width.'x'.$height.'-'.uniqid().'.jpg',
        'filename' => 'test.jpg',
        'width' => $width,
        'height' => $height,
    ]);

    $locales = array_unique([...array_values((array) config('translatable.locales', ['en'])), 'en', 'nl', 'de']);
    $perLocale = array_fill_keys($locales, null);

    $model->medias()->attach($media->id, [
        'role' => $role,
        'crop' => 'default',
        'metadatas' => json_encode(['altText' => $perLocale, 'caption' => $perLocale, 'video' => $perLocale]),
    ]);

    return $media;
}
