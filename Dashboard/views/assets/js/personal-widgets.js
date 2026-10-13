(() => {
    'use strict';
    const user = document.body.dataset.dashboardUser;
    if (!/^[1-9][0-9]*$/.test(user || '')) return;

    const notesKey = `dashboard:${user}:notes`;
    const historyKey = `dashboard:${user}:recent-pages`;
    const maxRecent = 6;
    let storageAvailable = true;
    let savedTimer;
    let clearedHistory = null;

    function read(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (_) {
            storageAvailable = false;
            return null;
        }
    }

    function write(key, value) {
        try {
            window.localStorage.setItem(key, value);
            storageAvailable = true;
            return true;
        } catch (_) {
            storageAvailable = false;
            return false;
        }
    }

    // Use only links currently provided by navigation, never stored HTML or labels.
    const pages = new Map();
    document.querySelectorAll('#dashboard-sidebar .nav-link[href]').forEach(link => {
        try {
            const url = new URL(link.getAttribute('href'), window.location.href);
            const path = url.pathname.replace(/\/+$/, '') || '/';
            if (url.origin !== window.location.origin || !['http:', 'https:'].includes(url.protocol)
                || ['/dashboard', '/dashboard/login', '/dashboard/logout'].includes(path)
                || path.startsWith('/dashboard/action/')) return;
            pages.set(url.pathname + url.search, {
                url: url.pathname + url.search,
                label: link.querySelector('.nav-label')?.textContent.trim() || link.textContent.trim(),
                icon: link.querySelector('.nav-icon')?.getAttribute('src'),
            });
        } catch (_) {
            // Ignore malformed navigation URLs.
        }
    });

    function parseHistory(value) {
        try {
            const parsed = JSON.parse(value || '[]');
            if (!Array.isArray(parsed)) return [];
            const unique = new Map();
            parsed.forEach(value => {
                // Preserve history saved by earlier versions without inventing dates.
                const url = typeof value === 'string' ? value : value?.url;
                if (typeof url !== 'string' || !pages.has(url) || unique.has(url)) return;
                const visitedAt = typeof value?.visitedAt === 'number' && Number.isFinite(value.visitedAt)
                    && value.visitedAt > 0 && value.visitedAt <= Date.now() ? value.visitedAt : null;
                unique.set(url, { url, visitedAt });
            });
            return [...unique.values()].slice(0, maxRecent);
        } catch (_) {
            return [];
        }
    }

    let notes = (read(notesKey) || '').slice(0, 4000);
    let recent = parseHistory(read(historyKey));
    const currentPath = window.location.pathname;
    const current = pages.get(currentPath + window.location.search)
        || pages.get(currentPath);
    if (current) {
        recent = [{ url: current.url, visitedAt: Date.now() }, ...recent.filter(entry => entry.url !== current.url)].slice(0, maxRecent);
        write(historyKey, JSON.stringify(recent));
    }

    function renderNotes() {
        document.querySelectorAll('[data-personal-notes]').forEach(widget => {
            const input = widget.querySelector('[data-notes-input]');
            if (input.dataset.notesReady) return;
            input.value = notes;
            input.dataset.notesReady = 'true';
            widget.querySelector('[data-notes-status]').textContent = storageAvailable
                ? (notes ? 'Saved in this browser.' : 'Your notes save automatically.')
                : 'Browser storage is unavailable. Notes will be kept only on this page.';
        });
    }

    function visitLabel(timestamp) {
        if (!timestamp) return 'Visited earlier';
        const minutes = Math.max(0, Math.floor((Date.now() - timestamp) / 60000));
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes} min ago`;
        const date = new Date(timestamp);
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const time = date.toLocaleTimeString('en', { hour: 'numeric', minute: '2-digit' });
        if (date.toDateString() === today.toDateString()) return `Today at ${time}`;
        if (date.toDateString() === yesterday.toDateString()) return `Yesterday at ${time}`;
        return date.toLocaleDateString('en', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function renderRecent() {
        document.querySelectorAll('[data-recently-visited]').forEach(widget => {
            const list = widget.querySelector('[data-recent-list]');
            list.replaceChildren();
            recent.forEach((entry, index) => {
                const page = pages.get(entry.url);
                if (!page) return;
                const item = document.createElement('li');
                const link = document.createElement('a');
                link.className = 'recent-page-link';
                link.setAttribute('href', page.url);
                const icon = document.createElement('span');
                icon.className = 'recent-page-icon';
                icon.setAttribute('aria-hidden', 'true');
                if (page.icon) {
                    const image = document.createElement('img');
                    image.className = 'nav-icon';
                    image.setAttribute('src', page.icon);
                    image.setAttribute('alt', '');
                    icon.appendChild(image);
                } else {
                    icon.textContent = Array.from(page.label)[0] || '';
                }
                const copy = document.createElement('span');
                copy.className = 'recent-page-copy';
                const title = document.createElement('span');
                title.className = 'recent-page-title';
                title.textContent = page.label;
                const time = document.createElement(entry.visitedAt ? 'time' : 'span');
                time.className = 'recent-page-time';
                time.textContent = `${index === 0 ? 'Last visited · ' : ''}${visitLabel(entry.visitedAt)}`;
                if (entry.visitedAt) {
                    time.setAttribute('datetime', new Date(entry.visitedAt).toISOString());
                    time.setAttribute('title', new Date(entry.visitedAt).toLocaleString('en'));
                }
                copy.appendChild(title);
                copy.appendChild(time);
                const arrow = document.createElement('span');
                arrow.className = 'recent-page-arrow fa-solid fa-arrow-right';
                arrow.setAttribute('aria-hidden', 'true');
                link.appendChild(icon);
                link.appendChild(copy);
                link.appendChild(arrow);
                item.appendChild(link);
                list.appendChild(item);
            });
            const status = widget.querySelector('[data-recent-status]');
            status.hidden = storageAvailable && recent.length > 0;
            status.textContent = storageAvailable
                ? (clearedHistory ? 'History cleared. You can undo this below.' : 'No recent pages yet. Open a page from the sidebar, then return here to quickly find it again.')
                : 'Browser storage is unavailable. History will be kept only on this page.';
            widget.querySelector('[data-recent-clear]').hidden = recent.length === 0;
            widget.querySelector('[data-recent-undo]').hidden = clearedHistory === null;
        });
    }

    function render() {
        renderNotes();
        renderRecent();
    }

    document.addEventListener('input', event => {
        const input = event.target.closest('[data-notes-input]');
        if (!input) return;
        notes = input.value.slice(0, 4000);
        const saved = write(notesKey, notes);
        const status = input.closest('[data-personal-notes]').querySelector('[data-notes-status]');
        clearTimeout(savedTimer);
        savedTimer = setTimeout(() => {
            status.textContent = saved ? 'Saved in this browser.' : 'Could not save. Keep this page open or copy your notes.';
        }, 350);
    });

    document.addEventListener('click', event => {
        const undo = event.target.closest('[data-recent-undo]');
        if (undo && clearedHistory !== null) {
            recent = clearedHistory;
            clearedHistory = null;
            write(historyKey, JSON.stringify(recent));
            renderRecent();
            undo.closest('[data-recently-visited]').querySelector('.recent-page-link')?.focus();
            return;
        }
        const button = event.target.closest('[data-recent-clear]');
        if (!button) return;
        clearedHistory = recent;
        recent = [];
        write(historyKey, '[]');
        renderRecent();
        button.closest('[data-recently-visited]').querySelector('[data-recent-status]').setAttribute('tabindex', '-1');
        button.closest('[data-recently-visited]').querySelector('[data-recent-status]').focus();
    });

    window.addEventListener('storage', event => {
        if (event.key === historyKey || event.key === null) {
            clearedHistory = null;
            recent = parseHistory(event.key === null ? null : event.newValue);
            renderRecent();
        }
        if (event.key === notesKey || event.key === null) {
            notes = (event.key === null ? '' : event.newValue || '').slice(0, 4000);
            document.querySelectorAll('[data-notes-input]').forEach(input => {
                input.value = notes;
                input.closest('[data-personal-notes]').querySelector('[data-notes-status]').textContent = 'Notes updated in another tab.';
            });
        }
    });

    render();
    // Widget layout saves and Undo replace the grid with fresh server markup.
    const grid = document.getElementById('dashboard-widgets');
    if (grid) new MutationObserver(render).observe(grid, { childList: true });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) renderRecent();
    });
})();
