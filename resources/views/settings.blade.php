{{--
    Same @once asset-push pattern as resources/views/form/analysis-panel.blade.php
    — this is the only OTHER page that mounts the package's Vue bundle, and it
    is a full free-layout page rather than a form partial, so there is no risk
    of it rendering twice in one response the way a duplicated fieldset could.
    @once is kept anyway for consistency with the established pattern.
--}}
@once
    @push('extra_css')
        <link rel="stylesheet" href="{{ \TwillSeo\Http\Controllers\AssetController::url('twill-seo.css') }}">
    @endpush
    @push('extra_js')
        <script src="{{ \TwillSeo\Http\Controllers\AssetController::url('twill-seo.iife.js') }}" defer></script>
    @endpush
@endonce

@extends('twill::layouts.free')

@section('customPageContent')
    <div class="tss-settings-header">
        <h2>{{ __('Twill SEO') }}</h2>
        <p>{{ __('Site identity, per-content-type templates, feature toggles and advanced options.') }}</p>
    </div>

    <div data-twill-seo-mount="settings" data-twill-seo='@json($config)'></div>
@stop
