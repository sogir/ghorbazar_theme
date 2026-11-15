/**
 * Wemax Gadgets — base theme JS (mobile menu toggle).
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('.site-header__toggle');
        var nav = document.getElementById('site-navigation');
        var drawerToggle = document.getElementById('hec-catnav-toggle'); // bottom-nav Categories button
        if (!toggle) return;

        // The mobile category drawer (categories + menu links) is the main menu on phones.
        if (drawerToggle) {
            toggle.setAttribute('aria-controls', 'hec-catnav-drawer');
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                drawerToggle.click();
            });
            return;
        }

        if (!nav) { toggle.hidden = true; return; }
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
})();
