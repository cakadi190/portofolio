@php
    $sentry = app(\App\Services\Monitoring\SentrySettings::class)->frontendConfig();
    $user = auth()->user();
@endphp
@if ($sentry)
    {{--
        Browser monitoring settings (public DSN only) and the trace headers
        that link browser spans to this server request. Read by
        resources/js/lib/sentry.ts. No personal data is rendered here.
    --}}
    <meta name="sentry-config" content="{{ json_encode($sentry + [
        'requestId' => (string) request()->attributes->get(\App\Http\Middleware\AssignRequestId::ATTRIBUTE),
    ], JSON_UNESCAPED_SLASHES) }}">
    {!! \Sentry\Laravel\Integration::sentryTracingMeta() !!}
    {!! \Sentry\Laravel\Integration::sentryBaggageMeta() !!}
@endif
