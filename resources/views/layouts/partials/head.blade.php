<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
@isset($title)
    <title>{{ $title }}</title>
@else
    <title>KRA</title>
@endisset
<link rel="icon" href="/images/logo_kra.png" type="image/png">

{{-- Prevent Alpine x-cloak flash and set initial sidebar width based on localStorage --}}
<style>
    [x-cloak] {
        display: none !important;
    }

    /* Set initial sidebar wrapper width */
    .sidebar-wrapper {
        min-width: 5.75rem;
    }

    @media (min-width: 768px) {
        html[data-sidebar="open"] .sidebar-wrapper {
            min-width: 16rem;
        }
    }

    /* Set initial sidebar panel state - CLOSED by default */
    .sidebar-panel {
        width: 5.75rem;
        height: fit-content;
        top: 15vh;
        border-bottom-right-radius: 60px;
        border-top-right-radius: 60px;
        transform: translateX(-100%);
    }

    @media (min-width: 768px) {
        .sidebar-panel {
            transform: translateX(0);
        }
    }

    /* Set initial sidebar panel state - OPEN */
    @media (min-width: 768px) {
        html[data-sidebar="open"] .sidebar-panel {
            width: 16rem;
            height: 85vh;
            border-bottom-right-radius: 0;
            border-top-right-radius: 80px;
        }
    }

    /* Hide LMS text initially if sidebar closed */
    html[data-sidebar="closed"] .lms-logo {
        display: none !important;
    }
</style>
<script>
    // Sync sidebar state BEFORE DOM renders to prevent flash
    (function() {
        var isOpen = JSON.parse(localStorage.getItem('sidebarOpen') || 'false');
        document.documentElement.setAttribute('data-sidebar', isOpen ? 'open' : 'closed');
    })();
</script>

@yield('css')

@vite(['resources/css/app.css', 'resources/js/app.js'])

<!-- Preconnects to speed up third-party origins used by YouTube embeds and fonts -->
<link rel="preconnect" href="https://www.youtube-nocookie.com">
<link rel="preconnect" href="https://www.youtube.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
