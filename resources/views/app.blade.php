<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

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

        @vite(['resources/css/app.scss', 'resources/js/app.ts'])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
