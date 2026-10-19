@php use App\Models\System\Setting; @endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-palette="{{ auth()->user()?->theme ?? 'pinkpanther' }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ config('app.name', 'Redfluz') }}</title>

    <!-- Vite Assets -->
    @vite(['resources/sass/app.scss', 'resources/sass/themes.scss', 'resources/js/app.js'])
</head>

<body class="bg-2">
    {{-- Navigation lives in the topbar (see layouts.nav). There is no
         sidebar rail and no show/hide state to restore. --}}
    <div class="app-wrapper d-flex flex-column w-100 min-vh-100" id="app">
        <!-- Topbar (contains main navigation + user menu) -->
        <header class="topbar-wrapper border-bottom bg-white flex-shrink-0">
            @include('layouts.topbar')
        </header>

        <!-- Main Content Area -->
        <main class="content-col md:p-4 flex-grow-1 p-3">
            @yield('content')
        </main>
    </div>

    <script>
        let bootstrapInvalidTabActivated = false;

        document.addEventListener(
            'invalid',
            (event) => {
                if (bootstrapInvalidTabActivated || !(event.target instanceof Element)) return;

                const tabPane = event.target.closest('.tab-pane');
                if (!tabPane || tabPane.classList.contains('active')) return;

                const tab = Array.from(document.querySelectorAll('[data-bs-toggle="tab"]')).find(
                    (item) => item.getAttribute('data-bs-target') === `#${tabPane.id}`,
                );
                if (tab) {
                    bootstrapInvalidTabActivated = true;
                    tab.click();
                    setTimeout(() => (bootstrapInvalidTabActivated = false), 0);
                }
            },
            true,
        );
    </script>

    @stack('scripts')
    @yield('script')
</body>
</html>
