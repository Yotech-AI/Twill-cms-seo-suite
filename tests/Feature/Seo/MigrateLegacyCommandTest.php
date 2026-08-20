<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TwillSeo\Models\SeoEntry;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Repositories\ArticleRepository;

/*
 * twill-seo:migrate-legacy — the adoption helper for sites that carried
 * hand-rolled seo_title / description columns (and a legacy share-image
 * role) before this package. The fixture article_translations table carries
 * a retired seo_title column for exactly these tests.
 */

function createLegacyArticle(array $legacyTitleByLocale, array $descriptionByLocale = []): Article
{
    $repository = new ArticleRepository(new Article);

    $article = $repository->create([
        'title' => ['en' => 'Legacy', 'nl' => 'Legacy'],
        'description' => $descriptionByLocale,
    ]);

    foreach ($legacyTitleByLocale as $locale => $seoTitle) {
        DB::table('article_translations')
            ->where('article_id', $article->id)
            ->where('locale', $locale)
            ->update(['seo_title' => $seoTitle]);
    }

    return $article;
}

it('copies legacy titles and descriptions into the suite tables per locale', function () {
    $article = createLegacyArticle(
        ['en' => 'Old EN title', 'nl' => 'Oude NL titel'],
        ['en' => 'Old EN meta description.', 'nl' => 'Oude NL metabeschrijving.'],
    );

    $this->artisan('twill-seo:migrate-legacy', ['--type' => ['articles']])
        ->assertSuccessful();

    expect($article->fresh()->seo('en')->seo_title)->toBe('Old EN title')
        ->and($article->fresh()->seo('en')->seo_description)->toBe('Old EN meta description.')
        ->and($article->fresh()->seo('nl')->seo_title)->toBe('Oude NL titel')
        ->and($article->fresh()->seo('nl')->seo_description)->toBe('Oude NL metabeschrijving.');
});

it('never overwrites suite values that already exist, and is idempotent', function () {
    $article = createLegacyArticle(['en' => 'Legacy title']);

    // The editor already wrote a suite title through the CMS — it must win.
    // firstOrCreate rather than create: saving through the repository may
    // already have produced the entry via the score cache.
    $entry = SeoEntry::firstOrCreate([
        'seoable_type' => $article->getMorphClass(),
        'seoable_id' => $article->id,
    ]);
    $translation = $entry->translationOrNew('en');
    $translation->seo_title = 'Editor title';
    $translation->save();

    $this->artisan('twill-seo:migrate-legacy', ['--type' => ['articles']])->assertSuccessful();
    $this->artisan('twill-seo:migrate-legacy', ['--type' => ['articles']])->assertSuccessful();

    expect($article->fresh()->seo('en')->seo_title)->toBe('Editor title')
        ->and(
            DB::table('twill_seo_entry_translations')
                ->where('twill_seo_entry_id', $entry->id)
                ->where('locale', 'en')
                ->count()
        )->toBe(1);
});

it('writes nothing in dry-run mode', function () {
    $article = createLegacyArticle(['en' => 'Legacy title']);

    // Saving through the repository may already have created suite rows
    // (score caching); dry-run is proven by the counts NOT moving and the
    // legacy title NOT arriving — never by absolute-zero table counts.
    $entriesBefore = DB::table('twill_seo_entries')->count();
    $translationsBefore = DB::table('twill_seo_entry_translations')->count();

    $this->artisan('twill-seo:migrate-legacy', ['--type' => ['articles'], '--dry-run' => true])
        ->assertSuccessful();

    expect(DB::table('twill_seo_entries')->count())->toBe($entriesBefore)
        ->and(DB::table('twill_seo_entry_translations')->count())->toBe($translationsBefore)
        ->and($article->fresh()->seo('en')?->seo_title)->not->toBe('Legacy title');
});

it('clones a legacy media role onto the suite share-image role exactly once', function () {
    // The Testbench harness never migrates Twill's media tables (they live
    // outside migrations/default), so this test carries the minimal schema
    // the command touches. Real hosts always have the full tables.
    if (! Schema::hasTable('medias')) {
        Schema::create('medias', function ($table): void {
            $table->bigIncrements('id');
            $table->string('uuid');
            $table->string('filename');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('mediables')) {
        Schema::create('mediables', function ($table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('media_id');
            $table->unsignedBigInteger('mediable_id');
            $table->string('mediable_type');
            $table->string('role');
            $table->string('crop')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    $article = createLegacyArticle(['en' => 'Legacy title']);

    $mediaId = DB::table('medias')->insertGetId([
        'uuid' => 'legacy/share.jpg',
        'filename' => 'share.jpg',
        'width' => 1200,
        'height' => 630,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('mediables')->insert([
        'media_id' => $mediaId,
        'mediable_id' => $article->id,
        'mediable_type' => $article->getMorphClass(),
        'role' => 'legacy_share',
        'crop' => 'default',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('twill-seo:migrate-legacy', [
        '--type' => ['articles'],
        '--media-role' => 'legacy_share',
    ])->assertSuccessful();

    // Second run must not duplicate the cloned row.
    $this->artisan('twill-seo:migrate-legacy', [
        '--type' => ['articles'],
        '--media-role' => 'legacy_share',
    ])->assertSuccessful();

    $suiteRows = DB::table('mediables')
        ->where('mediable_type', $article->getMorphClass())
        ->where('mediable_id', $article->id)
        ->where('role', Article::OG_IMAGE_ROLE)
        ->get();

    expect($suiteRows)->toHaveCount(1)
        ->and($suiteRows->first()->media_id)->toBe($mediaId)
        ->and($suiteRows->first()->crop)->toBe('default');
});

it('fails cleanly on an unknown registry key and skips models without translations', function () {
    $entriesBefore = DB::table('twill_seo_entries')->count();

    $this->artisan('twill-seo:migrate-legacy', ['--type' => ['nope']])->assertFailed();

    // The untranslated fixture Page has no translations relation — the
    // command reports the skip instead of guessing at base-table columns.
    $this->artisan('twill-seo:migrate-legacy', ['--type' => ['pages']])->assertSuccessful();

    expect(DB::table('twill_seo_entries')->count())->toBe($entriesBefore);
});
