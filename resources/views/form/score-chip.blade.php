{{--
    The BladePartial target for SeoFields::sideChip() — a server-rendered,
    JS-free compact summary (per-locale SEO score dots) for a host's own
    sidebar/summary UI. Reuses the exact same ScoreRating::dot() markup the
    listing columns render, so a dot here always agrees with the dot on the
    listing page for the same score. Shares $item the same way
    analysis-panel.blade.php does — see that file's own doc comment.
--}}
@if ($item?->exists)
    @php
        $twillSeoChipRegistry = app(\TwillSeo\Services\ModelRegistry::class);
        $twillSeoChipRegistered = $twillSeoChipRegistry->keyFor($item) !== null;
    @endphp

    @if ($twillSeoChipRegistered)
        @php
            $twillSeoChipLocales = array_values((array) config('translatable.locales', [app()->getLocale()]));
        @endphp

        <div class="tss-side-chip">
            @foreach ($twillSeoChipLocales as $twillSeoChipLocale)
                @php
                    $twillSeoChipSeo = method_exists($item, 'seo') ? $item->seo($twillSeoChipLocale) : null;
                @endphp
                <span class="tss-side-chip__locale">
                    {{ strtoupper($twillSeoChipLocale) }}
                    {!! \TwillSeo\Support\ScoreRating::dot($twillSeoChipSeo?->seo_score) !!}
                </span>
            @endforeach
        </div>
    @endif
@endif
