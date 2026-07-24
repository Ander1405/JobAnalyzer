<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f2f5fb">

        <script>
            (() => {
                let storedTheme = null;

                try {
                    storedTheme = window.localStorage.getItem('theme:preference');
                } catch {
                    // El tema del sistema sigue disponible si el almacenamiento está bloqueado.
                }

                const isDark = storedTheme === 'dark'
                    || (storedTheme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);

                document.documentElement.classList.toggle('dark', isDark);
                document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
                document.querySelector('meta[name="theme-color"]')
                    ?.setAttribute('content', isDark ? '#08101f' : '#f2f5fb');
            })();
        </script>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
