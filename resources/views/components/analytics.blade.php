@php
    $measurementId = app(\App\Services\SystemSettingService::class)
        ->get('google_analytics_id', config('services.google_analytics.measurement_id'));
@endphp
@if ($measurementId)
    {{--
        Google tag (gtag.js). Automatic page_view is disabled because this is
        an Inertia SPA: resources/js/lib/analytics.ts reports a page_view on
        every client-side visit instead.
    --}}
    <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($measurementId), { send_page_view: false });
    </script>
@endif
