@extends('twill::layouts.free')

@section('customPageContent')
    <style>
        .tss-header {
            margin: 40px 0 20px;
        }
        .tss-header h2 {
            font-size: 20px;
            font-weight: 600;
        }
        .tss-placeholder {
            padding: 20px;
            border: 1px solid rgba(115, 115, 115, 0.3);
            border-radius: 4px;
        }
        .tss-placeholder p {
            opacity: 0.6;
        }
    </style>

    <div class="tss-header">
        <h2>{{ __('Twill SEO') }}</h2>
    </div>

    <div class="tss-placeholder">
        <p>{{ __('Settings arrive in an upcoming release.') }}</p>
    </div>
@stop
