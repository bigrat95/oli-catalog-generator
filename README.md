# Oli Catalog Generator

Build print-ready WooCommerce product catalogues (e.g. an accessories catalogue) from the WordPress admin.

- Pick product categories (sub-categories included), untick products to remove them, add others manually.
- Canada (CAD) or United States (USD) edition.
- End-user prices or dealer prices.
- Letter or A4, 2–4 products per row, each category on its own page, page numbers and running footer.
- Print → Save as PDF from Chrome or Edge.

## Installation

1. Download this repository as a ZIP (**Code → Download ZIP**) and rename the folder inside to `oli-catalog-generator` if needed.
2. WordPress → **Plugins → Add New → Upload Plugin**, then activate.
3. Go to **WooCommerce → Catalog Generator**.

Requires WooCommerce. US pricing uses [Price Based on Country for WooCommerce](https://wordpress.org/plugins/woocommerce-product-price-based-on-countries/) when installed.

## Price rules

| Edition | End-user price | Dealer price |
| --- | --- | --- |
| Canada | Lowest of regular / sale price | `_dealer_cost_cad` meta |
| United States | Lowest of the USD zone regular / sale price | `_dealer_cost_usd` meta |

Regular price = list price, sale price = MAP. When only one exists, that one is shown. Variable products show "From $X" when variation prices differ.

## Filters

- `olicg_dealer_meta_keys` — change the dealer cost meta keys: `array( 'ca' => '_dealer_cost_cad', 'us' => '_dealer_cost_usd' )`.
- `olicg_us_zone_id` — Price Based on Country zone ID for USD prices (default `usa`, falls back to the first USD zone).
- `olicg_fonts_css_file` — path to an `@font-face` CSS file used by the catalogue (default: the active theme's `assets/fonts/fonts-local.css`).

## Printing tips

In the print dialog: **Margins: Default**, **Headers and footers: off**, **Background graphics: on**.
