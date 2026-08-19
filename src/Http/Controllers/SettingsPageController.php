<?php

namespace TwillSeo\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use TwillSeo\Services\Settings\SettingsPayload;

class SettingsPageController extends Controller
{
    public function __construct(private readonly SettingsPayload $payload) {}

    /**
     * Bootstraps the Vue settings app with the full current payload
     * server-side (`bootstrap` below) rather than an initial-fetch flag —
     * unlike the analysis panel's per-keystroke `initial` snapshot, a
     * settings row is already the authoritative current state at render
     * time, so there is nothing a follow-up GET would learn that this
     * request doesn't already know. GET/PUT {admin}/seo/settings (see
     * SettingsController) still exist as their own real endpoints — every
     * save goes through PUT, and GET remains a stable read contract of its
     * own — the Vue app simply never needs to call GET itself on mount.
     */
    public function index(): View
    {
        $config = [
            'endpoints' => [
                'show' => route(config('twill.admin_route_name_prefix', 'twill.').'seo.settings.show'),
                'update' => route(config('twill.admin_route_name_prefix', 'twill.').'seo.settings.update'),
                'media' => route(config('twill.admin_route_name_prefix', 'twill.').'seo.media.index'),
            ],
            'csrf' => csrf_token(),
            'bootstrap' => $this->payload->build(),
        ];

        return view('twill-seo::settings', ['config' => $config]);
    }
}
