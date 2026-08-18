<?php

use A17\Twill\TwillServiceProvider;
use Illuminate\Support\Facades\Schema;
use TwillSeo\TwillSeoServiceProvider;

it('boots Twill and the package under Testbench', function () {
    expect(app()->getLoadedProviders())
        ->toHaveKey(TwillServiceProvider::class)
        ->toHaveKey(TwillSeoServiceProvider::class);
});

it('applies Twill and fixture migrations on sqlite', function () {
    // Table name comes from config: Twill lets a host rename it, and
    // hardcoding would assert something the package never relies on.
    expect(Schema::hasTable(config('twill.users_table', 'twill_users')))->toBeTrue();

    // Fixture CMS, so later tasks can exercise repository behavior.
    expect(Schema::hasTable('articles'))->toBeTrue()
        ->and(Schema::hasTable('article_translations'))->toBeTrue()
        ->and(Schema::hasTable('article_slugs'))->toBeTrue();
});

it('merges the package config', function () {
    expect(config('twill-seo.enabled'))->toBeTrue();
});
