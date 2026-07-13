<!DOCTYPE html>
<html lang="en" data-footer="true" data-placement="horizontal" data-behaviour="pinned">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>@yield('title', 'Task Manager') | Acorn</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function() {
            var key = 'acorn-ecommerce-platform-color';
            var color = localStorage.getItem(key);
            if (color) {
                if (!color.endsWith('-blue')) {
                    if (color.startsWith('dark-')) {
                        localStorage.setItem(key, 'dark-blue');
                    } else {
                        localStorage.setItem(key, 'light-blue');
                    }
                }
            } else {
                localStorage.setItem(key, 'light-blue');
            }

            // Force horizontal menu layout to avoid left sidebar empty spacing
            localStorage.setItem('acorn-ecommerce-platform-placement', 'horizontal');

            // Force standard border radius for square type buttons with a little radius
            localStorage.setItem('acorn-ecommerce-platform-radius', 'standard');
        })();
    </script>

    <!-- Acorn Interface and Icons -->
    <link rel="stylesheet" href="{{ asset('Acorn/font/CS-Interface/style.css') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <!-- Acorn Vendored styles -->
    <link rel="stylesheet" href="{{ asset('Acorn/css/vendor/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('Acorn/css/vendor/OverlayScrollbars.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('Acorn/css/vendor/select2.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('Acorn/css/vendor/select2-bootstrap4.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('Acorn/css/styles.css') }}" />
    <link rel="stylesheet" href="{{ asset('Acorn/css/main.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        crossorigin="anonymous" />
    <script src="{{ asset('Acorn/js/base/loader.js') }}"></script>
    @stack('css')
</head>

<body>
    <div id="root">
        <main class="py-4">
            <div class="container">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('Acorn/js/vendor/jquery-3.5.1.min.js') }}"></script>
    <script src="{{ asset('Acorn/js/vendor/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('Acorn/js/vendor/OverlayScrollbars.min.js') }}"></script>
    <script src="{{ asset('Acorn/icon/acorn-icons.js') }}"></script>
    <script src="{{ asset('Acorn/icon/acorn-icons-interface.js') }}"></script>
    <script src="{{ asset('Acorn/js/vendor/select2.full.min.js') }}"></script>
    <script src="{{ asset('Acorn/js/base/helpers.js') }}"></script>
    <script src="{{ asset('Acorn/js/base/globals.js') }}"></script>
    <script src="{{ asset('Acorn/js/base/nav.js') }}"></script>
    <script src="{{ asset('Acorn/js/base/settings.js') }}"></script>
    <script src="{{ asset('Acorn/js/common.js') }}"></script>
    <script src="{{ asset('Acorn/js/scripts.js') }}"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (jQuery?.fn?.select2) {
            jQuery.fn.select2.defaults.set('theme', 'bootstrap4');
        }
    </script>
    @stack('js')
</body>

</html>
