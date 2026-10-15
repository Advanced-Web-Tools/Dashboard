<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ WEB_NAME }} | {{ $name }}</title>
    <script src="@asset('js/theme.js')"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="@asset('css/main.css')">
    <link rel="stylesheet" href="@asset('css/widgets.css')">
    <link rel="stylesheet" href="@asset('css/dashboard.css')">
</head>
@php
    $activeAdmin = new \admin\Admin();
    $activeAdminAuthenticated = $activeAdmin->checkAuthentication();
    $activeAdminName = trim(($activeAdmin->firstname ?? '') . ' ' . ($activeAdmin->lastname ?? ''));
    $activeAdminPicture = trim($activeAdmin->profile_picture ?? '');
@endphp
<body class="awt-ui" data-dashboard-user="{{ $activeAdminAuthenticated ? $activeAdmin->getKey() : '' }}">
<div class="app-shell">
    <aside id="dashboard-sidebar" class="sidebar" aria-label="Sidebar">
        <header class="sidebar-header">
            <div class="sidebar-brand">
                <span class="sidebar-name">{{ WEB_NAME }}</span>
                <span class="sidebar-caption">{{ $name }}</span>
            </div>
            <button id="sidebar-close" class="btn btn-ghost btn-icon sidebar-close" type="button" aria-label="Close navigation">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </header>
        {!! $navigation !!}
        @if($activeAdminAuthenticated)
            <footer class="sidebar-footer" aria-label="Current user">
                <div class="sidebar-user">
                    @if($activeAdminPicture !== '')
                        <img class="sidebar-user-picture" src="{{ $activeAdminPicture }}" alt="" width="40" height="40" referrerpolicy="no-referrer">
                    @endif
                    <div class="sidebar-user-details">
                        <span class="sidebar-user-name">{{ $activeAdminName !== '' ? $activeAdminName : $activeAdmin->username }}</span>
                        <span class="sidebar-caption">{{ $activeAdmin->username }} · {{ $activeAdmin->role ?? 'User' }}</span>
                    </div>
                </div>
                <div class="sidebar-footer-actions">
                    <button id="dark-mode-toggle" class="btn btn-ghost btn-icon" type="button" data-theme-toggle aria-label="Switch to dark mode" title="Switch to dark mode">
                        <i class="fa-solid fa-moon" data-theme-icon aria-hidden="true"></i>
                    </button>
                    <a class="btn btn-outline-secondary btn-sm" href="/dashboard/logout"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Logout</a>
                </div>
            </footer>
        @endif
    </aside>
    <button id="sidebar-overlay" class="sidebar-overlay" type="button" aria-label="Close navigation" tabindex="-1" hidden></button>
    <main id="dashboard-content" class="app-content">
        <header class="navbar mobile-header">
            <div class="sidebar-brand">
                <span class="sidebar-name">{{ WEB_NAME }}</span>
                <span class="sidebar-caption">{{ $name }}</span>
            </div>
            <button id="sidebar-open" class="btn btn-ghost btn-icon sidebar-open-button" type="button" aria-label="Open navigation" aria-controls="dashboard-sidebar" aria-expanded="false">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
        </header>
        @yield("content")
    </main>
</div>
<script src="@asset('js/personal-widgets.js')" defer></script>
<script>
    (() => {
        const sidebar = document.getElementById('dashboard-sidebar');
        const content = document.getElementById('dashboard-content');
        const openButton = document.getElementById('sidebar-open');
        const closeButton = document.getElementById('sidebar-close');
        const overlay = document.getElementById('sidebar-overlay');
        const mobile = window.matchMedia('(max-width: 767px)');

        function setSidebar(open, restoreFocus = true) {
            const expanded = mobile.matches && open;
            document.body.classList.toggle('sidebar-open', expanded);
            openButton.setAttribute('aria-expanded', String(expanded));
            overlay.hidden = !expanded;
            // Move focus before making its former container inert.
            if (expanded) {
                sidebar.inert = false;
                closeButton.focus();
            } else {
                content.inert = false;
                if (mobile.matches && restoreFocus) openButton.focus();
                else if (!mobile.matches && document.activeElement === closeButton) {
                    sidebar.querySelector('a[href], .nav-toggle')?.focus();
                }
            }
            sidebar.inert = mobile.matches && !expanded;
            content.inert = expanded;
        }

        openButton.addEventListener('click', () => setSidebar(true));
        closeButton.addEventListener('click', () => setSidebar(false));
        overlay.addEventListener('click', () => setSidebar(false));
        mobile.addEventListener('change', () => setSidebar(false, false));

        sidebar.addEventListener('click', event => {
            const toggle = event.target.closest('.nav-toggle');
            if (!toggle) return;
            const children = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!children) return;
            const expanded = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(expanded));
            children.hidden = !expanded;
        });

        document.addEventListener('keydown', event => {
            if (!document.body.classList.contains('sidebar-open')) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                setSidebar(false);
                return;
            }
            if (event.key !== 'Tab') return;
            const items = [...sidebar.querySelectorAll('a[href], button:not([disabled])')]
                .filter(element => element.tabIndex >= 0 && !element.closest('[inert]') && element.getClientRects().length > 0);
            const first = items[0];
            const last = items[items.length - 1];
            if (!first) return;
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
        setSidebar(false, false);
    })();
</script>
</body>
</html>
