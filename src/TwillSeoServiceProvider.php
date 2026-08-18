<?php

namespace TwillSeo;

use A17\Twill\Facades\TwillNavigation;
use A17\Twill\View\Components\Navigation\NavigationLink;
use Illuminate\Support\Facades\Route;
use Yotech\TwillPluginSupport\TwillPluginServiceProvider;

/**
 * Drop-in service provider for the Twill SEO suite. Registering this provider
 * is the only wiring a host application needs — routes, navigation, views,
 * migrations and the shared Plugins-page entry are all self-registered from
 * the host's own Twill configuration, so the package adapts to a custom admin
 * prefix automatically.
 */
class TwillSeoServiceProvider extends TwillPluginServiceProvider
{
    /**
     * The package ships no Twill capsules, so skip the capsule directory scan
     * TwillPackageServiceProvider performs by default — it would look for
     * src/Twill/Capsules and find nothing.
     */
    protected $autoRegisterCapsules = false;

    public function register(): void
    {
        // Binds the shared Plugins-page registry/page-owner container keys.
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/twill-seo.php', 'twill-seo');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\DoctorCommand::class,
                Console\InstallCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        // Registers this package's manifest on the shared Plugins page.
        parent::boot();

        $this->registerPublishing();

        // A disabled install still shows up on the Plugins page (via
        // parent::boot() above) so the card can explain why nothing else is
        // live; nothing past this point should run.
        if (! config('twill-seo.enabled')) {
            return;
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'twill-seo');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerRoutes();
        $this->registerNavigation();
    }

    /**
     * Describe this package for the shared Plugins page.
     */
    protected function twillPlugin(): array
    {
        return [
            'name' => 'Twill SEO',
            'description' => 'Yoast-style SEO: content analysis with traffic lights, meta & social tags, schema.org and XML sitemaps.',
            'package' => 'yotech-ai/twill-cms-seo-suite',
            'route' => config('twill.admin_route_name_prefix', 'twill.').'seo.index',
        ];
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/twill-seo.php' => config_path('twill-seo.php'),
        ], 'twill-seo-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/twill-seo'),
        ], 'twill-seo-views');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'twill-seo-migrations');
    }

    protected function registerRoutes(): void
    {
        // A cached route file cannot be appended to at runtime; the host must
        // have already cached OUR routes into it before deploy.
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['web', 'twill_auth:twill_users', 'impersonate', 'localization'])
            ->prefix(rtrim(ltrim(config('twill.admin_app_path', 'admin'), '/'), '/').'/seo')
            ->name(config('twill.admin_route_name_prefix', 'twill.').'seo.')
            ->group(__DIR__.'/../routes/admin.php');
    }

    protected function registerNavigation(): void
    {
        // Never bind A17\Twill\TwillNavigation ourselves — the plugin-support
        // base class owns that swap (first plugin to register wins the page).
        TwillNavigation::addLink(
            NavigationLink::make()
                ->title('SEO')
                ->forRoute(config('twill.admin_route_name_prefix', 'twill.').'seo.index')
        );
    }
}
