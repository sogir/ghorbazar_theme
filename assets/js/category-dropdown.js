/**
 * On touch devices (no real hover), tapping a category with children
 * toggles its submenu open/closed instead of navigating immediately.
 * Desktop hover behavior is handled entirely by CSS and ignores this.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var isTouch = window.matchMedia('(hover: none)').matches;
        if (!isTouch) {
            return;
        }

        var items = document.querySelectorAll('.hec-cat-item.has-children');

        items.forEach(function (item) {
            var link = item.querySelector('.hec-cat-link');

            link.addEventListener('click', function (e) {
                if (!item.classList.contains('is-open')) {
                    e.preventDefault(); // first tap opens submenu instead of navigating
                    // close any other open item
                    items.forEach(function (other) {
                        if (other !== item) {
                            other.classList.remove('is-open');
                        }
                    });
                    item.classList.add('is-open');
                }
                // second tap on an already-open item navigates normally
            });
        });
    });
})();