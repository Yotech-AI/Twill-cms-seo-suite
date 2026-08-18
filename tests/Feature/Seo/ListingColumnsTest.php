<?php

use TwillSeo\Services\Listings\ReadabilityScoreColumn;
use TwillSeo\Services\Listings\SeoScoreColumn;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Repositories\ArticleRepository;

/**
 * Writes known seo_score/readability_score values directly, rather than
 * running the real engine, so the column's color-band logic is pinned to
 * exact scores instead of drifting with whatever the assessments currently
 * grade some fixture content at.
 */
function articleWithScore(?int $seoScore, ?int $readabilityScore): Article
{
    $article = (new ArticleRepository(new Article))->create(['title' => ['en' => 'Scored Article']]);

    // create() above already ran ScoreCache::refresh() through
    // HandleSeo::afterSaveHandleSeo (it now runs on every save, seo_* fields
    // or not), which already created the SeoEntry row — firstOrCreate(), not
    // create(), or this collides with it.
    $entry = $article->seoEntry()->firstOrCreate();
    $entry->translationOrNew('en')->fill([
        'seo_score' => $seoScore,
        'readability_score' => $readabilityScore,
    ])->save();

    return $article->fresh();
}

it('presets the seo score column with the expected field, title and rendering flags', function () {
    $columnArray = SeoScoreColumn::make()->toColumnArray();

    expect($columnArray['name'])->toBe('seo_score')
        ->and($columnArray['label'])->toBe('SEO')
        ->and($columnArray['optional'])->toBeTrue()
        ->and($columnArray['html'])->toBeTrue();
});

it('presets the readability score column with the expected field, title and rendering flags', function () {
    $columnArray = ReadabilityScoreColumn::make()->toColumnArray();

    expect($columnArray['name'])->toBe('readability_score')
        ->and($columnArray['label'])->toBe('Readability')
        ->and($columnArray['optional'])->toBeTrue()
        ->and($columnArray['html'])->toBeTrue();
});

it('emits the exact inline-style dot markup for a scored article', function () {
    app()->setLocale('en');
    $article = articleWithScore(seoScore: 85, readabilityScore: null);

    $html = SeoScoreColumn::make()->renderCell($article);

    expect($html)->toBe(
        '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#7ad03a" title="85/100"></span>'
    );
});

it('renders the seo score dot with the color and title matching its band', function (?int $score, string $color, string $title) {
    app()->setLocale('en');
    $article = articleWithScore(seoScore: $score, readabilityScore: null);

    $html = SeoScoreColumn::make()->renderCell($article);

    expect($html)->toContain('background:'.$color)
        ->and($html)->toContain('title="'.$title.'"');
})->with([
    'never analyzed is grey' => [null, '#b0b0b0', 'Not analyzed'],
    'the bad upper bound is still red' => [40, '#dc3232', '40/100'],
    'just past the bad bound turns orange' => [41, '#ee7c1b', '41/100'],
    'the ok upper bound is still orange' => [70, '#ee7c1b', '70/100'],
    'just past the ok bound turns green' => [71, '#7ad03a', '71/100'],
    'a low score is red' => [10, '#dc3232', '10/100'],
    'a high score is green' => [95, '#7ad03a', '95/100'],
]);

it('renders the readability score dot with the color matching its band, grey when never analyzed', function (?int $score, string $color) {
    app()->setLocale('en');
    $article = articleWithScore(seoScore: null, readabilityScore: $score);

    $html = ReadabilityScoreColumn::make()->renderCell($article);

    expect($html)->toContain('background:'.$color);
})->with([
    'never analyzed is grey' => [null, '#b0b0b0'],
    'a bad score is red' => [30, '#dc3232'],
    'an ok score is orange' => [55, '#ee7c1b'],
    'a good score is green' => [85, '#7ad03a'],
]);
