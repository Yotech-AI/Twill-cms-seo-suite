{{--
    Everything here reads $seo (a TwillSeo\Services\Meta\PageSeo, already
    fully resolved — see SeoResolver, the single cascade authority) and
    prints; no fallback/precedence decision is made in this file. Every
    {{ }} auto-escapes via Blade's own e() — the one exception is the JSON-LD
    script body, which uses json_encode()'s own escaping (HTML-safe flags)
    instead, since HTML-escaping valid JSON would corrupt it.
--}}
@if ($seo === null)
<!-- twill-seo: no page context resolved (no $model, and no prior TwillSeo::page() call this request) -->
{{-- ^ a literal HTML comment, not a {{-- Blade --}} one: a Blade comment compiles to nothing at all,
     which would render this branch as a genuinely empty string instead of a visible, debuggable marker. --}}
@else
<title>{{ $seo->title }}</title>
@if ($seo->description !== null)
<meta name="description" content="{{ $seo->description }}">
@endif
<meta name="robots" content="{{ $seo->robots }}">
@if ($seo->canonicalUrl !== null)
<link rel="canonical" href="{{ $seo->canonicalUrl }}">
@endif
@foreach ($seo->alternates as $hreflang => $href)
<link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
@endforeach
@if ($showOg)
<meta property="og:locale" content="{{ $seo->ogLocale }}">
<meta property="og:type" content="{{ $seo->ogType }}">
@if ($seo->ogTitle !== null)
<meta property="og:title" content="{{ $seo->ogTitle }}">
@endif
@if ($seo->ogDescription !== null)
<meta property="og:description" content="{{ $seo->ogDescription }}">
@endif
@if ($seo->url !== null)
<meta property="og:url" content="{{ $seo->url }}">
@endif
<meta property="og:site_name" content="{{ $siteName }}">
@if ($seo->ogImage !== null)
<meta property="og:image" content="{{ $seo->ogImage['url'] }}">
<meta property="og:image:width" content="{{ $seo->ogImage['width'] }}">
<meta property="og:image:height" content="{{ $seo->ogImage['height'] }}">
@endif
@if ($seo->ogType === 'article')
@if ($seo->publishedTime !== null)
<meta property="article:published_time" content="{{ $seo->publishedTime->format(DATE_ATOM) }}">
@endif
@if ($seo->modifiedTime !== null)
<meta property="article:modified_time" content="{{ $seo->modifiedTime->format(DATE_ATOM) }}">
@endif
@endif
@endif
@if ($seo->twitterTitle !== null)
<meta name="twitter:card" content="{{ $seo->twitterCard() }}">
<meta name="twitter:title" content="{{ $seo->twitterTitle }}">
@if ($seo->twitterDescription !== null)
<meta name="twitter:description" content="{{ $seo->twitterDescription }}">
@endif
@endif
@if ($graph !== null)
{{-- JSON_HEX_TAG closes off a "</script>" breakout inside any string value (e.g. a title containing literal script tags); UNESCAPED_SLASHES/UNICODE just keep URLs and non-ASCII text readable rather than mangled. --}}
<script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endif
@endif
