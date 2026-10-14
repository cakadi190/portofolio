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
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($measurementId), { send_page_view: false });

        // gtag.js (~100 KB) is fetched after the page has loaded and gone idle, so it
        // never competes with LCP/TBT. Queued dataLayer calls are replayed on load.
        window.addEventListener('load', function () {
            var loadGtag = function () {
                var script = document.createElement('script');
                script.async = true;
                script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(@json($measurementId));
                document.head.appendChild(script);
            };
            if ('requestIdleCallback' in window) {
                requestIdleCallback(loadGtag, { timeout: 4000 });
            } else {
                setTimeout(loadGtag, 2000);
            }
        });
    </script>
@endif
