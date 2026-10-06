# Oli Catalog & Product PDF

Build print-ready WooCommerce product catalogues (e.g. an accessories catalogue) from the WordPress admin, and optionally add a "Download PDF" product sheet button to product pages.

## Catalog

- Pick product categories (sub-categories included), untick products to remove them, add others manually.
- Arrange products by drag and drop (on the generated catalogue, or with the ⋮⋮ handle in the admin list) and hover a product to remove it with ×. Changes on the catalogue save automatically, with Undo. Arranged products come first in their category, the rest stay alphabetical.
- Zoom an image inside its box: hover the product and drag the corner handle of the image (bigger / smaller), then drag the image to position it. Double-click resets. Saved per layout and printed exactly as shown.
- Canada (CAD) or United States (USD) edition.
- Prices: tick any combination of **Cost** (dealer cost), **List** (regular price), **MAP** (sale price) and **End-user** (lowest of list and MAP). Each ticked price gets its own line, or untick all for a catalogue without prices.
- Price labels: type your own label next to each price (e.g. "Dealer", "MSRP", "Street"); empty uses the default. Custom labels are printed on cards, table headers and the cover, and can be translated in WPML String Translation / Polylang (names "Price label: cost", …) or TranslatePress.
- Product details: tick **Image**, **Brand**, **SKU** and **UPC** independently.
- **Clickable PDF**: optionally link product names to their page; the links keep working in the PDF saved from Chrome / Edge.
- **Sort products by** name, SKU, shop order (WooCommerce sorting) or price. Products dragged into place keep their position.
- **Spreadsheet (CSV)**: *Save & download spreadsheet*, or the **CSV** button next to each edition — category, product, SKU, UPC, brand, the chosen prices, stock and link, in the catalogue's order (UTF-8, opens in Excel).
  - The brand comes from the field chosen in **Data sources**, else Products → Brands, Perfect Brands, or a `brand` attribute.
  - The UPC comes from the field chosen in **Data sources**, else WooCommerce's GTIN / UPC / EAN / ISBN field, falling back to common barcode meta (`quivers_upc`, `_upc`, `_gtin`…).
- **Data sources (ACF / custom fields)**: choose the ACF field or meta key holding the dealer cost (CAD), dealer cost (USD), UPC and brand. Your ACF product fields are suggested as you type (fields inside ACF groups too).
- Four layouts: **Compact grid** (small images, up to 6 per row, about 30–36 products per page — default), **List** (thumbnails in two columns, about 34 per page), **Large cards** (9 per page) and **Price list**.
- **Price list** layout: one table per category (black category band with the edition, e.g. "CDN DEALER", then SKU / UPC / Brand / Description and one column per chosen price), with product pictures below each table. You choose which pictures show: hover a row and click ◩, hover a picture and click ×, or use "Show all / Hide all" per category. Drag rows or pictures to reorder (both stay in sync); picture zoom works like the cards. "Products per row" sets how many pictures per row.
- Letter or A4, optional new page per category, page numbers and running footer.
- Print → Save as PDF from Chrome or Edge.
- **Private:** catalogues are only built in the admin. Links require a logged-in user with `manage_woocommerce` (shop managers and administrators) plus a valid nonce; anyone else gets a 403. Responses are sent with no-cache / noindex headers. Nothing is published on the front end.

## Design

**WooCommerce → Catalog & Product PDF → Design** controls the look of both the catalogue and the product PDF sheet:

