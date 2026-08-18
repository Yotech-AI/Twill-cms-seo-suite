<?php

use Illuminate\Support\Facades\Route;
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
