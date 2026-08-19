<?php

use TwillSeo\Contracts\ResolvedContent;
use TwillSeo\Contracts\SeoContentResolver;
use TwillSeo\Models\SeoEntry;
use TwillSeo\Models\SeoEntryTranslation;
use TwillSeo\Models\SeoSetting;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Repositories\ArticleRepository;

beforeEach(function () {
    $this->articles = new ArticleRepository(new Article);
});

/**
 * Fetches a locale's cached translation straight from the database rather
 * than through the model's own relation cache, so the assertion can never be
 * fooled by a stale, already-loaded PHP object.
 */
function freshSeoTranslation(int $entryId, string $locale): ?SeoEntryTranslation
{
    return SeoEntryTranslation::query()
        ->where('twill_seo_entry_id', $entryId)
        ->where('locale', $locale)
        ->first();
}

/**
 * Asserts the exact compact shape the brief specifies, without pinning the
 * red/orange/green counts to fragile numbers that would break the moment a
 * future task adds another assessment.
 */
function assertCompactSummaryShape(array $summary): void
{
    expect(array_keys($summary))->toBe(['seo', 'readability', 'insights']);

    foreach (['seo', 'readability'] as $section) {
        expect(array_keys($summary[$section]))->toBe(['red', 'orange', 'green']);

        foreach ($summary[$section] as $count) {
            expect($count)->toBeInt()->toBeGreaterThanOrEqual(0);
        }
    }

    expect(array_keys($summary['insights']))->toBe(['words', 'reading_time', 'flesch']);
    expect($summary['insights']['words'])->toBeInt()->toBeGreaterThanOrEqual(0);
    expect($summary['insights']['reading_time'])->toBeInt()->toBeGreaterThanOrEqual(0);

    // English is a fully supported language pack, so a paper with enough
    // text gets a real flesch float rather than null — Pest has no built-in
    // "one of these types" expectation, hence the plain boolean check.
    $flesch = $summary['insights']['flesch'];
    expect($flesch === null || is_float($flesch) || is_int($flesch))->toBeTrue();
}

it('caches seo and readability scores for every configured locale after an update carries seo fields', function () {
    $article = $this->articles->create(['title' => ['en' => 'Test Article', 'nl' => 'Testartikel']]);

    $this->articles->update($article->id, [
        'seo_keyphrase' => ['en' => 'green tea', 'nl' => 'groene thee'],
        'seo_description' => ['en' => str_repeat('a', 130), 'nl' => str_repeat('a', 130)],
    ]);

    $entry = SeoEntry::query()->where('seoable_id', $article->id)->first();
    expect($entry)->not->toBeNull();

    foreach (['en', 'nl'] as $locale) {
        $translation = freshSeoTranslation($entry->id, $locale);

        expect($translation)->not->toBeNull()
            ->and($translation->seo_score)->toBeInt()
            ->and($translation->seo_score)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100)
            ->and($translation->readability_score)->toBeInt()
            ->and($translation->readability_score)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100)
            ->and($translation->analyzed_at)->not->toBeNull();

        assertCompactSummaryShape($translation->analysis_summary);
    }
});

it('writes no scores when refresh_scores_on_save is disabled', function () {
    config(['twill-seo.analysis.refresh_scores_on_save' => false]);

    $article = $this->articles->create([
        'title' => ['en' => 'Test Article'],
        'seo_keyphrase' => ['en' => 'green tea'],
    ]);

    $entry = SeoEntry::query()->where('seoable_id', $article->id)->first();
    expect($entry)->not->toBeNull();

    $translation = freshSeoTranslation($entry->id, 'en');

    expect($translation)->not->toBeNull()
        ->and($translation->seo_score)->toBeNull()
        ->and($translation->readability_score)->toBeNull()
        ->and($translation->analysis_summary)->toBeNull()
        ->and($translation->analyzed_at)->toBeNull();
});

it('writes no scores when the analysis feature is disabled via the settings ROW, even with config left true', function () {
    // DB-over-config precedence, same shape as the config-only test above,
    // but through SeoSettings::feature() rather than the raw config key —
    // the settings admin's toggle must actually stop ScoreCache from
    // writing, not just the config file.
    SeoSetting::create(['id' => 1, 'features' => ['analysis' => false]]);

    $article = $this->articles->create([
        'title' => ['en' => 'Test Article'],
        'seo_keyphrase' => ['en' => 'green tea'],
    ]);

    $entry = SeoEntry::query()->where('seoable_id', $article->id)->first();
    expect($entry)->not->toBeNull();

    $translation = freshSeoTranslation($entry->id, 'en');

    expect($translation)->not->toBeNull()
        ->and($translation->seo_score)->toBeNull()
        ->and($translation->readability_score)->toBeNull()
        ->and($translation->analysis_summary)->toBeNull()
        ->and($translation->analyzed_at)->toBeNull();
});

it('writes scores when the settings row re-enables analysis over a config default of false', function () {
    config(['twill-seo.features.analysis' => false]);
    SeoSetting::create(['id' => 1, 'features' => ['analysis' => true]]);

    $article = $this->articles->create([
        'title' => ['en' => 'Test Article'],
        'seo_keyphrase' => ['en' => 'green tea'],
    ]);

    $entry = SeoEntry::query()->where('seoable_id', $article->id)->first();
    expect($entry)->not->toBeNull();

    $translation = freshSeoTranslation($entry->id, 'en');

    expect($translation)->not->toBeNull()
        ->and($translation->seo_score)->not->toBeNull()
        ->and($translation->readability_score)->not->toBeNull()
        ->and($translation->analyzed_at)->not->toBeNull();
});

it('never lets a failing content resolver break the save', function () {
    app()->bind(SeoContentResolver::class, function () {
        return new class implements SeoContentResolver
        {
            public function resolve(object $model, string $locale): ResolvedContent
            {
                throw new RuntimeException('the resolver blew up');
            }
        };
    });

    $article = $this->articles->create([
        'title' => ['en' => 'Test Article'],
        'seo_keyphrase' => ['en' => 'green tea'],
    ]);

    // The save itself must succeed and the model must be persisted — no
    // exception escapes HandleSeo::afterSaveHandleSeo's try/catch.
    expect($article->exists)->toBeTrue()
        ->and(Article::query()->find($article->id))->not->toBeNull();

    $entry = SeoEntry::query()->where('seoable_id', $article->id)->first();
    expect($entry)->not->toBeNull();

    // The refresh aborted before it could write anything for this locale.
    expect(freshSeoTranslation($entry->id, 'en')?->seo_score)->toBeNull();
});

it('refreshes scores on a publish-toggle style update that posts no seo_* keys', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'Test Article'],
        'seo_keyphrase' => ['en' => 'green tea'],
    ]);

    $entry = SeoEntry::query()->where('seoable_id', $article->id)->first();

    // Blank out what the create() call just cached, so the next assertion can
    // only pass if the SECOND save (which touches no seo_* keys at all) is
    // what refreshed them again.
    freshSeoTranslation($entry->id, 'en')->update([
        'seo_score' => null,
        'readability_score' => null,
        'analysis_summary' => null,
        'analyzed_at' => null,
    ]);

    // Mirrors a publish toggle: no seo_* keys in the payload at all, so
    // HandleSeo's stashedSeoFields stays empty and the old early return would
    // have skipped straight past the cache refresh.
    $this->articles->update($article->id, ['title' => ['en' => 'Test Article']]);

    $translation = freshSeoTranslation($entry->id, 'en');

    expect($translation->seo_score)->not->toBeNull()
        ->and($translation->analyzed_at)->not->toBeNull();
});
