/**
 * Product search box: debounced AJAX suggestions dropdown as the
 * customer types. The form itself still submits normally (Enter or
 * the search button) to the real WordPress search results page —
 * this dropdown is just a shortcut for the obvious match.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var boxes = document.querySelectorAll('.hec-search-box');

        boxes.forEach(function (box) {
            var input = box.querySelector('.hec-search-input');
            var panel = box.querySelector('.hec-search-suggestions');

            if (!input || !panel || typeof hecSearchAjax === 'undefined') {
                return;
            }

            var debounceTimer = null;
            var requestSeq = 0;
            var lastTerm = '';

            function closePanel() {
                panel.classList.remove('is-open');
            }

            function openPanel() {
                panel.classList.add('is-open');
            }

            function escapeHtml(str) {
                var div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            function renderLoading() {
                panel.innerHTML = '<div class="hec-search-suggestion-loading">Searching...</div>';
                openPanel();
            }

            function renderResults(items, term, searchUrl) {
                if (!items.length) {
                    panel.innerHTML = '<div class="hec-search-suggestion-empty">No products found.</div>';
                    openPanel();
                    return;
                }

                var html = items.map(function (item) {
                    return '<a class="hec-search-suggestion" href="' + item.url + '">' +
                        item.image +
                        '<span class="hec-search-suggestion-info">' +
                            '<span class="hec-search-suggestion-name">' + escapeHtml(item.name) + '</span>' +
                            '<span class="hec-search-suggestion-price">' + item.price + '</span>' +
                        '</span>' +
                    '</a>';
                }).join('');

                if (searchUrl) {
                    html += '<a class="hec-search-suggestion-more" href="' + searchUrl + '">See all results ("' + escapeHtml(term) + '")</a>';
                }

                panel.innerHTML = html;
                openPanel();
            }

            function fetchSuggestions(term) {
                var seq = ++requestSeq;

                var url = hecSearchAjax.ajaxUrl +
                    '?action=hec_search_products' +
                    '&nonce=' + encodeURIComponent(hecSearchAjax.nonce) +
                    '&term=' + encodeURIComponent(term);

                fetch(url, { credentials: 'same-origin' })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        // Ignore stale responses from an earlier, slower
                        // request that resolves after a newer one.
                        if (seq !== requestSeq) {
                            return;
                        }
                        if (data && data.success) {
                            renderResults(data.data.items, term, data.data.search_url);
                        } else {
                            closePanel();
                        }
                    })
                    .catch(function () {
                        if (seq === requestSeq) {
                            closePanel();
                        }
                    });
            }

            input.addEventListener('input', function () {
                var term = input.value.trim();
                lastTerm = term;

                clearTimeout(debounceTimer);

                if (term.length < 2) {
                    closePanel();
                    return;
                }

                debounceTimer = setTimeout(function () {
                    renderLoading();
                    fetchSuggestions(term);
                }, 300);
            });

            input.addEventListener('focus', function () {
                if (lastTerm.length >= 2 && panel.innerHTML) {
                    openPanel();
                }
            });

            document.addEventListener('click', function (e) {
                if (!box.contains(e.target)) {
                    closePanel();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closePanel();
                }
            });
        });
    });
})();
