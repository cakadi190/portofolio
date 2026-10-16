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
    <script nonce="{{ Vite::cspNonce() }}">
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($measurementId), { send_page_view: false });

        // gtag.js (~100 KB) is fetched on the visitor's first interaction, or 8 s after the
        // page has loaded for passive readers, so it never competes with LCP/TBT.
        // Queued dataLayer calls are replayed once the script arrives.
        (function () {
            var started = false;
            var events = ['pointerdown', 'keydown', 'scroll', 'touchstart'];
            var loadGtag = function () {
                if (started) {
                    return;
                }
                started = true;
                events.forEach(function (name) {
                    window.removeEventListener(name, loadGtag);
                });
                var script = document.createElement('script');
                script.async = true;
                script.src = {!! json_encode('https://www.googletagmanager.com/gtag/js?id='.urlencode($measurementId), JSON_UNESCAPED_SLASHES) !!};
                document.head.appendChild(script);
            };
            events.forEach(function (name) {
                window.addEventListener(name, loadGtag, { once: true, passive: true });
            });
            window.addEventListener('load', function () {
                setTimeout(loadGtag, 8000);
            });
        })();
    </script>
@endif
