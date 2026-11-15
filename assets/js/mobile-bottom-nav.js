/**
 * Mobile bottom nav: the Categories button slides the category drawer
 * in with a backdrop, same interaction as the shop page's mobile
 * filter drawer. Closable via the × button, tapping the backdrop, or
 * pressing Escape. Home/Checkout are plain links, no JS needed.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var toggleBtn = document.getElementById('hec-catnav-toggle');
        var closeBtn = document.getElementById('hec-catnav-close');
        var backdrop = document.getElementById('hec-catnav-backdrop');
        var drawer = document.getElementById('hec-catnav-drawer');

        if (!toggleBtn || !drawer) {
            return;
        }

        function openDrawer() {
            drawer.classList.add('is-open');
            if (backdrop) backdrop.classList.add('is-open');
        }

        function closeDrawer() {
            drawer.classList.remove('is-open');
            if (backdrop) backdrop.classList.remove('is-open');
        }

        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openDrawer();
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
