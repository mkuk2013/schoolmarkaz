/**
 * School Markaz — small front-end helpers.
 * Dark-mode toggle persisted in localStorage; sidebar hamburger for mobile.
 */
(function () {
    'use strict';

    // Theme toggle
    var btn = document.getElementById('theme-toggle');
    function applyTheme(t) {
        document.documentElement.dataset.theme = t;
        try { localStorage.setItem('sm-theme', t); } catch (e) {}
        if (btn) btn.textContent = t === 'dark' ? '☀️' : '🌙';
    }
    try {
        var saved = localStorage.getItem('sm-theme');
        if (saved === 'dark' || saved === 'light') applyTheme(saved);
    } catch (e) {}
    if (btn) {
        btn.addEventListener('click', function () {
            applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
        });
    }
    // Expose for inline onclick handlers (legacy pages)
    window.smToggleTheme = function () {
        applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
    };

    // Mobile sidebar
    var ham = document.getElementById('hamburger');
    var sidebar = document.getElementById('sidebar');
    if (ham && sidebar) {
        ham.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
        document.addEventListener('click', function (ev) {
            if (window.innerWidth <= 900 && sidebar.classList.contains('open')
                && !sidebar.contains(ev.target) && ev.target !== ham) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Confirm on dangerous actions
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (ev) {
            if (!confirm(el.getAttribute('data-confirm'))) ev.preventDefault();
        });
    });
})();
