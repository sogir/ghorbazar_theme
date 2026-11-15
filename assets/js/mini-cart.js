/**
 * Mini cart dropdown: click toggles the panel open/closed (works on
 * every device). On desktop, CSS also opens it on hover — this JS
 * doesn't need to handle that case, hover is pure CSS.
 *
 * The [hec_mini_cart] shortcode is placed in both the desktop and
 * mobile header rows, so more than one `.hec-mini-cart` instance can
 * exist in the DOM at once (CSS just hides whichever doesn't match
 * the current breakpoint). Everything here is scoped per-wrapper via
 * querySelectorAll/closures instead of getElementById, so each
 * instance opens/closes independently — using a shared id would only
 * ever bind to the first instance in the DOM (desktop) and leave the
 * other (mobile) completely inert.
 *
 * Item removal itself is NOT handled here — the remove buttons carry
 * WooCommerce's own `remove_from_cart_button` class + data attributes,
 * which WooCommerce's already-loaded wc-cart-fragments.js intercepts
 * automatically (AJAX removal + fragment refresh, including this panel).
 *
 * IMPORTANT: that fragment refresh REPLACES `.hec-mini-cart-panel` with
 * a freshly rendered element, which never carries the "is-open" class.
 * On desktop this is invisible — CSS keeps the panel visually open via
 * :hover regardless of the class. On mobile, only that class controls
 * visibility, so without tracking + restoring it here, removing an
 * item on mobile makes the panel silently snap shut right when it
 * should be showing the updated cart — this is what fixes that.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var wrappers = document.querySelectorAll('.hec-mini-cart');

        if (!wrappers.length) {
            return;
        }

        wrappers.forEach(function (wrapper) {
            var trigger = wrapper.querySelector('.hec-mini-cart-trigger');

            if (!trigger) {
                return;
            }

            var wasOpen = false;
            var suppressOutsideClose = false;

            function getPanel() {
                // Re-queried each time since WooCommerce's fragment
                // refresh replaces this element's contents after any
                // add/remove action. The wrapper itself is never
                // replaced, so it stays a stable scope to query within.
                return wrapper.querySelector('.hec-mini-cart-panel');
            }

            function openPanel() {
                var panel = getPanel();
                if (panel) {
                    panel.classList.add('is-open');
                    wasOpen = true;
                }
            }

            function closePanel() {
                var panel = getPanel();
                if (panel) {
                    panel.classList.remove('is-open');
                }
                wasOpen = false;
            }

            // Briefly ignore "click outside" while a click inside the
            // panel (e.g. the remove button) is being processed by
            // WooCommerce's own AJAX handler — this does NOT stop the
            // click from bubbling (so WooCommerce's document-body
            // delegated remove handler still receives it normally), it
            // only tells our own outside-click-closer to stand down for
            // a moment, in case the panel gets replaced mid-click.
            wrapper.addEventListener('click', function () {
                suppressOutsideClose = true;
                setTimeout(function () {
                    suppressOutsideClose = false;
                }, 400);
            });

            var triggerDebounce = false;
            function handleTriggerActivate(e) {
                if (triggerDebounce) {
                    return;
                }
                triggerDebounce = true;
                setTimeout(function () { triggerDebounce = false; }, 350);

                e.preventDefault();
                e.stopPropagation();
                var panel = getPanel();
                if (!panel) {
                    return;
                }
                if (panel.classList.contains('is-open')) {
                    closePanel();
                } else {
                    openPanel();
                }
            }

            // Both click and touchend are bound (with a debounce guard so
            // they can't double-fire on the same tap) — belt-and-braces
            // in case a click synthesized from a touch is ever delayed or
            // swallowed in a particular mobile browser/webview context.
            trigger.addEventListener('click', handleTriggerActivate);
            trigger.addEventListener('touchend', handleTriggerActivate);

            document.addEventListener('click', function (e) {
                if (suppressOutsideClose) {
                    return;
                }
                var panel = getPanel();
                if (panel && panel.classList.contains('is-open') && !wrapper.contains(e.target)) {
                    closePanel();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closePanel();
                }
            });

            // WooCommerce fires this after any fragment refresh (add to
            // cart, remove from cart, etc.) — the panel element itself
            // has just been replaced, so if it was open before, re-apply
            // the open state to the new element.
            jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function () {
                if (wasOpen) {
                    var panel = getPanel();
                    if (panel) {
                        panel.classList.add('is-open');
                    }
                }
            });
        });
    });
})();
