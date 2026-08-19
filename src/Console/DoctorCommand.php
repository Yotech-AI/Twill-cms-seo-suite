<?php

namespace TwillSeo\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;
use Throwable;
use TwillSeo\Analysis\AnalysisRunner;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Http\Controllers\SitemapController;
use TwillSeo\Models\Behaviors\HasSeo;
use TwillSeo\Models\SeoEntry;
use TwillSeo\Models\SeoEntryTranslation;
use TwillSeo\Models\SeoSetting;
use TwillSeo\PluginPage\TwillPluginServiceProvider;
use TwillSeo\Repositories\Behaviors\HandleSeo;
use TwillSeo\Services\Form\SeoFields;
use TwillSeo\Services\ModelRegistry;
use TwillSeo\Services\Resolvers\UrlResolver;
use TwillSeo\Services\Settings\SeoSettings;

/**
 * Real diagnostics for an install: wiring, config, every registered model,
 * the settings row, feature-dependent routes, built assets and a live engine
 * smoke test. Table output; exits 1 the moment any check FAILs, 0 otherwise
 * (a WARN never fails the run — see each check's own reasoning for why it is
 * one severity or the other).
 */
class DoctorCommand extends Command
{
    protected $signature = 'twill-seo:doctor';

    protected $description = 'Diagnose the Twill SEO environment: wiring, config, registered models, settings, routes, assets and the engine.';

    private const STATUS_OK = 'OK';

    private const STATUS_WARN = 'WARN';

    private const STATUS_FAIL = 'FAIL';

    private const MIN_TWILL_VERSION = '3.6.0';

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $rows = [];

    private bool $hasFailure = false;

