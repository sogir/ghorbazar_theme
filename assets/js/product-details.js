/**
 * Single product page behavior:
 * - Desktop: arrow buttons navigate the gallery (drag/swipe disabled
 *   via CSS on fine-pointer devices).
 * - Mobile: native touch swipe via CSS scroll-snap (no JS needed for
 *   the swipe itself); thumbnails stay in sync either way.
 * - Quantity stepper (+/-, no visible input field).
 */
(function ($) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        // ---- Gallery ----
        var slider = document.getElementById('hec-pd-slider');
        var thumbs = document.querySelectorAll('.hec-pd-thumb');
        var prevBtn = document.getElementById('hec-pd-prev');
        var nextBtn = document.getElementById('hec-pd-next');

        if (slider) {
            var slideCount = slider.querySelectorAll('.hec-pd-slide').length;
            var currentIndex = 0;

            function scrollToIndex(index) {
                if (index < 0) index = 0;
                if (index > slideCount - 1) index = slideCount - 1;
                currentIndex = index;
                var slideWidth = slider.clientWidth;
                slider.scrollTo({ left: slideWidth * index, behavior: 'smooth' });
                setActiveThumb(index);
            }

            function setActiveThumb(index) {
                thumbs.forEach(function (t) { t.classList.remove('is-active'); });
                if (thumbs[index]) {
                    thumbs[index].classList.add('is-active');
                }
            }

            thumbs.forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    var index = parseInt(thumb.getAttribute('data-index'), 10) || 0;
                    scrollToIndex(index);
                });
            });

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    scrollToIndex(currentIndex - 1);
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    scrollToIndex(currentIndex + 1);
                });
            }

            // Keep thumbnails in sync during native touch-swipe on mobile
            var scrollTimeout;
            slider.addEventListener('scroll', function () {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(function () {
                    var slideWidth = slider.clientWidth;
                    var index = Math.round(slider.scrollLeft / slideWidth);
                    currentIndex = index;
                    setActiveThumb(index);
                }, 80);
            });
        }

        // ---- Quantity stepper ----
        var qtyDisplay = document.getElementById('hec-pd-qty');
        var minusBtn = document.querySelector('.hec-pd-qty-minus');
        var plusBtn  = document.querySelector('.hec-pd-qty-plus');
        var pdButtons = document.querySelectorAll('.hec-pd-btn');

        function getQty() {
            return parseInt(qtyDisplay.textContent, 10) || 1;
        }

        function setQty(qty) {
            if (qty < 1) qty = 1;
            qtyDisplay.textContent = qty;
            pdButtons.forEach(function (btn) {
                btn.setAttribute('data-quantity', qty);
            });
        }

        if (minusBtn) {
            minusBtn.addEventListener('click', function () {
                setQty(getQty() - 1);
            });
        }
        if (plusBtn) {
            plusBtn.addEventListener('click', function () {
                setQty(getQty() + 1);
            });
        }
    });
})(jQuery);