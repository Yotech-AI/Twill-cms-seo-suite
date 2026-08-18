<?php

namespace TwillSeo\Http\Controllers;

use Composer\InstalledVersions;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the package's built frontend assets (resources/dist/twill-seo.iife.js
 * + twill-seo.css) straight from the package itself.
 *
 * Shipping them through a route rather than requiring a publish step means an
 * adopter can never end up running a stale copy of the JS after a package
 * upgrade, and needs no JS toolchain at all to use the panel (see the Task 6
 * brief). The URL carries a version query string, so the far-future
 * Cache-Control header below is safe. A host that prefers real files can
 * still run `vendor:publish --tag=twill-seo-assets`; url() below prefers a
 * published copy when one exists (see registerPublishing() in the provider),
 * and the Blade partial always goes through url() rather than hardcoding a
 * path — this class is the single place either fact lives.
 *
 * Pattern copied from the twill-cms-ai-assistent sibling's own
 * AssetController, with its version() fallback improved: that sibling falls
 * back to a static "dev" string when Composer's InstalledVersions has no
 * version for the package (its own path-repository dev install), which never
 * busts the browser cache across a local rebuild. This one falls back to the
 * built file's own mtime instead, so every `npm run build` during local
 * development gets a fresh query string immediately.
 */
class AssetController extends Controller
{
    private const PACKAGE_NAME = 'yotech-ai/twill-cms-seo-suite';

    private const TYPES = [
        'twill-seo.iife.js' => 'text/javascript; charset=utf-8',
        'twill-seo.css' => 'text/css; charset=utf-8',
    ];

    public function __invoke(Request $request, string $file): Response
    {
        if (! isset(self::TYPES[$file])) {
            abort(404);
        }

        $path = self::distPath($file);

        if (! is_file($path)) {
            abort(404);
        }

        $response = new BinaryFileResponse($path, headers: [
            'Content-Type' => self::TYPES[$file],
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);

        $response->setAutoEtag();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * URL for one built asset, preferring a published copy in public/ when
     * the host ran `vendor:publish --tag=twill-seo-assets`.
     */
    public static function url(string $file): string
    {
        $published = public_path('vendor/twill-seo/'.$file);

        if (is_file($published)) {
            return asset('vendor/twill-seo/'.$file).'?v='.self::version($published);
        }

        return route(
            config('twill.admin_route_name_prefix', 'twill.').'seo.asset',
            ['file' => $file, 'v' => self::version(self::distPath($file))]
        );
    }

    private static function distPath(string $file): string
    {
        return __DIR__.'/../../../resources/dist/'.$file;
    }

    /**
     * The installed package version when Composer knows one (a real tagged
     * release, or any install with proper version metadata); otherwise the
     * given file's own mtime, so a local `npm run build` during development —
     * with no tagged version to bump — still busts the cache on every
     * rebuild instead of hiding behind a static fallback string forever.
     */
    private static function version(string $fileForMtimeFallback): string
    {
        if (InstalledVersions::isInstalled(self::PACKAGE_NAME)) {
            $version = InstalledVersions::getPrettyVersion(self::PACKAGE_NAME);

            if ($version !== null) {
                return $version;
            }
        }

        return (string) (@filemtime($fileForMtimeFallback) ?: 'dev');
    }
}
