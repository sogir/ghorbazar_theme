/**
 * Single product page behavior:
 * - Desktop: arrow buttons navigate the gallery (drag/swipe disabled
 *   via CSS on fine-pointer devices).
 * - Mobile: native touch swipe via CSS scroll-snap (no JS needed for
 *   the swipe itself); thumbnails stay in sync either way.
 * - Quantity stepper (+/-, no visible input field).
 * - Variable products: option chips (Color / Size …) pick the variation,
 *   update price / discount / SKU / image / stock, and Order now + Add to
 *   cart post the chosen variation to wc-ajax=hec_add_to_cart, then fire
 *   WooCommerce's normal `added_to_cart` event (mini cart, toast and the
 *   Order now → checkout redirect in product-loop.js all react to it).
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

        // ---- Variable products ----
        var picker = document.querySelector('[data-hec-variations]');
        if (picker) {
            initVariationPicker(picker);
        }
    });

    function initVariationPicker(picker) {
        var root = picker.closest('.hec-product-details');
        var dataEl = picker.querySelector('.hec-pd-var-data');
        var data;
        try {
            data = JSON.parse(dataEl ? dataEl.textContent : '{}');
        } catch (err) {
            return;
        }
        var attrs = data.attributes || [];
        var variations = data.variations || [];
        var rows = picker.querySelectorAll('.hec-pd-var-row');
        var msg = picker.querySelector('.hec-pd-var-msg');
        var buttons = root.querySelectorAll('.hec-pd-btn[data-hec-variable]');
        var priceEl = root.querySelector('.hec-pd-price');
        var pill = root.querySelector('.hec-pd-discount-pill');
        var skuWrap = root.querySelector('.hec-pd-sku');
        var skuVal = root.querySelector('.hec-pd-sku-value');
        var firstImg = root.querySelector('.hec-pd-slide img');
        var firstThumb = root.querySelector('.hec-pd-thumb img');
        var contactLinks = root.querySelectorAll('.hec-pd-whatsapp, .hec-pd-messenger');

        var original = {
            price: priceEl ? priceEl.innerHTML : '',
            pill: pill ? pill.textContent : '',
            pillHidden: pill ? pill.hidden : true,
            sku: skuWrap ? skuWrap.getAttribute('data-default-sku') : '',
            img: firstImg ? firstImg.getAttribute('src') : '',
            thumb: firstThumb ? firstThumb.getAttribute('src') : '',
            contacts: Array.prototype.map.call(contactLinks, function (a) { return a.href; })
        };
        buttons.forEach(function (btn) { btn.setAttribute('data-hec-html', btn.innerHTML); });

        var selected = {};
        var current = null;

        // A variation value of '' means "Any …" in the admin.
        function matches(variation, sel) {
            for (var key in sel) {
                if (!sel[key]) continue;
                var v = variation.attributes[key];
                if (v && v !== sel[key]) return false;
            }
            return true;
        }

        function findVariation() {
            for (var i = 0; i < attrs.length; i++) {
                if (!selected[attrs[i].key]) return null;
            }
            for (var j = 0; j < variations.length; j++) {
                if (matches(variations[j], selected)) return variations[j];
            }
            return null;
        }

        function labelFor(row, value) {
            var b = row.querySelector('.hec-pd-opt[data-value="' + cssEscape(value) + '"]');
            return b ? b.getAttribute('data-label') : value;
        }

        function cssEscape(v) {
            return window.CSS && CSS.escape ? CSS.escape(v) : String(v).replace(/["\\]/g, '\\$&');
        }

        function selectionText() {
            var parts = [];
            rows.forEach(function (row) {
                var key = row.getAttribute('data-attribute');
                if (selected[key]) parts.push(labelFor(row, selected[key]));
            });
            return parts.join(', ');
        }

        function setMessage(text, isError) {
            if (!msg) return;
            msg.textContent = text || '';
            msg.classList.toggle('is-error', !!isError);
        }

        function render() {
            rows.forEach(function (row) {
                var key = row.getAttribute('data-attribute');
                var currentLabel = row.querySelector('.hec-pd-var-current');
                if (currentLabel) currentLabel.textContent = selected[key] ? labelFor(row, selected[key]) : '';
                row.classList.toggle('is-missing', false);

                row.querySelectorAll('.hec-pd-opt').forEach(function (opt) {
                    var value = opt.getAttribute('data-value');
                    var isOn = selected[key] === value;
                    opt.classList.toggle('is-selected', isOn);
                    opt.setAttribute('aria-pressed', isOn ? 'true' : 'false');

                    // Is there a buyable variation with this value + the other choices?
                    var test = {};
                    for (var k in selected) test[k] = selected[k];
                    test[key] = value;
                    var ok = variations.some(function (v) { return v.buyable && matches(v, test); });
                    opt.classList.toggle('is-unavailable', !ok);
                });
            });

            current = findVariation();
            var complete = attrs.every(function (a) { return !!selected[a.key]; });

            // Price, discount, SKU, image.
            if (priceEl) priceEl.innerHTML = current && current.price_html ? current.price_html : original.price;
            if (pill) {
                if (current) {
                    pill.hidden = !(current.discount > 0);
                    pill.textContent = current.discount + '% OFF';
                } else {
                    pill.hidden = original.pillHidden;
                    pill.textContent = original.pill;
                }
            }
            if (skuWrap && skuVal) {
                var sku = current && current.sku ? current.sku : original.sku;
                skuVal.textContent = sku;
                skuWrap.hidden = !sku;
            }
            var img = current && current.image ? current.image : original.img;
            if (firstImg && img && firstImg.getAttribute('src') !== img) {
                firstImg.setAttribute('src', img);
                firstImg.removeAttribute('srcset');
                if (firstThumb) firstThumb.setAttribute('src', current && current.image ? current.image : original.thumb);
                var slider = document.getElementById('hec-pd-slider');
                if (slider) slider.scrollTo({ left: 0, behavior: 'smooth' });
            }

            // Stock / availability message.
            if (current && !current.buyable) {
                setMessage(current.stock_text || 'This option is out of stock.', true);
            } else if (complete && !current) {
                setMessage('This combination is not available. Please choose another option.', true);
            } else {
                setMessage(current && current.stock_text ? current.stock_text : '', false);
            }

            var ready = !!(current && current.buyable);
            var name = root.getAttribute('data-product-name') || '';
            var choice = selectionText();
            buttons.forEach(function (btn) {
                btn.classList.toggle('is-disabled', !ready);
                btn.setAttribute('aria-disabled', ready ? 'false' : 'true');
                btn.setAttribute('data-hec-toast-name', choice ? name + ' - ' + choice : name);
                // A new choice is a new item: reset the "Added to cart" state.
                if (btn.classList.contains('hec-pd-btn-added')) {
                    btn.classList.remove('hec-pd-btn-added');
                    btn.innerHTML = btn.getAttribute('data-hec-html');
                }
            });

            updateContactLinks(name, choice);
        }

        function updateContactLinks(name, choice) {
            contactLinks.forEach(function (a, i) {
                if (!current) {
                    a.href = original.contacts[i];
                    return;
                }
                try {
                    var u = new URL(original.contacts[i]);
                    var text = '🛒 ' + name + (choice ? ' (' + choice + ')' : '') +
                        ' - ' + current.price + ' TK\n' + (root.getAttribute('data-product-url') || '') +
                        '\n\nI would like to order this.';
                    u.searchParams.set('text', text);
                    a.href = u.toString();
                } catch (err) { /* keep the original link */ }
            });
        }

        picker.addEventListener('click', function (e) {
            var opt = e.target.closest('.hec-pd-opt');
            if (!opt) return;
            var row = opt.closest('.hec-pd-var-row');
            var key = row.getAttribute('data-attribute');
            var value = opt.getAttribute('data-value');
            selected[key] = selected[key] === value ? '' : value; // click again to clear
            // Picked a value that doesn't exist with the other choices?
            // Keep the new pick and clear the others that conflict.
            if (selected[key] && !variations.some(function (v) { return matches(v, selected); })) {
                for (var k in selected) {
                    if (k !== key) selected[k] = '';
                }
            }
            render();
        });

        // Defaults from the admin ("Default Form Values"), or the only option.
        attrs.forEach(function (a) {
            if (a.default) {
                selected[a.key] = a.default;
            } else if (a.options.length === 1) {
                selected[a.key] = a.options[0].value;
            }
        });
        render();

        // ---- Order now / Add to cart for the chosen variation ----
        $(root).on('click', '.hec-pd-btn[data-hec-variable]', function (e) {
            e.preventDefault();
            var $btn = $(this);
            if ($btn.hasClass('loading')) return;

            if (!current) {
                var missing = [];
                rows.forEach(function (row) {
                    var key = row.getAttribute('data-attribute');
                    if (!selected[key]) {
                        row.classList.add('is-missing');
                        var a = attrs.filter(function (x) { return x.key === key; })[0];
                        if (a) missing.push(a.label);
                    }
                });
                setMessage(missing.length ? 'Please select ' + missing.join(' and ') + '.' : 'Please choose an available option.', true);
                picker.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            if (!current.buyable) {
                setMessage(current.stock_text || 'This option is out of stock.', true);
                return;
            }

            var payload = {
                product_id: root.getAttribute('data-product-id'),
                variation_id: current.id,
                quantity: parseInt($btn.attr('data-quantity'), 10) || 1
            };
            for (var key in selected) payload[key] = selected[key];

            var ajaxUrl = (window.hecVariationData && hecVariationData.ajaxUrl) ||
                (window.wc_add_to_cart_params ? wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'hec_add_to_cart') : '/?wc-ajax=hec_add_to_cart');

            $btn.addClass('loading');
            setMessage('', false);
            $(document.body).trigger('adding_to_cart', [$btn, payload]);

            $.post(ajaxUrl, payload).done(function (res) {
                if (!res || res.error || !res.fragments) {
                    setMessage((res && res.message) || 'Sorry, something went wrong. Please try again.', true);
                    return;
                }
                $(document.body).trigger('added_to_cart', [res.fragments, res.cart_hash, $btn]);
            }).fail(function () {
                setMessage('Sorry, something went wrong. Please try again.', true);
            }).always(function () {
                $btn.removeClass('loading');
            });
        });
    }
})(jQuery);