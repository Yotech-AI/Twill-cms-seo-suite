<?php

namespace TwillSeo\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class SettingsPageController extends Controller
{
    public function index(): View
    {
        return view('twill-seo::settings');
    }
}
