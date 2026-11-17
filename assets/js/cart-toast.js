/**
 * Add-to-cart popup + cart icon wiggle (header mini-cart AND mobile
 * bottom nav, both at once).
 *
 * Fires off WooCommerce's own `added_to_cart` body event (dispatched by
 * wc-add-to-cart.js after every successful AJAX add: product loop,
 * product details, related products), so no extra AJAX wiring is needed.
 * The popup shows the product that was added (photo + name, read from
 * the card/page the button lives in) and a big Checkout button.
 */
(function ($) {
    'use strict';

    if (typeof $ === 'undefined') {
        return;
    }

    var DURATION = 7000; // ms before auto-hide (paused while hovered/touched)
    var toastWrap = null;
    var hideTimer = null;

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

    /** Find the added product's name + image from where the button was clicked. */
    function productInfo($button) {
        var info = { name: '', img: '' };
        if (!$button || !$button.length) return info;

        var $card = $button.closest('.hec-product-card');
        if ($card.length) {
            info.name = $.trim($card.find('.hec-product-name').first().text());
            info.img = $card.find('.hec-product-image img').first().attr('src') || '';
            return info;
        }
        // Single product page (variable products: name includes the chosen option).
        info.name = $.trim($button.attr('data-hec-toast-name') || '') ||
            $.trim($('.hec-pd-title').first().text()) || $.trim($('h1').first().text());
        var $img = $('.hec-pd-main-image img, .hec-pd-gallery img, .woocommerce-product-gallery img').first();
        info.img = $img.attr('src') || '';
        return info;
    }

    function showToast($button) {
        var data = (typeof hecCartToastData !== 'undefined') ? hecCartToastData : {};
        var wrap = getToastWrap();
        var p = productInfo($button);

        // One popup at a time: a quick second add replaces the current one.
        wrap.innerHTML = '';

        var toast = document.createElement('div');
        toast.className = 'hec-cart-toast';
        toast.setAttribute('role', 'status');
        toast.innerHTML =
            '<button type="button" class="hec-cart-toast-close" aria-label="Close">&times;</button>' +
            '<div class="hec-cart-toast-head">' +
                '<span class="hec-cart-toast-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>' +
                '<span class="hec-cart-toast-title"></span>' +
            '</div>' +
            '<div class="hec-cart-toast-product">' +
                '<span class="hec-cart-toast-thumb"></span>' +
                '<span class="hec-cart-toast-name"></span>' +
            '</div>' +
            '<a class="hec-cart-toast-btn">' +
                '<span class="hec-cart-toast-btn-text"></span>' +
                '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
            '</a>' +
            '<button type="button" class="hec-cart-toast-continue"></button>' +
            '<span class="hec-cart-toast-timer"><span></span></span>';

        toast.querySelector('.hec-cart-toast-title').textContent = data.message || 'Added to cart!';
        toast.querySelector('.hec-cart-toast-btn-text').textContent = data.checkoutText || 'Checkout now';
        toast.querySelector('.hec-cart-toast-continue').textContent = data.continueText || 'Continue shopping';

        var btn = toast.querySelector('.hec-cart-toast-btn');
        btn.href = data.checkoutUrl || '#';

        var productRow = toast.querySelector('.hec-cart-toast-product');
        if (p.name) {
            toast.querySelector('.hec-cart-toast-name').textContent = p.name;
            if (p.img) {
                var img = document.createElement('img');
                img.src = p.img;
                img.alt = '';
                toast.querySelector('.hec-cart-toast-thumb').appendChild(img);
            } else {
                toast.querySelector('.hec-cart-toast-thumb').remove();
            }
        } else {
            productRow.remove();
        }

        wrap.appendChild(toast);
        void toast.offsetWidth; // reflow so the entrance animation runs
        toast.classList.add('is-visible');

        var bar = toast.querySelector('.hec-cart-toast-timer span');
        var remaining = DURATION;
        var startedAt = 0;

        function hideToast() {
            clearTimeout(hideTimer);
            toast.classList.remove('is-visible');
            toast.classList.add('is-leaving');
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 250);
        }
        function startTimer() {
            startedAt = Date.now();
            bar.style.transition = 'transform ' + remaining + 'ms linear';
            bar.style.transform = 'scaleX(0)';
            clearTimeout(hideTimer);
            hideTimer = setTimeout(hideToast, remaining);
        }
        function pauseTimer() {
            clearTimeout(hideTimer);
            remaining = Math.max(800, remaining - (Date.now() - startedAt));
            var scale = remaining / DURATION;
            bar.style.transition = 'none';
            bar.style.transform = 'scaleX(' + scale + ')';
        }

        toast.querySelector('.hec-cart-toast-close').addEventListener('click', hideToast);
        toast.querySelector('.hec-cart-toast-continue').addEventListener('click', hideToast);
        toast.addEventListener('mouseenter', pauseTimer);
        toast.addEventListener('mouseleave', startTimer);
        toast.addEventListener('touchstart', pauseTimer, { passive: true });
        toast.addEventListener('touchend', startTimer);

        requestAnimationFrame(function () { requestAnimationFrame(startTimer); });
    }

    function wiggleCartIcon() {
        var icons = document.querySelectorAll('.hec-mini-cart-icon-wrap, .hec-mnav-cart-icon-wrap');
        icons.forEach(function (icon) {
            icon.classList.remove('hec-wiggle');
            void icon.offsetWidth;
            icon.classList.add('hec-wiggle');
            icon.addEventListener('animationend', function handler() {
                icon.classList.remove('hec-wiggle');
                icon.removeEventListener('animationend', handler);
            });
        });
    }

    $(document.body).on('added_to_cart', function (e, fragments, cartHash, $button) {
        // "Order now" buttons go straight to checkout; no popup needed there.
        if ($button && $button.hasClass && $button.hasClass('hec-order-now')) {
            wiggleCartIcon();
            return;
        }
        showToast($button);
        wiggleCartIcon();
    });
})(jQuery);
