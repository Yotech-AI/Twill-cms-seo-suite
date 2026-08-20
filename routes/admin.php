<?php

use Illuminate\Support\Facades\Route;
use TwillSeo\Http\Controllers\AnalyzeController;

/*
|--------------------------------------------------------------------------
| Twill SEO routes
|--------------------------------------------------------------------------
|
| Loaded by TwillSeoServiceProvider inside the Twill admin context:
| middleware [web, twill_auth:twill_users, impersonate, localization],
| prefix {admin_app_path}/seo, route name prefix {admin_route_name_prefix}seo.
| (e.g. twill.seo.*).
|
| The settings screen itself is the SeoSettings Twill singleton capsule
| (native form + media library) — this file only carries what is not a
| Twill module: the analyze endpoint and a bookmark-friendly redirect from
| the old /seo settings URL to the singleton.
|
*/

Route::get('/', function () {
    return redirect()->route(config('twill.admin_route_name_prefix', 'twill.').'seoSetting');
})->name('index');

Route::post('/analyze', AnalyzeController::class)
    ->name('analyze')
    ->middleware('throttle:'.config('twill-seo.analysis.throttle', '60,1'));
