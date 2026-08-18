<?php

use Illuminate\Support\Facades\DB;
use TwillSeo\Models\SeoEntry;
use TwillSeo\Models\SeoEntryTranslation;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Models\Page;
use TwillSeo\Tests\Fixtures\Repositories\ArticleRepository;
use TwillSeo\Tests\Fixtures\Repositories\PageRepository;

beforeEach(function () {
    $this->articles = new ArticleRepository(new Article);
    $this->pages = new PageRepository(new Page);
});

it('creates a seo entry with mapped translations and flat booleans on create', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'A', 'nl' => 'A'],
        'seo_title' => ['en' => 'SEO A', 'nl' => 'SEO A nl'],
        'seo_keyphrase' => ['en' => 'alpha'],
        'seo_noindex' => true,
    ]);

    // None of the package's form keys leaked into the host model's own
    // attribute bag — they were stashed and unset before fill() ever saw
    // them, not merely ignored because "articles" happens to lack the columns.
    expect($article->getAttributes())
        ->not->toHaveKey('seo_title')
        ->not->toHaveKey('seo_keyphrase')
        ->not->toHaveKey('seo_noindex');

    expect(SeoEntry::query()->count())->toBe(1);

    $entry = SeoEntry::query()->first();

    expect($entry->seoable_type)->toBe($article->getMorphClass())
        ->and($entry->seoable_id)->toBe($article->id)
        ->and($entry->robots_noindex)->toBeTrue()
        ->and($entry->robots_nofollow)->toBeFalse()
        ->and($entry->cornerstone)->toBeFalse();

    expect($entry->translations)->toHaveCount(2);

    $en = $entry->translation('en');
    $nl = $entry->translation('nl');

    expect($en->seo_title)->toBe('SEO A')
        ->and($en->focus_keyphrase)->toBe('alpha')
        ->and($nl->seo_title)->toBe('SEO A nl')
        // 'nl' was never posted for seo_keyphrase, so it must stay unset —
        // this is only meaningful because the two mapped fields (seo_title,
        // seo_keyphrase) are stashed and looped independently per field, not
        // against one shared "the locales this save touched" list.
        ->and($nl->focus_keyphrase)->toBeNull();
});

it('stashes no seo_* persistence when a save never touches any seo_* field', function () {
    // Superseded by Task 5: this used to assert NO SeoEntry was created at
    // all (afterSaveHandleSeo returned before firstOrCreate() ever ran). Task
    // 5's ScoreCache now runs unconditionally at the end of
    // afterSaveHandleSeo — even a save with no seo_* keys still needs a
    // cached score, or a never-touched page would show a permanently grey
    // "not analyzed" listing dot instead of the real (if red) score it
    // deserves — and per ScoreCache's own contract it creates the entry and
    // its translation rows when absent. What Task 2 actually guarded against
    // — the seo_* PERSISTENCE block (flat columns, translated columns) never
    // running for fields nobody posted — still holds: this asserts THAT.
    $article = $this->articles->create(['title' => ['en' => 'A', 'nl' => 'A']]);

    $entry = SeoEntry::query()->where('seoable_id', $article->id)->first();

    expect($entry)->not->toBeNull()
        // Never touched by the save, so the flat columns keep their
        // migration defaults rather than gaining stashed values.
        ->and($entry->robots_noindex)->toBeFalse()
        ->and($entry->robots_nofollow)->toBeFalse()
        ->and($entry->cornerstone)->toBeFalse();

    // Nor did the seo_* TRANSLATED persistence run — every translation's
    // own seo_* columns (as opposed to ScoreCache's score columns) stay
    // null, exactly as an untouched save should leave them.
    foreach ($entry->translations as $translation) {
        expect($translation->seo_title)->toBeNull()
            ->and($translation->focus_keyphrase)->toBeNull();
    }
});

it('leaves stored seo data untouched when an update posts no seo_* keys', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'A', 'nl' => 'A'],
        'seo_title' => ['en' => 'SEO A', 'nl' => 'SEO A nl'],
        'seo_noindex' => true,
    ]);

    // Mirrors flows like updateBasic()/publish toggles, which never send
    // seo_* keys at all: HandleSeo must not touch what it wasn't given.
    $this->articles->update($article->id, ['title' => ['en' => 'B']]);

    $entry = SeoEntry::query()->first();

    expect($entry->robots_noindex)->toBeTrue()
        ->and($entry->translation('en')->seo_title)->toBe('SEO A')
        ->and($entry->translation('nl')->seo_title)->toBe('SEO A nl');
});

