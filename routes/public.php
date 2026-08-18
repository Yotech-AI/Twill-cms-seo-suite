<?php

use Illuminate\Support\Facades\Route;
use TwillSeo\Http\Controllers\SitemapController;

/*
|--------------------------------------------------------------------------
| Twill SEO public routes
|--------------------------------------------------------------------------
|
| Loaded by TwillSeoServiceProvider::registerPublicRoutes() OUTSIDE the admin
| group: root-level paths, no prefix, no middleware at all (see that method's
| own doc comment for why). Route names get the group prefix
| "twill-seo.sitemap." (twill-seo.sitemap.index / twill-seo.sitemap.show) —
| a fixed literal, unlike the admin routes' configurable
| {admin_route_name_prefix}seo. prefix, since these are not part of the
| admin namespace at all.
|
*/

Route::get(config('twill-seo.sitemap.path', 'sitemap.xml'), [SitemapController::class, 'index'])
    ->name('index');

Route::get('sitemap-{type}-{page}.xml', [SitemapController::class, 'show'])
    ->where(['type' => '[a-z0-9_-]+', 'page' => '[0-9]+'])
    ->name('show');
