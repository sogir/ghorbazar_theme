/**
 * Mobile filter drawer: tapping the fixed left-edge icon tab slides
 * the filter panel in with a backdrop. Closable via the × button,
 * tapping the backdrop, or pressing Escape. Does nothing on desktop
 * — the CSS keeps the panel inline and hides the toggle/backdrop
 * entirely there regardless of these classes.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var toggleBtn = document.getElementById('hec-filter-toggle');
        var closeBtn = document.getElementById('hec-filter-close');
        var backdrop = document.getElementById('hec-filter-backdrop');
        var filterPanel = document.getElementById('hec-shop-filters');

        if (!toggleBtn || !filterPanel) {
            return;
        }

        function openDrawer() {
            filterPanel.classList.add('is-open');
            if (backdrop) backdrop.classList.add('is-open');
        }

        function closeDrawer() {
            filterPanel.classList.remove('is-open');
            if (backdrop) backdrop.classList.remove('is-open');
        }

        toggleBtn.addEventListener('click', openDrawer);
        // Extra openers (e.g. the theme's filter button beside the shop title).
        document.querySelectorAll('[data-hec-filter-open]').forEach(function (btn) {
            btn.addEventListener('click', openDrawer);
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', closeDrawer);
        }
        if (backdrop) {
            backdrop.addEventListener('click', closeDrawer);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDrawer();
            }
        });
    });
})();
