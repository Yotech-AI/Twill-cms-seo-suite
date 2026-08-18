<?php

namespace TwillSeo\Tests;

use A17\Twill\Facades\TwillBlocks;
use A17\Twill\Models\User;
use A17\Twill\TwillServiceProvider;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase as Orchestra;
use TwillSeo\Tests\Fixtures\FixtureServiceProvider;
use TwillSeo\TwillSeoServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetTwillBlockStatics();
        $this->stubTwillAssetManifest();
    }

    /**
     * Twill's compiled front-end assets are NOT shipped through Composer — they
     * are published by `php artisan twill:update` from a dist/ directory that
     * does not exist in a vendor install. Any test that renders an admin view
     * therefore dies on "Twill assets manifest is missing".
     *
     * Twill reads that manifest through Cache::rememberForever('twill-manifest'),
     * and twillAsset() falls back to a plain public path for any key the
     * manifest lacks. Seeding the cache with an empty manifest is enough to make
     * every admin view render; the asset URLs it produces are never asserted on.
     */
    protected function stubTwillAssetManifest(): void
    {
        Cache::forever('twill-manifest', []);
    }

    /**
     * Provider order mirrors a real host: Twill first (our provider reads its
     * config and facades at boot), the fixture CMS next (its models and
     * migrations exist independently of the package under test), ours last.
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TwillServiceProvider::class,
            FixtureServiceProvider::class,
            TwillSeoServiceProvider::class,
        ];
    }

    /**
     * Twill ships migrations a host normally runs through its own provider;
     * Testbench needs them pointed at explicitly, and so do our own (empty for
     * now) and the fixture CMS's.
     */
    protected function defineDatabaseMigrations(): void
    {
        // __DIR__-relative, not base_path(): under Testbench base_path() is the
        // skeleton app, not this package.
        $vendor = __DIR__.'/../vendor';

        // Deliberately `artisan migrate` rather than loadMigrationsFrom(): the
        // latter also registers a migrate:rollback on teardown, and Twill's
        // 2023_03_24_125122_add_id_to_related::down() unconditionally drops the
        // `id` column that 2020_02_09_000010 created as the PRIMARY KEY, which
        // SQLite cannot do. The rollback is redundant here anyway — the database
        // is :memory:, so every test already starts from an empty schema.
        foreach ([
            $vendor.'/area17/twill/migrations/default',
            __DIR__.'/../database/migrations',
            __DIR__.'/Fixtures/migrations',
        ] as $path) {
            $this->artisan('migrate', [
                '--path' => $path,
                '--realpath' => true,
            ]);
        }
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        // Name the connection "sqlite", not "testing". Several Twill migrations
        // branch on `config('database.default') !== 'sqlite'` — they compare the
        // connection NAME, not the driver — and take a MySQL-only path under any
        // other name.
        $config->set('database.default', 'sqlite');
        $config->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        // Two locales, matching what the twill-cms-ai-assistent sibling sets
        // (translatable.locales + twill.locale — Twill has no "active_languages"
        // config key), so a test that only ever wrote English could never
        // accidentally pass for lack of a second locale to compare against.
        $config->set('translatable.locales', ['en', 'nl']);
        $config->set('twill.locale', 'en');
    }

    /**
     * TwillBlocks keeps its discovered repeaters in class-level statics that
     * survive between test files in the same process, so a file that registers
     * blocks leaks them into the next one. Clearing them per test keeps
     * block-dependent assertions honest. This package registers no blocks yet,
     * but the fixture CMS is expected to grow some in a later task.
     */
    protected function resetTwillBlockStatics(): void
    {
        if (! class_exists(TwillBlocks::class)) {
            return;
        }

        $instance = TwillBlocks::getFacadeRoot();

        if ($instance === null) {
            return;
        }

        foreach (['dynamicRepeaters', 'loadedDynamicRepeaters'] as $property) {
            $this->resetStaticProperty($instance, $property);
        }
    }

    protected function resetStaticProperty(object $instance, string $property): void
    {
        $reflection = new \ReflectionClass($instance);

        if (! $reflection->hasProperty($property)) {
            return;
        }

        $reflected = $reflection->getProperty($property);

        if (! $reflected->isStatic()) {
            return;
        }

        $current = $reflected->getValue();
        $reflected->setValue(null, is_array($current) ? [] : null);
    }

    /**
     * Creates a saved, published Twill admin and logs the test client in as
     * them on the twill_users guard. Returns $this (as actingAs() does) so an
     * admin-page request can be chained straight off it.
     */
    protected function actingAsTwillAdmin(string $email = 'admin@example.test', string $role = 'SUPERADMIN'): static
    {
        $userClass = config('twill.models.user', User::class);

        $user = new $userClass;
        $user->name = 'Admin';
        $user->email = $email;
        $user->password = bcrypt('secret');
        $user->published = true;
        $user->role = $role;
        $user->save();

        return $this->actingAs($user, 'twill_users');
    }
}