    public function handle(ModelRegistry $registry, SeoSettings $settings): int
    {
        $this->info('Twill SEO doctor');
        $this->newLine();

        $this->checkPluginRegistry();
        $this->checkConfigLoaded();
        $this->checkTwillVersion();
        $this->checkPackageTables();
        $this->checkModels($registry);
        $this->checkSettingsSiteName($settings);
        $this->checkHreflangLocales($settings);
        $this->checkSitemapRoute($settings);
        $this->checkDistAssets();
        $this->checkEngineSmoke();

        $this->table(['Check', 'Status', 'Detail'], $this->rows);

        if ($this->hasFailure) {
            $this->newLine();
            $this->error('One or more checks failed.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('All checks passed'.($this->hasWarning() ? ' (with warnings above).' : '.'));

        return self::SUCCESS;
    }

    private function record(string $check, string $status, string $detail = ''): void
    {
        $this->rows[] = [$check, $status, $detail];

        if ($status === self::STATUS_FAIL) {
            $this->hasFailure = true;
        }
    }

    private function hasWarning(): bool
    {
        foreach ($this->rows as [, $status]) {
            if ($status === self::STATUS_WARN) {
                return true;
            }
        }

        return false;
    }

    private function checkPluginRegistry(): void
    {
        $bound = app()->bound(TwillPluginServiceProvider::REGISTRY_BINDING);

        $this->record(
            'Plugins-page registry',
            $bound ? self::STATUS_OK : self::STATUS_FAIL,
            $bound ? 'Registry binding is present.' : 'Registry binding is missing — is TwillSeoServiceProvider loaded?'
        );

        $registered = false;

        if ($bound) {
            $registry = app(TwillPluginServiceProvider::REGISTRY_BINDING);
            $registered = isset($registry['yotech-ai/twill-cms-seo-suite']);
        }

        $this->record(
            'Manifest registered',
            $registered ? self::STATUS_OK : self::STATUS_FAIL,
            $registered ? 'Manifest is registered on the Plugins page.' : 'Manifest is not registered — is TwillSeoServiceProvider loaded?'
        );
    }

    private function checkConfigLoaded(): void
    {
        $loaded = config()->has('twill-seo');

        $this->record(
            'Package config',
            $loaded ? self::STATUS_OK : self::STATUS_FAIL,
            $loaded ? 'Config is loaded.' : 'Config is not loaded — is TwillSeoServiceProvider loaded?'
        );
    }

    /**
     * FAIL on a version we can positively confirm is too old, WARN when the
     * installed version string cannot be parsed as a plain x.y(.z) release
     * (a dev-branch/VCS install) rather than guessing either way. Deliberately
     * plain version_compare() rather than composer/semver's VersionParser:
     * that package is only ever pulled in transitively via dev-only tooling
     * (orchestra/canvas, in this repo's own vendor tree) — using it here
     * would make a production `composer install --no-dev` host fatal the
     * moment `artisan twill-seo:doctor` runs.
     */
    private function checkTwillVersion(): void
    {
        if (! InstalledVersions::isInstalled('area17/twill')) {
            $this->record('Twill version', self::STATUS_FAIL, 'area17/twill does not appear to be installed.');

            return;
        }

        $version = InstalledVersions::getVersion('area17/twill');

        if ($version === null || ! preg_match('/^(\d+\.\d+(?:\.\d+)?)/', $version, $m)) {
            $this->record(
                'Twill version',
                self::STATUS_WARN,
                "Installed version \"{$version}\" could not be parsed — confirm Twill ".self::MIN_TWILL_VERSION.'+ manually.'
            );

            return;
        }

        $pass = version_compare($m[1], self::MIN_TWILL_VERSION, '>=');

        $this->record(
            'Twill version',
            $pass ? self::STATUS_OK : self::STATUS_FAIL,
            $pass
                ? "Installed: {$version} (>= ".self::MIN_TWILL_VERSION.').'
                : "Installed: {$version} — this package requires Twill ".self::MIN_TWILL_VERSION.'+.'
        );
    }

    private function checkPackageTables(): void
    {
        $tables = [
            (new SeoEntry)->getTable(),
            (new SeoEntryTranslation)->getTable(),
            (new SeoSetting)->getTable(),
        ];

        $missing = array_values(array_filter($tables, fn (string $table) => ! Schema::hasTable($table)));

        $this->record(
            'Database tables',
            $missing === [] ? self::STATUS_OK : self::STATUS_FAIL,
            $missing === []
                ? implode(', ', $tables).' all exist.'
                : 'Missing: '.implode(', ', $missing).' — run php artisan migrate.'
        );
    }

    private function checkModels(ModelRegistry $registry): void
    {
        $all = $registry->all();

        if ($all === []) {
            $this->record('Registered models', self::STATUS_WARN, 'twill-seo.models is empty — no content types are managed yet.');

            return;
        }

        $this->record('Registered models', self::STATUS_OK, count($all).' registered: '.implode(', ', array_keys($all)).'.');

        foreach ($all as $key => $config) {
            $this->checkOneModel($key, $config);
        }
    }

    /**
     * @param  array<string,mixed>  $config
     */
    private function checkOneModel(string $key, array $config): void
    {
        $modelClass = $config['model'] ?? null;

        if (! is_string($modelClass) || $modelClass === '' || ! class_exists($modelClass)) {
            $this->record(
                "Model: {$key}",
                self::STATUS_FAIL,
                'Class '.($modelClass !== null ? (string) $modelClass : '(none configured)').' does not exist.'
            );

            return;
        }

        $this->record("Model: {$key}", self::STATUS_OK, "{$modelClass} exists.");

        $usesHasSeo = in_array(HasSeo::class, class_uses_recursive($modelClass), true);

        $this->record(
            "Model uses HasSeo: {$key}",
            $usesHasSeo ? self::STATUS_OK : self::STATUS_FAIL,
            $usesHasSeo ? "{$modelClass} uses HasSeo." : "{$modelClass} does not use TwillSeo\\Models\\Behaviors\\HasSeo."
        );

        $this->checkRepository($key, $modelClass, $config);
        $this->checkTranslatedAttributes($key, $modelClass);
        $this->checkLatestRowUrl($key, $modelClass);
    }

    /**
     * @param  array<string,mixed>  $config
     */
    private function checkRepository(string $key, string $modelClass, array $config): void
    {
        $repositoryClass = $this->resolveRepositoryClass($modelClass, $config);

        if ($repositoryClass === null) {
            $this->record(
                "Repository: {$key}",
                self::STATUS_WARN,
                "Could not determine a repository class for {$modelClass} — set a `repository` key in its registry entry if it doesn't follow the Models\\->Repositories\\ convention, and confirm it uses HandleSeo manually."
            );

            return;
        }

        $usesHandleSeo = in_array(HandleSeo::class, class_uses_recursive($repositoryClass), true);

        $this->record(
            "Repository: {$key}",
            $usesHandleSeo ? self::STATUS_OK : self::STATUS_FAIL,
            $usesHandleSeo
                ? "{$repositoryClass} uses HandleSeo."
                : "{$repositoryClass} does not use TwillSeo\\Repositories\\Behaviors\\HandleSeo — seo_* fields will never be saved."
        );
    }

    /**
     * A registry `repository` override wins when set (a host whose structure
     * does not follow Twill's own Models\->Repositories\ naming convention
     * has no other way to tell us); otherwise the same convention Twill's
     * own module generator uses is applied to the model's FQCN.
     *
     * @param  array<string,mixed>  $config
     */
    private function resolveRepositoryClass(string $modelClass, array $config): ?string
    {
        $explicit = $config['repository'] ?? null;

        if (is_string($explicit) && $explicit !== '' && class_exists($explicit)) {
            return $explicit;
        }

        if (! str_contains($modelClass, '\\Models\\')) {
            return null;
        }

        $conventional = str_replace('\\Models\\', '\\Repositories\\', $modelClass).'Repository';

        return class_exists($conventional) ? $conventional : null;
    }

    /**
     * Reads the seo_* names straight off SeoFields::fieldset() (reflecting
     * each field's protected `name`, same technique FormFieldsTest already
     * uses) rather than a second hand-written copy of HandleSeo's own
     * TRANSLATED_FIELDS/FLAT_FIELDS keys — PHP forbids reading those trait
     * constants from outside a composing class (see HandleSeo/HasSeo's own
     * doc comments on the same restriction), and a third hardcoded list here
     * would just be one more place for the three to quietly drift apart.
     * The media role (twill_seo_og_image) has no `name` property shaped like
     * a form field's and does not start with "seo_" anyway, so it is
     * naturally excluded rather than needing to be filtered out by hand.
     */
    private function checkTranslatedAttributes(string $key, string $modelClass): void
    {
        try {
            $instance = new $modelClass;
        } catch (Throwable $e) {
            $this->record("Translated attributes: {$key}", self::STATUS_WARN, "Could not instantiate {$modelClass}: {$e->getMessage()}");

            return;
        }

        $translated = property_exists($instance, 'translatedAttributes') && is_array($instance->translatedAttributes)
            ? array_map(strval(...), $instance->translatedAttributes)
            : [];

        $collisions = array_values(array_intersect($translated, $this->reservedSeoFieldNames()));

        if ($collisions !== []) {
            $this->record(
                "Translated attributes: {$key}",
                self::STATUS_FAIL,
                "{$modelClass}'s translatedAttributes collides with a reserved seo_* field name: ".implode(', ', $collisions).
                    ' — HandleSeo strips these before they ever reach fill(), so a real translated column by one of these names can never be saved.'
            );

            return;
        }

        $this->record("Translated attributes: {$key}", self::STATUS_OK, 'No collision with the seo_* field names.');
    }

    /**
     * @return list<string>
     */
    private function reservedSeoFieldNames(): array
    {
        $names = [];

        foreach (SeoFields::fieldset()->fields as $field) {
            try {
                $name = (new ReflectionProperty($field, 'name'))->getValue($field);
            } catch (Throwable) {
                // The analysis panel's BladePartial has no `name` property —
                // it isn't a BaseFormField (see FormFieldsTest's own note).
                continue;
            }

            if (is_string($name) && str_starts_with($name, 'seo_')) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * WARN either way (no rows to check yet, or a row whose URL genuinely
     * does not resolve) rather than FAIL: an unresolved URL is UrlResolver's
     * own ordinary, non-exceptional outcome throughout this package (a fresh
     * install with no `url` callback wired up yet is a normal, common state,
     * not a broken one), and a doctor run should not hard-fail an entire
     * install over it.
     */
    private function checkLatestRowUrl(string $key, string $modelClass): void
    {
        try {
            $row = $modelClass::query()->orderByDesc((new $modelClass)->getKeyName())->first();
        } catch (Throwable $e) {
            $this->record("URL resolution: {$key}", self::STATUS_WARN, "Could not query {$modelClass}: {$e->getMessage()}");

            return;
        }

        if ($row === null) {
            $this->record("URL resolution: {$key}", self::STATUS_WARN, "{$modelClass} has no rows yet — nothing to check.");

            return;
        }

        $url = app(UrlResolver::class)->resolve($row, app()->getLocale());

        $this->record(
            "URL resolution: {$key}",
            $url !== null ? self::STATUS_OK : self::STATUS_WARN,
            $url !== null
                ? "Resolved: {$url}"
                : "The latest {$modelClass} row (id {$row->getKey()}) has no resolvable URL yet."
        );
    }

    private function checkSettingsSiteName(SeoSettings $settings): void
    {
        $siteName = trim($settings->siteName());

        $this->record(
            'Settings: site name',
            $siteName !== '' ? self::STATUS_OK : self::STATUS_WARN,
            $siteName !== ''
                ? "Resolved site name: \"{$siteName}\"."
                : 'No site name is configured — set general.site_name in the settings admin, or config(\'app.name\').'
        );
    }

    private function checkHreflangLocales(SeoSettings $settings): void
    {
        if (! $settings->feature('hreflang')) {
            $this->record('Hreflang locales', self::STATUS_OK, 'hreflang feature is disabled — nothing to check.');

            return;
        }

        $count = count((array) config('translatable.locales', []));

        $this->record(
            'Hreflang locales',
            $count >= 2 ? self::STATUS_OK : self::STATUS_WARN,
            $count >= 2
                ? "{$count} locales configured."
                : "Hreflang is enabled with only {$count} locale configured — alternates need at least two to render."
        );
    }

    private function checkSitemapRoute(SeoSettings $settings): void
    {
        if (! $settings->feature('sitemap')) {
            $this->record('Sitemap route', self::STATUS_OK, 'Sitemap feature is disabled — nothing to check.');

            return;
        }

        try {
            $response = app(SitemapController::class)->index();
            $status = $response->getStatusCode();
        } catch (Throwable $e) {
            $this->record('Sitemap route', self::STATUS_FAIL, 'The sitemap index threw: '.$e->getMessage());

            return;
        }

        $this->record(
            'Sitemap route',
            $status === 200 ? self::STATUS_OK : self::STATUS_FAIL,
            $status === 200 ? 'Sitemap index responded 200.' : "Sitemap index responded {$status}, expected 200."
        );
    }

    /**
     * Both filenames must match vite.config.js's fileName()/cssFileName and
     * AssetController::TYPES — fixed by the build pipeline, not config.
     */
    private function checkDistAssets(): void
    {
        $dist = __DIR__.'/../../resources/dist/';
        $files = ['twill-seo.iife.js', 'twill-seo.css'];

        $missing = array_values(array_filter($files, fn (string $file) => ! is_file($dist.$file)));

        $this->record(
            'Built assets',
            $missing === [] ? self::STATUS_OK : self::STATUS_FAIL,
            $missing === []
                ? implode(', ', $files).' are both present.'
                : 'Missing: '.implode(', ', $missing).' — run npm run build.'
        );
    }

    private function checkEngineSmoke(): void
    {
        try {
            $paper = Paper::builder()
                ->text('<p>This is a short first sentence. Here is a second one to analyze.</p>')
                ->locale('en')
                ->build();

            app(AnalysisRunner::class)->analyze($paper);
        } catch (Throwable $e) {
            $this->record('Engine smoke test', self::STATUS_FAIL, 'AnalysisRunner threw: '.$e->getMessage());

            return;
        }

        $this->record('Engine smoke test', self::STATUS_OK, 'AnalysisRunner analyzed a two-sentence paper without throwing.');
    }
}
