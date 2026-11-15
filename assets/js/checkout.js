/**
 * Custom checkout page behavior.
 *
 * - "Confirm order" button: clicks WooCommerce's own real #place_order
 *   button (rendered from checkout/payment.php) so the actual order
 *   submission is 100% native WooCommerce — nothing custom here.
 * - Delivery area radio change: triggers WooCommerce's own
 *   'update_checkout' event (native shipping/totals recalculation).
 * - Remove item / quantity +/-: small custom AJAX endpoints that
 *   update the real cart, then also trigger 'update_checkout' so
 *   shipping and coupon totals stay in sync with the new quantities.
 * - Coupon apply: uses WooCommerce's own apply_coupon AJAX action
 *   directly (the same one its default coupon form uses), since our
 *   coupon UI is custom markup rather than WooCommerce's template.
 */
(function ($) {
    'use strict';

    if (typeof $ === 'undefined') {
        return;
    }

    // ---- Order button(s) trigger the real WooCommerce submit button.
    // Matches both the main button and the mobile-only duplicate one
    // after the disclaimer — they share this class. ----
    $(document.body).on('click', '.hec-co-place-order-trigger', function (e) {
        e.preventDefault();
        var $real = $('#place_order');
        if ($real.length) {
            $real.trigger('click');
        }
    });

    // ---- Delivery area change -> native WooCommerce shipping recalculation ----
    $(document.body).on('change', 'input.shipping_method', function () {
        $(document.body).trigger('update_checkout');
    });

    // ---- Remove item ----
    $(document.body).on('click', '.hec-co-remove-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var key = $btn.data('cart-item-key');
        if (!key) {
            return;
        }
        var $row = $btn.closest('.hec-co-row');
        $row.addClass('hec-co-updating');

        $.post(hecCheckoutAjax.ajaxUrl, {
            action: 'hec_checkout_remove_item',
            nonce: hecCheckoutAjax.nonce,
            cart_item_key: key
        }).done(function (resp) {
            if (resp && resp.success) {
                if (resp.data.cart_count <= 0) {
                    window.location.href = hecCheckoutAjax.homeUrl;
                    return;
                }
                $('#hec-co-summary-table-wrap').replaceWith(resp.data.table_html);
                $(document.body).trigger('update_checkout');
            } else {
                $row.removeClass('hec-co-updating');
            }
        }).fail(function () {
            $row.removeClass('hec-co-updating');
        });
    });

    // ---- Quantity +/- ----
    function changeQty(key, newQty) {
        var $row = $('.hec-co-row[data-cart-item-key="' + key + '"]');
        $row.addClass('hec-co-updating');

        $.post(hecCheckoutAjax.ajaxUrl, {
            action: 'hec_checkout_update_qty',
            nonce: hecCheckoutAjax.nonce,
            cart_item_key: key,
            qty: newQty
        }).done(function (resp) {
            if (resp && resp.success) {
                $('#hec-co-summary-table-wrap').replaceWith(resp.data.table_html);
                $(document.body).trigger('update_checkout');
            } else {
                $row.removeClass('hec-co-updating');
            }
        }).fail(function () {
            $row.removeClass('hec-co-updating');
        });
    }

    $(document.body).on('click', '.hec-co-qty-minus', function () {
        var key = $(this).data('cart-item-key');
        var current = parseInt($(this).siblings('.hec-co-qty-val').text(), 10) || 1;
        if (current > 1) {
            changeQty(key, current - 1);
        }
    });

    $(document.body).on('click', '.hec-co-qty-plus', function () {
        var key = $(this).data('cart-item-key');
        var current = parseInt($(this).siblings('.hec-co-qty-val').text(), 10) || 1;
        changeQty(key, current + 1);
    });

    // ---- Coupon apply — uses WooCommerce's own apply_coupon AJAX action ----
    $(document.body).on('click', '#hec-co-coupon-apply', function () {
        var code = $('#hec-co-coupon-input').val();
        var $msg = $('#hec-co-coupon-msg');

        if (!code) {
            return;
        }

        if (typeof wc_checkout_params === 'undefined') {
            return;
        }

        $msg.removeClass('hec-co-msg-success hec-co-msg-error').text('...');

        $.ajax({
            type: 'POST',
            url: wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'apply_coupon'),
            data: {
                security: wc_checkout_params.apply_coupon_nonce,
                coupon_code: code
            }
        }).done(function (resp) {
            // WooCommerce returns raw HTML notices on success/error for this endpoint
            var isError = typeof resp === 'string' && resp.indexOf('woocommerce-error') !== -1;
            if (isError) {
                var tmp = $('<div>' + resp + '</div>');
                $msg.addClass('hec-co-msg-error').text(tmp.text().trim() || 'This coupon is not valid.');
            } else {
                $msg.addClass('hec-co-msg-success').text('Coupon applied!');
                $('#hec-co-coupon-input').val('');
                $(document.body).trigger('update_checkout');
            }
        }).fail(function () {
            $msg.addClass('hec-co-msg-error').text('Could not apply the coupon.');
        });
    });

})(jQuery);
