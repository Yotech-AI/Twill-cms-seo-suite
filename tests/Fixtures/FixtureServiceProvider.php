<?php

namespace TwillSeo\Tests\Fixtures;

use Illuminate\Support\ServiceProvider;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Models\Page;

/**
 * Stands up the fixture CMS for the test harness: the Article/Page modules'
 * migrations are loaded directly by TestCase::defineDatabaseMigrations, and
 * this provider registers them in config('twill-seo.models') — in register()
 * so it runs before TwillSeoServiceProvider::register()'s mergeConfigFrom()
 * (provider order: Twill, fixtures, ours — see TestCase::getPackageProviders).
 * mergeConfigFrom() only fills in keys the config doesn't already have, so
 * whatever we set here survives the merge untouched.
 *
 * No fixture blocks exist yet, so nothing else is registered. A later task
 * that tests renderBlocks() will register fixture blocks and their view
 * namespace here, the way the twill-cms-ai-assistent sibling's fixture
 * provider does.
 */
class FixtureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app['config']->set('twill-seo.models', [
            'articles' => [
                'model' => Article::class,
                'title_attribute' => 'title',
            ],
            'pages' => [
                'model' => Page::class,
                'title_attribute' => 'title',
            ],
        ]);
    }
}