- Fonts for headings, body, labels & prices, and the product PDF sheet: installed/system fonts (default — nothing is loaded), a stylesheet URL (Adobe Fonts kit, self-hosted `@font-face` CSS…), or Google Fonts by name (opt-in; the reader's browser then connects to Google).
- Italic headings on/off, heading weight, uppercase labels on/off.
- Colours: text, secondary text, borders, image background, prices, cover band / PDF footer bar and its text, catalogue page background.
- Product images: **Blend** (the photo melts into the image background colour — best for photos on white), **Solid colour** behind the image (best for transparent PNGs) or **No background**, plus an optional soft or strong drop shadow.
- Custom CSS, added to the catalogue and the PDF sheet.

## Cover & pages

**WooCommerce → Catalog & Product PDF → Cover & pages**:

- **Designed cover**, **Full-page image** (your own artwork printed edge to edge, fill or fit) or **No cover**.
- Every cover text is editable: company name, the two top-right lines, the small line above the title, title, second title line, the three information-band boxes (label + value) and the note. Empty = automatic text; a single dash (`-`) prints nothing. Placeholders: `{title}`, `{year}`, `{date}`, `{site}`, `{domain}`, `{market}`, `{currency}`, `{prices}`, `{edition_label}`, `{count}`, `{note}`.
- Show / hide the logo, top-right lines, line above the title, information band and note.
- Logo (media library picker, height in px) — also used by the product PDF sheet when it has no logo of its own. Left empty: the **Site logo from ACF** field (any image / URL field of an ACF options page; image array, ID or URL), then the theme's custom logo.
- Background colour and/or background image (with an adjustable colour overlay for legibility), cover text and secondary text colours — a coloured or image background prints edge to edge.
- Title font and size (empty = the Design tab headings font).
- Footer & page numbers: footer text on every page (editable, same placeholders), page number text (`{page}`, `{pages}` — e.g. "Page {page} of {pages}"), position (bottom right / centre / left), footer size, each on/off. Untick "Count the cover as page 1" to start numbering on the first product page (`{pages}` still counts the cover).
- Closing text at the end of the catalogue: editable or hidden.
- Custom texts are translatable with WPML String Translation / Polylang (names "Cover: …") or TranslatePress.

## Product PDF (optional)

Off by default. Enable it in **WooCommerce → Catalog & Product PDF → Product PDF**.

- Adds a "Download PDF" button on product pages: automatically (after the product summary or after the Add to cart button) or with the shortcode `[oli_product_pdf]` (`[oli_product_pdf id="123" label="Spec sheet"]` works anywhere).
- The button opens an A4 product sheet in a print window: logo, product name, SKU, short description, main image, visible attributes as specifications, and a black footer bar.
- Logo per brand: uses the product's brand image (Products → Brands) when set, otherwise the logo you choose, otherwise the site logo.
- Footer bar: optional icon and three lines (e.g. "MADE IN" / "CANADA" / "Since 1972"), site address and a disclaimer.
- Button style: dark, light, or your theme's button style.

## Advanced Custom Fields (ACF)

ACF is optional; everything also works with plain options and custom fields.

- **Logo**: Cover & pages → *Site logo from ACF* lists the image / URL / file fields of your ACF options pages. Image arrays, attachment IDs and URLs are all supported. Without ACF active, the stored option (`options_{field}`) is still read.
- **Product data**: Catalog → *Data sources* — dealer costs (CAD / USD), UPC and brand can come from any ACF field on products or variations (or any meta key). Relationship, post object, taxonomy and choice fields are converted to their titles / labels; numeric codes keep their leading zeros.

## Multilingual

Works with **WPML**, **Polylang**, **TranslatePress** and **qTranslate-XT**, and with any other translation plugin through filters.

- **Plugin texts**: fully translatable (`languages/oli-catalog-generator.pot`). French (`fr_FR`, `fr_CA`) is included. Other languages can be added with Loco Translate, WPML String Translation, or files in `wp-content/languages/plugins/`.
- **Catalogue language**: when a multilingual plugin is active, the Edition card gets a **Language** choice, and Quick generate has a row per language. The catalogue then uses that language for:
  - product names, categories and brands:
    - WPML / Polylang: the translated products and categories;
    - TranslatePress: its translation dictionary;
    - qTranslate: the language tags;
  - labels, dates and the default title, which follow the language's locale.

  Prices, stock, order, removed products and image zoom come from the selected products, so one arrangement serves every language. The catalogue still renders only in the admin.
- **Custom texts**: a custom catalogue title, plus the PDF button label, footer lines and disclaimer, are registered in WPML String Translation and Polylang → Languages → Translations. TranslatePress translates them on the page like any other text. Default texts follow the visitor's language automatically.
- **Product PDF**:
  - the button and sheet are rendered on the product page, so they show the translated product;
  - SKU and site address are marked as non-translatable;
  - `wpml-config.xml` makes the shortcode `label` translatable and copies the dealer cost fields to translations (read by WPML and Polylang).
- **Other plugins**:
  - `olicg_languages` — add languages (`code => [ 'label' => …, 'locale' => … ]`);
  - `olicg_translate_post_id` / `olicg_translate_term_id` — map products and categories to their translations;
  - `olicg_translate_strings` — translate catalogue texts (names, categories, brands, title);
  - `olicg_translate_string` — translate custom setting texts;
  - `olicg_switch_language` / `olicg_restore_language` — actions to switch your plugin's language while a catalogue renders.

## Installation

1. Download this repository as a ZIP (**Code → Download ZIP**) and rename the folder inside to `oli-catalog-generator` if needed.
2. WordPress → **Plugins → Add New → Upload Plugin**, then activate.
3. Go to **WooCommerce → Catalog & Product PDF**.

Requires WordPress 6.5+, PHP 7.4+ and WooCommerce (HPOS compatible). US pricing uses [Price Based on Country for WooCommerce](https://wordpress.org/plugins/woocommerce-product-price-based-on-countries/) when installed.

## Price rules

| Edition | End-user price | Dealer price |
| --- | --- | --- |
| Canada | Lowest of regular / sale price | Data sources field (default `_dealer_cost_cad`) |
| United States | Lowest of the USD zone regular / sale price | Data sources field (default `_dealer_cost_usd`) |

Regular price = list price, sale price = MAP. When only one exists, that one is shown. With several prices ticked, each product shows one labelled line per price ("—" when missing). Variable products show "From $X" when variation prices differ.

## Filters

- `olicg_dealer_meta_keys` — override the dealer cost fields chosen in Data sources: `array( 'ca' => '_dealer_cost_cad', 'us' => '_dealer_cost_usd' )`.
- `olicg_logo_url` — the site logo URL used by the cover and the product PDF sheet.
- `olicg_csv_header` / `olicg_csv_row` — columns of the spreadsheet export (`$row, $item, $section, $region, $lang`).
- `olicg_translate_url` — product links in another language (TranslatePress and qTranslate-XT are handled; WPML / Polylang use the translated product's permalink).
- `olicg_us_zone_id` — Price Based on Country zone ID for USD prices (default `usa`, falls back to the first USD zone).
- `olicg_upc_meta_keys` / `olicg_product_upc` — where the UPC is read from, when WooCommerce's GTIN field is empty.
- `olicg_capability` — capability required to build and view catalogues (default `manage_woocommerce`).
- `olicg_fonts_css_file` — path to an `@font-face` CSS file used by the catalogue (default: the active theme's `assets/fonts/fonts-local.css`).

## Printing tips

In the print dialog: **Margins: Default**, **Headers and footers: off**, **Background graphics: on**.

## Author

[Olivier Bigras](https://olivierbigras.com) (bigrat95). Licensed under the GPLv2 or later. See `readme.txt` for the WordPress.org description and changelog.
