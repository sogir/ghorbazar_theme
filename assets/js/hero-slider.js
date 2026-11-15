/**
 * Homepage slider: fade between slides, arrows, dots, swipe, keyboard,
 * autoplay (pauses on hover/focus and when the tab is hidden).
 */
(function () {
    'use strict';

    function init(slider) {
        var slides = slider.querySelectorAll('.hec-slide');
        var dots = slider.querySelectorAll('.hec-slider-dot');
        if (slides.length < 2) return;

        var current = 0;
        var delay = parseInt(slider.getAttribute('data-autoplay'), 10) || 0;
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var timer = null;

        function go(i) {
            current = (i + slides.length) % slides.length;
            slides.forEach(function (s, n) {
                var on = n === current;
                s.classList.toggle('is-active', on);
                s.setAttribute('aria-hidden', on ? 'false' : 'true');
                s.querySelectorAll('a').forEach(function (a) { a.tabIndex = on ? 0 : -1; });
            });
            dots.forEach(function (d, n) { d.classList.toggle('is-active', n === current); });
        }

        function stop() { if (timer) { clearInterval(timer); timer = null; } }
        function start() {
            stop();
            if (delay > 0 && !reduce) timer = setInterval(function () { go(current + 1); }, delay * 1000);
        }

        var prev = slider.querySelector('.hec-slider-prev');
        var next = slider.querySelector('.hec-slider-next');
        if (prev) prev.addEventListener('click', function () { go(current - 1); start(); });
        if (next) next.addEventListener('click', function () { go(current + 1); start(); });
        dots.forEach(function (d, n) { d.addEventListener('click', function () { go(n); start(); }); });

        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', start);
        slider.addEventListener('focusin', stop);
        slider.addEventListener('focusout', start);
        document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });

        slider.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') { go(current - 1); start(); }
            if (e.key === 'ArrowRight') { go(current + 1); start(); }
        });

        // Touch swipe.
        var x0 = null, y0 = null;
        slider.addEventListener('touchstart', function (e) {
            x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; stop();
        }, { passive: true });
        slider.addEventListener('touchend', function (e) {
            if (x0 === null) return;
            var dx = e.changedTouches[0].clientX - x0;
            var dy = e.changedTouches[0].clientY - y0;
            if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) go(current + (dx < 0 ? 1 : -1));
            x0 = y0 = null;
            start();
        });

        start();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.hec-slider').forEach(init);
    });
})();
