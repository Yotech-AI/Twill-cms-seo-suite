<?php

use A17\Twill\Models\User;
use A17\Twill\Services\Forms\BladePartial;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Repositories\ArticleRepository;

/**
 * Renders the partial through the exact seam Twill itself uses: BladePartial::render()
 * reads $item off View::shared('form') (see vendor/area17/twill's own
 * BladePartial::render()) rather than receiving it as a normal Blade
 * variable — see this class's own doc comment for why. This lets the test
 * exercise the real view without needing a full module edit-form request.
 */
function renderAnalysisPanel(object $item): string
{
    View::share('form', ['item' => $item]);

    return (string) BladePartial::make()->view('twill-seo::form.analysis-panel')->render();
}

/** Pulls the @json()-encoded config out of the mount div's data-twill-seo attribute. */
function decodeMountConfig(string $html): array
{
    preg_match('/data-twill-seo=\'(.*?)\'/s', $html, $matches);

    expect($matches)->toHaveCount(2, 'expected exactly one data-twill-seo attribute in the rendered HTML');

    return json_decode($matches[1], associative: true, flags: JSON_THROW_ON_ERROR);
}

it('renders the mount div with a full config for a saved, registered article', function () {
    $article = (new ArticleRepository(new Article))->create([
        'title' => ['en' => 'Test Article'],
        'seo_keyphrase' => ['en' => 'green tea'],
        'seo_title' => ['en' => 'Test Article SEO title'],
    ]);

    $html = renderAnalysisPanel($article->fresh());

    expect($html)->toContain('data-twill-seo-mount="panel"');

    $config = decodeMountConfig($html);

    expect($config['endpoint'])->toBe(route(config('twill.admin_route_name_prefix', 'twill.').'seo.analyze'))
        ->and($config['model']['type'])->toBe('articles')
        ->and($config['model']['id'])->toBe($article->id)
        ->and($config['model']['title'])->toBe('Test Article')
        ->and($config['locale'])->toBe(app()->getLocale())
        ->and($config['locales'])->toBe(array_values((array) config('translatable.locales', ['en'])))
        ->and($config['debounceMs'])->toBe(config('twill-seo.analysis.debounce_ms'))
        ->and($config['initial']['en']['seo_score'])->toBeInt()
        ->and($config['initial']['en']['readability_score'])->toBeInt()
        ->and($config['initial'])->toHaveKey('nl');
});

it('shows a neutral placeholder and no mount div for an unsaved model', function () {
    $html = renderAnalysisPanel(new Article);

    expect($html)->not->toContain('data-twill-seo-mount')
        ->and($html)->toContain('Save this item once to enable SEO analysis.');
});

it('shows a registration note and no mount div for a model outside the registry', function () {
    $user = new User;
    $user->name = 'Admin';
    $user->email = 'admin-outside-registry@example.test';
    $user->password = bcrypt('secret');
    $user->published = true;
    $user->role = 'SUPERADMIN';
    $user->save();

    $html = renderAnalysisPanel($user);

    expect($html)->not->toContain('data-twill-seo-mount')
        ->and($html)->toContain('This content type is not registered with Twill SEO.');
});

it('pushes the panel assets exactly once even when the partial renders twice', function () {
    // A real page renders exactly one top-level view; @push/@once state is
    // flushed the moment THAT render finishes (Factory::flushStateIfDoneRendering(),
    // keyed off renderCount hitting zero) — so two independent top-level
    // (string) casts, one after another, would each start and flush their
    // own render pass and could never actually exercise @once's dedup
    // against each other. Rendering both @includes inside one Blade::render()
    // call is what makes this test representative of the real one-page,
    // twice-embedded scenario it is meant to guard against.
    $article = (new ArticleRepository(new Article))->create(['title' => ['en' => 'Test Article']]);

    $html = Blade::render(<<<'BLADE'
        @include('twill-seo::form.analysis-panel')
        @include('twill-seo::form.analysis-panel')
        @stack('extra_css')
        @stack('extra_js')
        BLADE, ['item' => $article->fresh()]);

    expect(substr_count($html, 'twill-seo.css'))->toBe(1)
        ->and(substr_count($html, 'twill-seo.iife.js'))->toBe(1)
        // Sanity check: the mount div itself really did render twice — this
        // test is about the ASSETS being deduped, not about the partial
        // silently only rendering once.
        ->and(substr_count($html, 'data-twill-seo-mount="panel"'))->toBe(2);
});

it('never renders the mount div for an unsaved model even when it is a registered type', function () {
    $html = renderAnalysisPanel(new Article);

    expect($html)->not->toContain('data-twill-seo-mount="panel"');
});
