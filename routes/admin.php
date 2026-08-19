<?php

use Illuminate\Support\Facades\Route;
use TwillSeo\Http\Controllers\AnalyzeController;
use TwillSeo\Http\Controllers\MediaSearchController;
use TwillSeo\Http\Controllers\SettingsController;
use TwillSeo\Http\Controllers\SettingsPageController;

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
*/

Route::get('/', [SettingsPageController::class, 'index'])->name('index');

Route::post('/analyze', AnalyzeController::class)
    ->name('analyze')
    ->middleware('throttle:'.config('twill-seo.analysis.throttle', '60,1'));

Route::get('/settings', [SettingsController::class, 'show'])->name('settings.show');

Route::put('/settings', [SettingsController::class, 'update'])
    ->name('settings.update')
    ->middleware('throttle:'.config('twill-seo.settings.throttle', '30,1'));

Route::get('/media', MediaSearchController::class)->name('media.index');