it('turns an empty posted value into null for that locale only', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'A', 'nl' => 'A'],
        'seo_title' => ['en' => 'SEO A', 'nl' => 'SEO A nl'],
    ]);

    $this->articles->update($article->id, ['seo_title' => ['en' => '']]);

    $entry = SeoEntry::query()->first();

    expect($entry->translation('en')->seo_title)->toBeNull()
        ->and($entry->translation('nl')->seo_title)->toBe('SEO A nl');
});

it('never lets a colliding host column receive the seo_* form value', function () {
    // pages.seo_title is a REAL column (see the fixture migration) that
    // happens to share its name with our own translated form field. If
    // HandleSeo ever failed to strip the key before fill(), this column
    // would silently receive our locale-keyed array instead of a string.
    $page = $this->pages->create(['title' => 'A page']);

    $this->pages->update($page->id, ['seo_title' => ['en' => 'X']]);

    expect($page->fresh()->seo_title)->toBeNull();

    $entry = SeoEntry::query()->where('seoable_id', $page->id)->first();

    expect($entry->translation('en')->seo_title)->toBe('X');
});

it('duplicates the seo entry and translations but clears analysis fields', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'A', 'nl' => 'A'],
        'seo_title' => ['en' => 'SEO A', 'nl' => 'SEO A nl'],
        'seo_keyphrase' => ['en' => 'alpha'],
    ]);

    // No analysis engine exists yet (a later task), so set the analysis
    // columns by hand to prove afterDuplicateHandleSeo clears them on copy.
    $entry = SeoEntry::query()->where('seoable_id', $article->id)->first();
    $en = $entry->translation('en');
    $en->seo_score = 80;
    $en->readability_score = 70;
    $en->analysis_summary = ['good' => ['Uses the keyphrase']];
    $en->analyzed_at = now();
    $en->save();

    $duplicate = $this->articles->duplicate($article->id);

    expect($duplicate)->not->toBeNull();

    $newEntry = SeoEntry::query()->where('seoable_id', $duplicate->id)->first();

    expect($newEntry)->not->toBeNull()
        ->and($newEntry->id)->not->toBe($entry->id)
        ->and($newEntry->translations)->toHaveCount(2);

    $newEn = $newEntry->translation('en');

    expect($newEn->seo_title)->toBe('SEO A')
        ->and($newEn->focus_keyphrase)->toBe('alpha')
        ->and($newEn->seo_score)->toBeNull()
        ->and($newEn->readability_score)->toBeNull()
        ->and($newEn->analysis_summary)->toBeNull()
        ->and($newEn->analyzed_at)->toBeNull();

    // The clearing only applies to the copy — the original keeps its scores.
    expect($entry->fresh()->translation('en')->seo_score)->toBe(80);
});

it('keeps the seo entry through a soft delete but removes it on force delete', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'A', 'nl' => 'A'],
        'seo_title' => ['en' => 'SEO A', 'nl' => 'SEO A nl'],
    ]);

    $this->articles->delete($article->id);

    expect(SeoEntry::query()->where('seoable_id', $article->id)->exists())->toBeTrue();

    // TestCase runs sqlite with foreign_key_constraints off (see its
    // defineEnvironment() — some Twill migrations don't tolerate strict FK
    // enforcement), so cascadeOnDelete() never actually fires by default in
    // this harness. Turn enforcement on for this one connection/assertion so
    // the DELETE below proves the migration's cascade for real, rather than
    // only asserting on what HandleSeo's own code deletes.
    DB::statement('PRAGMA foreign_keys = ON');

    $this->articles->forceDelete($article->id);

    expect(SeoEntry::query()->where('seoable_id', $article->id)->exists())->toBeFalse()
        ->and(SeoEntryTranslation::query()->count())->toBe(0);
});

it('maps a plain string seo value to the current app locale on an untranslated model', function () {
    $page = $this->pages->create(['title' => 'A page']);

    $this->pages->update($page->id, ['seo_title' => 'Just a string']);

    $entry = SeoEntry::query()->where('seoable_id', $page->id)->first();

    expect($entry->translation(app()->getLocale())->seo_title)->toBe('Just a string');
});
