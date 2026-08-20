<?php

use A17\Twill\Services\Forms\BladePartial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use TwillSeo\Models\SeoSetting;
use TwillSeo\Services\Form\SeoFields;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Models\ArticleWithoutDeclaredCrops;
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

it('builds the full fieldset with every seo_* field name, plus the analysis panel first', function () {
    $fieldset = SeoFields::fieldset();

    expect($fieldset->id)->toBe('seo')
        ->and($fieldset->title)->toBe(__('SEO'))
        ->and($fieldset->open)->toBeFalse();

    expect($fieldset->fields->first())->toBeInstanceOf(BladePartial::class);

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

    // The BladePartial itself has no `name` property to reflect (it isn't a
    // BaseFormField), so the reflection-based field-name check runs over
    // everything after it.
    expect(($this->fieldNames)($fieldset->fields->skip(1)->values()))->toBe($expected);
});

it('trims the fieldset to only the core trio without social or advanced fields', function () {
    $fieldset = SeoFields::fieldset(analysis: false, social: false, advanced: false);

    $expected = ['seo_description', 'seo_keyphrase', 'seo_title'];
    sort($expected);

    expect(($this->fieldNames)($fieldset->fields))->toBe($expected);
});

it('includes the analysis panel as the first fieldset item by default', function () {
    $fieldset = SeoFields::fieldset();

    expect($fieldset->fields->first())->toBeInstanceOf(BladePartial::class);
});

it('omits the analysis panel when $analysis is false', function () {
    $fieldset = SeoFields::fieldset(analysis: false);

    expect($fieldset->fields->filter(fn ($field) => $field instanceof BladePartial))->toBeEmpty();
});

it('omits the analysis panel when the analysis feature is disabled, even with $analysis true', function () {
    config(['twill-seo.features.analysis' => false]);

    $fieldset = SeoFields::fieldset();

    expect($fieldset->fields->filter(fn ($field) => $field instanceof BladePartial))->toBeEmpty();
});

it('omits the analysis panel when the analysis feature is disabled via the settings ROW, even with config left true', function () {
    // DB-over-config precedence, the same shape HeadRenderTest already pins
    // for the general section: the settings row must win over whatever
    // config('twill-seo.features.analysis') still says.
    SeoSetting::create(['id' => 1, 'features' => ['analysis' => false]]);

    $fieldset = SeoFields::fieldset();

    expect($fieldset->fields->filter(fn ($field) => $field instanceof BladePartial))->toBeEmpty();
});

it('includes the analysis panel when the settings row re-enables it over a config default of false', function () {
    config(['twill-seo.features.analysis' => false]);
    SeoSetting::create(['id' => 1, 'features' => ['analysis' => true]]);

    $fieldset = SeoFields::fieldset();

    expect($fieldset->fields->filter(fn ($field) => $field instanceof BladePartial))->not->toBeEmpty();
});

it('renders sideForm() through Twill\'s real side-column pipeline without crashing', function () {
    // Regression for the first sidebar deployment: a Fieldset placed among a
    // Form's loose items reaches base_form's `$field->render()` loop, and
    // Fieldset has no render() — the edit page 500s. sideForm() must route
    // the fieldset through the Form's dedicated fieldsets collection, which
    // is exactly what this render (the same call renderSideForm() makes)
    // proves end to end.
    $article = (new ArticleRepository(new Article))
        ->create(['title' => ['en' => 'Side', 'nl' => 'Side']]);

    View::share('form', [
        'item' => $article,
        'form_fields' => [],
        'moduleName' => 'articles',
        'routePrefix' => null,
    ]);

    $form = SeoFields::sideForm();
    $renderArray = $form->formToRenderArray();

    // The structural half: the fieldset sits in the fieldsets collection,
    // never among the loose render fields...
    expect($renderArray['renderFieldsets'])->not->toBeNull()
        ->and($renderArray['renderFieldsets']->first()->id)->toBe('seo')
        ->and($renderArray['renderFields']->isEmpty())->toBeTrue()
        // ...so Twill suppresses the empty implicit "Content" fieldset.
        ->and($renderArray['disableContentFieldset'])->toBeTrue();

    // The behavioral half: the exact view renderSideForm() builds renders
    // without throwing and contains the SEO fieldset with its fields.
    $html = view('twill::partials.form.renderer.base_form', $renderArray)->render();

    expect($html)->toContain('seo_title')
        ->and($html)->toContain('seo_keyphrase');
});

it('exposes analysisPanel() and sideChip() as their own BladePartials', function () {
    $panel = SeoFields::analysisPanel();
    $chip = SeoFields::sideChip();

    expect($panel)->toBeInstanceOf(BladePartial::class)
        ->and($chip)->toBeInstanceOf(BladePartial::class);
});

it('registers the og image media role on the fixture article without losing the default crops', function () {
    $params = (new Article)->getMediasParams();

    expect($params)->toHaveKey(Article::OG_IMAGE_ROLE)
        ->and($params[Article::OG_IMAGE_ROLE]['default'][0]['ratio'])->toBe(1.91)
        // The host's own declared role must survive the merge. (The fixture
        // declares $mediasParams the way real Twill models do — which is
        // also the composition regression: HasSeo declaring the property
        // itself fatals against any such host.)
        ->and($params)->toHaveKey('cover');
});

it('skips the og role merge for a HasMedias host without a declared mediasParams property', function () {
    // Composing at all is half the test (no trait/class property fatal);
    // the skip itself is the other half — merging here would go through
    // Eloquent's __set() and corrupt the model's own writes.
    $model = new ArticleWithoutDeclaredCrops;

    expect($model->getMediasParams())->not->toHaveKey(Article::OG_IMAGE_ROLE)
        ->and($model->getAttributes())->not->toHaveKey('mediasParams');
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
