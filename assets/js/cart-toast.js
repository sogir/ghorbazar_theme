/**
 * Add-to-cart toast + cart icon wiggle (header mini-cart AND mobile
 * bottom nav, both at once).
 *
 * Both fire off WooCommerce's own `added_to_cart` body event (already
 * dispatched by wc-add-to-cart.js after every successful ajax add —
 * product loop, product details, related products, anywhere), so no
 * extra AJAX wiring is needed here.
 */
(function ($) {
    'use strict';

    if (typeof $ === 'undefined') {
        return;
    }

    var toastWrap = null;
    var autoHideTimer = null;

    function getToastWrap() {
        if (toastWrap && document.body.contains(toastWrap)) {
            return toastWrap;
        }
        toastWrap = document.createElement('div');
        toastWrap.className = 'hec-cart-toast-wrap';
        toastWrap.setAttribute('aria-live', 'polite');
        document.body.appendChild(toastWrap);
        return toastWrap;
    }

    function showToast() {
        var data = (typeof hecCartToastData !== 'undefined') ? hecCartToastData : {};
        var wrap = getToastWrap();

        // Only one toast at a time — a rapid second add-to-cart just
        // replaces the current toast instead of stacking another one.
        wrap.innerHTML = '';

        var toast = document.createElement('div');
        toast.className = 'hec-cart-toast';
        toast.innerHTML =
            '<span class="hec-cart-toast-icon">' +
                '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
            '</span>' +
            '<span class="hec-cart-toast-body">' +
                '<span class="hec-cart-toast-text"></span>' +
                '<a class="hec-cart-toast-link"></a>' +
            '</span>' +
            '<button type="button" class="hec-cart-toast-close" aria-label="Close">&times;</button>';

        toast.querySelector('.hec-cart-toast-text').textContent = data.message || 'Added to cart.';

        var link = toast.querySelector('.hec-cart-toast-link');
        link.href = data.checkoutUrl || '#';
        link.textContent = data.checkoutText || 'Checkout';

        wrap.appendChild(toast);

        // Force reflow so the opacity/transform transition actually runs.
        void toast.offsetWidth;
        toast.classList.add('is-visible');

        function hideToast() {
            toast.classList.remove('is-visible');
            setTimeout(function () {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 220);
        }

        toast.querySelector('.hec-cart-toast-close').addEventListener('click', hideToast);

        clearTimeout(autoHideTimer);
        autoHideTimer = setTimeout(hideToast, 5000);
    }

    function wiggleCartIcon() {
        // Both the header mini-cart icon(s) — there can be more than
        // one, e.g. separate desktop/mobile header instances — and the
        // mobile bottom nav's cart (Checkout) icon.
        var icons = document.querySelectorAll('.hec-mini-cart-icon-wrap, .hec-mnav-cart-icon-wrap');

        icons.forEach(function (icon) {
            icon.classList.remove('hec-wiggle');
            // Restart the animation even if a very fast second
            // add-to-cart re-triggers this mid-wiggle.
            void icon.offsetWidth;
            icon.classList.add('hec-wiggle');

            icon.addEventListener('animationend', function handler() {
                icon.classList.remove('hec-wiggle');
                icon.removeEventListener('animationend', handler);
            });
        });
    }

    $(document.body).on('added_to_cart', function () {
        showToast();
        wiggleCartIcon();
    });
})(jQuery);
