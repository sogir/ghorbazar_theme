# Ecomx Bookshop

A standalone (parent) WordPress theme for online stores running WooCommerce
and Elementor. No parent theme required. All store features live in
`/snippets/` and are auto-loaded, so the theme drops into any site.

## Requirements
- WordPress 6.4+, PHP 7.4+
- WooCommerce (needed by every store snippet)
- Elementor (optional; Elementor Pro Theme Builder fully supported)
- CartFlows (optional; the theme stays out of CartFlows funnel steps)

## Install
1. Copy this folder to `wp-content/themes/`.
2. Appearance → Themes → activate **Ecomx Bookshop**.
3. Appearance → Customize → **Store Details**: phone, WhatsApp, Messenger, delivery charges.
4. Appearance → Customize → **Homepage Slider** and **Footer**.
5. Appearance → Menus: assign **Primary Menu** and **Footer Menu**.

Per-site values are stored in the database, never in theme files, so
theme updates can't overwrite them. (Optional file alternative:
`wp-content/ecomx-config.php`, see `config.sample.php`.)

## Updates (push once, every site updates)
1. One-time: the theme's git repo is on GitHub, and `style.css` has
   `Update URI: https://github.com/OWNER/REPO`.
2. Make your changes, then run:
   ```
   bin/release.sh patch     # or minor / major / 2.5.0
   ```
   This bumps `Version` in style.css, commits, tags `vX.Y.Z` and pushes.
3. GitHub Actions (`.github/workflows/release.yml`) builds
   `ecomx-bookshop.zip` and publishes a Release.
4. Every site running the theme sees the update and **installs it
   automatically** (WordPress checks about every 12 hours; Dashboard →
   Updates → *Check again* to pull it immediately).

Private repo: add `define( 'ECOMX_GITHUB_TOKEN', 'github_pat_…' );` to
each site's wp-config.php (fine-grained token, read-only Contents).
Turn off auto-updates on a site: `define( 'ECOMX_AUTO_UPDATE', false );`.

## Structure
```
style.css            Theme header
functions.php        Supports, menus, assets, Elementor locations, snippet loader
header.php/footer.php  Wrap template-parts/header.php & footer.php
index.php            Blog / archive / search fallback
singular.php         Posts & pages (Elementor pages render edge to edge)
woocommerce.php      Shop / category / product wrapper
404.php, comments.php
templates/           Page templates: Full Width, Canvas (no header/footer)
template-parts/      Header, footer, post card
snippets/            Store features (auto-loaded)
assets/css/theme.css Base styles (+ one CSS/JS file per feature)
inc/                 Store Details settings, GitHub updater
bin/release.sh       One-command release
```

## Shortcodes
`[hec_search_box]` `[hec_mini_cart]` `[hec_category_dropdown]`
`[hec_product_loop]` `[hec_shop_filters]` `[hec_shop_grid]`
`[hec_product_details]` `[hec_product_description]` `[hec_product_video]`
`[hec_related_products]` `[hec_order_tracker]` `[hec_search_results_heading]`
The default header already uses the search box and mini cart. When an
Elementor Pro header/footer/single/archive template is assigned, it
replaces the theme's own markup.

## Child themes
Create a child with `Template: ecomx-bookshop` (this folder's name). A child can:
- override any template file;
- ship its own `config.php` (used instead of the parent's);
- add `snippets/*.php` (a file with the same name as a parent snippet replaces it).
The child's `style.css` is loaded automatically after the parent's styles.

## Handles & constants
- Base stylesheet handle: `ecomx-style` (use it as a dependency).
- `HEC_THEME_DIR`, `HEC_THEME_URI`, `HEC_THEME_VERSION`
  (`HEC_CHILD_*` are kept as aliases for older snippets).
