{{--
    The BladePartial target for SeoFields::analysisPanel() — see
    A17\Twill\Services\Forms\BladePartial::render(), which shares $item,
    $form_fields, $formModuleName and $routePrefix into this view from
    View::shared('form') (populated by Twill's own module edit-form
    controller). Only $item is used here.

    Assets load unconditionally (not just in the mount branch below) so the
    tss-placeholder boxes in the two guard states get real styling too, not
    just unstyled text; @once still guarantees a host page that happens to
    render this partial more than once (a duplicated fieldset, a preview
    pane) never gets the <link>/<script> tags twice.
--}}
@once
    @push('extra_css')
        <link rel="stylesheet" href="{{ \TwillSeo\Http\Controllers\AssetController::url('twill-seo.css') }}">
    @endpush
    @push('extra_js')
        <script src="{{ \TwillSeo\Http\Controllers\AssetController::url('twill-seo.iife.js') }}" defer></script>
    @endpush
@endonce

@if (! ($item?->exists))
    <div class="tss-placeholder">{{ __('Save this item once to enable SEO analysis.') }}</div>
@else
    @php
        $twillSeoRegistry = app(\TwillSeo\Services\ModelRegistry::class);
        $twillSeoRegistryKey = $twillSeoRegistry->keyFor($item);
    @endphp

    @if ($twillSeoRegistryKey === null)
        <div class="tss-placeholder">{{ __('This content type is not registered with Twill SEO.') }}</div>
    @else
        @php
            $twillSeoModelConfig = $twillSeoRegistry->get($twillSeoRegistryKey);
            $twillSeoLocale = app()->getLocale();
            $twillSeoLocales = array_values((array) config('translatable.locales', [$twillSeoLocale]));
            $twillSeoTitleAttribute = $twillSeoModelConfig['title_attribute'] ?? 'title';

            $twillSeoModelTitle = \TwillSeo\Support\TranslatedAttribute::get($item, $twillSeoTitleAttribute, $twillSeoLocale);

            // Same defensive pattern as PaperFactory::resolvePermalink(): a
            // host model without getFullUrl(), or one whose implementation
            // throws, degrades to a null URL rather than breaking the panel.
            $twillSeoModelUrl = null;
            if (method_exists($item, 'getFullUrl')) {
                try {
                    $twillSeoModelUrl = $item->getFullUrl();
                } catch (\Throwable $twillSeoUrlException) {
                    $twillSeoModelUrl = null;
                }
            }

            // Per-locale cached scores (ScoreCache's own last write) so the
            // panel has something to show before its first live response —
            // never a second, independent analysis, just what is already on
            // disk.
            $twillSeoInitial = [];
            foreach ($twillSeoLocales as $twillSeoLoop) {
                $twillSeoSeo = method_exists($item, 'seo') ? $item->seo($twillSeoLoop) : null;

                $twillSeoInitial[$twillSeoLoop] = [
                    'seo_score' => $twillSeoSeo?->seo_score,
                    'readability_score' => $twillSeoSeo?->readability_score,
                    'analysis_summary' => $twillSeoSeo?->analysis_summary,
                ];
            }

            $twillSeoConfig = [
                'endpoint' => route(config('twill.admin_route_name_prefix', 'twill.').'seo.analyze'),
                'csrf' => csrf_token(),
                'model' => [
                    'type' => $twillSeoRegistryKey,
                    'id' => $item->getKey(),
                    'title' => $twillSeoModelTitle,
                    'url' => $twillSeoModelUrl,
                ],
                'locale' => $twillSeoLocale,
                'locales' => $twillSeoLocales,
                'initial' => $twillSeoInitial,
                'debounceMs' => (int) config('twill-seo.analysis.debounce_ms', 500),
            ];
        @endphp

        <div data-twill-seo-mount="panel" data-twill-seo='@json($twillSeoConfig)'></div>
    @endif
@endif
