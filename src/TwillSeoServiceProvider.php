<?php

namespace TwillSeo;

use A17\Twill\Facades\TwillNavigation;
use A17\Twill\View\Components\Navigation\NavigationLink;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use TwillSeo\Analysis\AnalysisRunner;
use TwillSeo\Analysis\Assessor\AssessorFactory;
use TwillSeo\Analysis\Html\HtmlParser;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Contracts\SeoContentResolver;
use TwillSeo\Http\Controllers\AssetController;
use TwillSeo\PluginPage\TwillPluginServiceProvider;
use TwillSeo\Services\KeyphraseUsage;
use TwillSeo\Services\Meta\SeoResolver;
use TwillSeo\Services\ModelRegistry;
use TwillSeo\Services\Resolvers\RenderedBlocksResolver;
use TwillSeo\Services\Resolvers\UrlResolver;
use TwillSeo\Services\Settings\SeoSettings;
use TwillSeo\Services\Sitemap\SitemapBuilder;
use TwillSeo\Services\Sitemap\SitemapCache;
use TwillSeo\Support\TranslatorMessageRenderer;

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
     * The SeoSettings singleton (the native settings screen) lives in
     * src/Twill/Capsules — TwillPackageServiceProvider's capsule scan
     * registers its model, repository, controller and routes by convention.
     */
    protected $autoRegisterCapsules = true;

    public function register(): void
    {
        // Binds the shared Plugins-page registry/page-owner container keys.
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/twill-seo.php', 'twill-seo');

        $this->registerAnalysisServices();

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\DoctorCommand::class,
                Console\InstallCommand::class,
                Console\MigrateLegacyCommand::class,
            ]);
        }
    }

    /**
     * Bindings only — no side effects, so they exist even when the package
     * is disabled (config('twill-seo.enabled') only gates boot()'s routes,
     * views, migrations and navigation).
     */
    protected function registerAnalysisServices(): void
    {
        $this->app->singleton(ModelRegistry::class);

        // Stateless given its own (singleton) ModelRegistry dependency, and
        // shared by PaperFactory, ScoreCache and (Task 7) the head-rendering
        // meta layer — one instance per request is enough.
        $this->app->singleton(UrlResolver::class);

        // Memoizes the twill_seo_settings row for the life of the request
        // (see its own doc comment) — must be a singleton for that memo to
        // mean anything.
        $this->app->singleton(SeoSettings::class);

        // Stateless orchestration over the singletons above; SeoManager is
        // the one that genuinely needs per-request identity (see its own
        // doc comment), so this is a singleton mainly for consistency and
        // to avoid rebuilding it once per Head render + once per
        // TwillSeo::for()/page() call in the same request.
        $this->app->singleton(SeoResolver::class);

        $this->app->singleton(SeoManager::class);

        // The default resolver for any registry entry with no `content`
        // class of its own; RenderedBlocksResolver's own ModelRegistry
        // dependency resolves through the singleton just bound above.
        $this->app->bind(SeoContentResolver::class, RenderedBlocksResolver::class);

        // Stateless (see SitemapBuilder's own doc comment); singleton for
        // the same reason as UrlResolver/SeoResolver above. Bound outside
        // the config('twill-seo.enabled') gate below, like every other
        // service here, because HandleSeo::afterSaveHandleSeo and
        // HasSeo::bootHasSeo call SitemapCache::forgetFor() unconditionally
        // on every save/delete of a registered model, regardless of whether
        // this package's own routes/views ever booted.
        $this->app->singleton(SitemapBuilder::class);
        $this->app->singleton(SitemapCache::class);

        $this->app->singleton(AnalysisRunner::class, function ($app) {
            return new AnalysisRunner(
                new HtmlParser,
                LanguagePackRegistry::withDefaults(),
                new AssessorFactory,
                $app->make(TranslatorMessageRenderer::class),
                $app->make(KeyphraseUsage::class),
            );
        });
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
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'twill-seo');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Fulfils the Task 1 ruling deferred to this task: <x-twill-seo::head />
        // resolves to TwillSeo\View\Components\Head.
        Blade::componentNamespace('TwillSeo\\View\\Components', 'twill-seo');

        $this->registerRoutes();
        $this->registerPublicRoutes();
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
            'route' => config('twill.admin_route_name_prefix', 'twill.').'seoSetting',
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

        // Optional: the assets are served from a package route by default
        // (registerRoutes() below) and never need publishing. Publishing
        // them only shortens the URL — AssetController::url() prefers a
        // published copy automatically once one exists.
        $this->publishes([
            __DIR__.'/../resources/dist' => public_path('vendor/twill-seo'),
        ], 'twill-seo-assets');
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

        // Built JS/CSS, served with a far-future cache header and an ETag so
        // no publish step is required and the files can never go stale. A
        // separate, lighter web-only group (no twill_auth/impersonate/
        // localization): every other admin page needs this JS to even
        // render, so it cannot itself sit behind the auth gate it would
        // otherwise need it to pass first. Mirrors the twill-cms-ai-assistent
        // sibling's own asset route exactly.
        Route::middleware(['web'])
            ->prefix(rtrim(ltrim(config('twill.admin_app_path', 'admin'), '/'), '/').'/seo')
            ->name(config('twill.admin_route_name_prefix', 'twill.').'seo.')
            ->group(function (): void {
                Route::get('asset/{file}', AssetController::class)
                    ->where('file', 'twill-seo\.(iife\.js|css)')
                    ->name('asset');
            });
    }

    /**
     * GET /sitemap.xml + GET /sitemap-{type}-{page}.xml — root-level, no
     * admin prefix. Registered unconditionally whenever the package's routes
     * are registered at all (same enabled/routesAreCached gating as
     * registerRoutes() above): SitemapController, not route registration,
     * decides per-request whether the sitemap feature is on, since that
     * answer lives in the DB-backed settings row (SeoSettings::feature()),
     * and a fresh install's route registration must survive running before
     * `migrate` ever has.
     *
     * No middleware at all — not even 'web' — because a sitemap is a public,
     * cacheable XML document with no session/cookies/CSRF concerns, and
     * crawlers request it far more often than browsers do. Verified under
     * Testbench (SitemapTest) that a route with zero middleware groups
     * responds normally: no missing-session-driver error, no missing-
     * encrypter error, no CSRF token requirement — 'web' exists to protect
     * stateful features this route deliberately has none of.
     */
    protected function registerPublicRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::name('twill-seo.sitemap.')
            ->group(__DIR__.'/../routes/public.php');
    }

    /**
     * Off by default: the shared Plugins page is where a plugin lives, and
     * listing every installed plugin in the admin's main navigation as well
     * defeats the point of having that page. A host whose editors work in
     * the SEO screens constantly can set twill-seo.ui.navigation_link to
     * true and get a top-level entry back. (Family rule — the redirects and
     * AI-assistant packages follow the same convention.)
     */
    protected function registerNavigation(): void
    {
        if (! config('twill-seo.ui.navigation_link', false)) {
            return;
        }

        // Never bind A17\Twill\TwillNavigation ourselves — the plugin-support
        // base class owns that swap (first plugin to register wins the page).
        TwillNavigation::addLink(
            NavigationLink::make()
                ->title('SEO')
                ->forRoute(config('twill.admin_route_name_prefix', 'twill.').'seoSetting')
        );
    }
}
