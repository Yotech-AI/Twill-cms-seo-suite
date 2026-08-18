<?php

use Illuminate\Support\Collection;
use TwillSeo\Services\Form\SeoFields;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Models\Page;
use TwillSeo\Tests\Fixtures\Repositories\ArticleRepository;

beforeEach(function () {
    // BaseFormField keeps `name` protected with no public getter — Twill's
    // form-field API is built to be ->render()ed straight into a live admin
    // form, not serialized. Reflection is the least brittle way to assert
    // "these are the fields SeoFields wired up" without coupling this test
    // to Twill's Blade partials, which are Twill's own concern to test.
    $this->fieldNames = function (Collection $fields): array {
        $names = $fields->map(function (object $field) {
            return (new ReflectionProperty($field, 'name'))->getValue($field);
        })->all();

        sort($names);

        return $names;
    };
});

it('exposes stored seo values through getFormFields after a save', function () {
    $repository = new ArticleRepository(new Article);

    $article = $repository->create([
        'title' => ['en' => 'A', 'nl' => 'A'],
        'seo_title' => ['en' => 'SEO A', 'nl' => 'SEO A nl'],
        'seo_noindex' => true,
    ]);

    $fields = $repository->getFormFields($article);

    expect($fields['translations']['seo_title']['en'])->toBe('SEO A')
        ->and($fields['translations']['seo_title']['nl'])->toBe('SEO A nl')
        ->and($fields['seo_noindex'])->toBeTrue();
});

it('builds the full fieldset with every seo_* field name', function () {
    $fieldset = SeoFields::fieldset();

    expect($fieldset->id)->toBe('seo')
        ->and($fieldset->title)->toBe(__('SEO'))
        ->and($fieldset->open)->toBeFalse();

    $expected = [
        'seo_keyphrase',
        'seo_title',
        'seo_description',
        'seo_canonical_url',
        'seo_noindex',
        'seo_nofollow',
        'seo_cornerstone',
        'seo_og_title',
        'seo_og_description',
        'seo_twitter_title',
        'seo_twitter_description',
        // A trait constant can't be read directly via the trait name from
        // outside a class that uses it — Article::OG_IMAGE_ROLE (inherited
        // from HasSeo) is the valid way to reach it here.
        Article::OG_IMAGE_ROLE,
    ];
    sort($expected);

    expect(($this->fieldNames)($fieldset->fields))->toBe($expected);
});

it('trims the fieldset to only the core trio without social or advanced fields', function () {
    $fieldset = SeoFields::fieldset(social: false, advanced: false);

    $expected = ['seo_description', 'seo_keyphrase', 'seo_title'];
    sort($expected);

    expect(($this->fieldNames)($fieldset->fields))->toBe($expected);
});

it('registers the og image media role on the fixture article without losing the default crops', function () {
    $params = (new Article)->getMediasParams();

    expect($params)->toHaveKey(Article::OG_IMAGE_ROLE)
        ->and($params[Article::OG_IMAGE_ROLE]['default'][0]['ratio'])->toBe(1.91)
        // config('twill.default_crops')'s own roles must survive the merge.
        ->and($params)->toHaveKey('cover');
});

it('registers both fixture modules in the twill-seo model registry', function () {
    // Later tasks (analyze endpoint, sitemap, settings UI) discover which
    // models are SEO-managed through this config, keyed by a stable string
    // rather than a class name the client could otherwise spoof.
    expect(config('twill-seo.models.articles.model'))->toBe(Article::class)
        ->and(config('twill-seo.models.articles.title_attribute'))->toBe('title')
        ->and(config('twill-seo.models.pages.model'))->toBe(Page::class)
        ->and(config('twill-seo.models.pages.title_attribute'))->toBe('title')
        // The fixture registration must not have clobbered the rest of the
        // package's own config defaults merged in by TwillSeoServiceProvider.
        ->and(config('twill-seo.enabled'))->toBeTrue();
});
