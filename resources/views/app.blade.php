<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32">
        <link rel="icon" href="/favicon-16x16.png" type="image/png" sizes="16x16">
        <link rel="icon" href="/favicon-196x196.png" type="image/png" sizes="196x196">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon-152x152.png">
        <link rel="apple-touch-icon" sizes="120x120" href="/apple-touch-icon-120x120.png">
        <meta name="msapplication-TileColor" content="#2e3192">
        <meta name="msapplication-TileImage" content="/mstile-144x144.png">
        <meta name="theme-color" content="#2e3192">

        @fonts

        {{--
            Anti-FOUC boot script for the admin sidebar: mirrors the persisted
            desktop collapse preference onto <html> before first paint, so the
            sidebar never flashes expanded and then snaps collapsed once the JS
            bundle boots. AdminSidebarState (resources/js/layouts/admin/sidebar-state.svelte.ts)
            keeps this class in sync at runtime. The storage key must match
            COLLAPSED_STORAGE_KEY and the class name below must match the
            `sidebar--collapsed` presentation applied by admin-sidebar.svelte;
            the media query mirrors DESKTOP_BREAKPOINT.
        --}}
        <script>
            try {
                if (
                    window.matchMedia('(min-width: 992px)').matches &&
                    window.localStorage.getItem('sidebar:collapsed') === '1'
                ) {
                    document.documentElement.classList.add('sidebar-toggled');
                }
            } catch (error) {}
        </script>

        {{--
            Anti-FOUC boot script for the light/dark theme: sets `data-bs-theme`
            on <html> before first paint, so the page never flashes light and
            then snaps to dark once the JS bundle boots. theme-toggler.svelte
            (resources/js/components/theme-toggler.svelte) keeps this in sync
            at runtime — the storage key must match THEME_STORAGE_KEY in
            resources/js/lib/theme.ts.
        --}}
        <script>
            try {
                var storedTheme = window.localStorage.getItem('catatancakadi:theme');
                var theme = storedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', theme);
            } catch (error) {}
        </script>

        {{--
            Anti-FOUC: fetch the brand artwork with the document (so the sidebar
            logo/icon don't pop in after JS boots) and keep overlays that are
            only revealed by Bootstrap JS hidden until the stylesheet arrives.
        --}}
        <link rel="preload" as="image" href="/images/brands/logo-white.svg" type="image/svg+xml">
        <link rel="preload" as="image" href="/images/brands/icon-color.svg" type="image/svg+xml">
        <link rel="preload" as="image" href="/images/brands/logo-color.svg" type="image/svg+xml">
        <style>
            .offcanvas:not(.show):not(.showing):not(.hiding),
            .modal:not(.show),
            .dropdown-menu:not(.show) { display: none; }
        </style>

        <x-analytics />

        @vite(['resources/css/app.scss', 'resources/js/app.ts'])
        <x-seo />
        <x-inertia::head />
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
