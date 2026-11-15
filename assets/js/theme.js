/**
 * Wemax Gadgets — base theme JS (mobile menu toggle).
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('.site-header__toggle');
        var nav = document.getElementById('site-navigation');
        if (!toggle) return;
        if (!nav) { toggle.hidden = true; return; }

        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
})();
