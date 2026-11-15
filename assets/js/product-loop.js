/**
 * Product loop / product details button behavior:
 * 1. Order now — after successful add-to-cart, redirect to checkout.
 * 2. Add to cart — after successful add-to-cart, switch to a
 *    "Added to cart ✓" outline success state on that specific button,
 *    then remove focus so it doesn't visually appear "stuck" filled.
 * 3. Removes WooCommerce's default "View cart" link that gets
 *    inserted next to the clicked button.
 *
 * Two button contexts share this one behavior script, but are kept
 * visually independent on purpose:
 *   - Product loop / related products: identified by class names
 *     (hec-btn-cart / hec-order-now), styled in product-loop.css,
 *     success class is "hec-btn-added".
 *   - Product details page buttons: identified by data-hec-role="cart"
 *     or "order", styled entirely separately in product-details.css,
 *     success class is "hec-pd-btn-added". No class name is shared
 *     between the two, so a styling change in one file can never
 *     bleed into the other.
 */
(function ($) {
    'use strict';

    if (typeof $ === 'undefined') {
        return;
    }

    var checkIconSVG = '<svg class="hec-btn-check" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
        '<path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>' +
        '</svg>';

    function isOrderButton($btn) {
        return $btn.hasClass('hec-order-now') || $btn.attr('data-hec-role') === 'order';
    }

    function isCartButton($btn) {
        return $btn.hasClass('hec-btn-cart') || $btn.attr('data-hec-role') === 'cart';
    }

    $(document.body).on('added_to_cart', function (event, fragments, cart_hash, $button) {
        if (!$button || !$button.length) {
            return;
        }

        // Always remove WooCommerce's inserted "View cart" link,
        // regardless of which button/page this happened on.
        $button.siblings('.added_to_cart.wc-forward').remove();
        $button.parent().find('.added_to_cart.wc-forward').remove();

        // Order now — redirect straight to checkout
        if (isOrderButton($button)) {
            if (typeof hecCheckoutData !== 'undefined') {
                window.location.href = hecCheckoutData.checkoutUrl;
            }
            return;
        }

        // Add to cart — show success state on this specific button
        if (isCartButton($button)) {
            var addedClass = $button.attr('data-hec-role') === 'cart' ? 'hec-pd-btn-added' : 'hec-btn-added';
            $button
                .addClass(addedClass)
                .html(checkIconSVG + ' Added to cart')
                .blur();
        }
    });
})(jQuery);