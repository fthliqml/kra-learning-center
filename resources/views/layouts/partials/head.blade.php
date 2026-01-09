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

    /* Set initial sidebar wrapper width - only on desktop */
    .sidebar-wrapper {
        min-width: 0;
    }

    @media (min-width: 768px) {
        .sidebar-wrapper {
            min-width: 5.75rem;
        }

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

    /* Set initial sidebar panel state - OPEN (all screens including mobile) */
    html[data-sidebar="open"] .sidebar-panel {
        width: 16rem;
        height: 85vh;
        border-bottom-right-radius: 0;
        border-top-right-radius: 80px;
        transform: translateX(0);
    }

    /* Hide LMS text initially if sidebar closed */
    html[data-sidebar="closed"] .lms-logo {
        display: none !important;
    }

    /* Hide menu labels when sidebar closed */
    html[data-sidebar="closed"] .sidebar-menu-label {
        display: none !important;
    }

    /* Hide logout section when sidebar closed */
    html[data-sidebar="closed"] .sidebar-logout {
        display: none !important;
    }

    /* Hide submenu chevron when sidebar closed */
    html[data-sidebar="closed"] .sidebar-chevron {
        display: none !important;
    }

    /* Menu button alignment based on sidebar state */
    html[data-sidebar="closed"] .sidebar-menu-btn {
        justify-content: center;
        border-radius: 9999px;
    }

    html[data-sidebar="open"] .sidebar-menu-btn {
        justify-content: space-between;
    }

    /* Nav spacing based on sidebar state */
    html[data-sidebar="closed"] .sidebar-nav {
        margin-top: 0;
    }

    html[data-sidebar="closed"] .sidebar-nav>div {
        margin-bottom: 0.75rem;
    }

    html[data-sidebar="open"] .sidebar-nav {
        margin-top: 15px;
    }

    /* Inner padding adjustment */
    html[data-sidebar="closed"] .sidebar-inner {
        padding-left: 10px;
        padding-right: 30px;
    }

    /* Hide submenus initially - Alpine will take over after init */
    .sidebar-submenu:not([data-alpine-ready]) {
        display: none;
    }

    /* Show active submenu before Alpine ready (only when sidebar open) */
    html[data-sidebar="open"] .sidebar-submenu[data-active="true"]:not([data-alpine-ready]) {
        display: block;
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
