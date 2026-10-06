=== Oli Catalog & Product PDF ===
Contributors: bigrat95
Tags: woocommerce, catalog, pdf, product sheet, acf
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.14.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private, print-ready WooCommerce catalogues and dealer or retail price tables, plus an optional Download PDF product sheet. Works with ACF.

== Description ==

**Oli Catalog & Product PDF** builds print-ready product catalogues from your WooCommerce categories, right in the admin. Pick the categories, remove or add products, choose the edition (Canada / United States), the prices and the layout, then print or save as PDF from Chrome or Edge. Catalogues are private: they are only generated in the admin and are never published on your site.

An optional **Download PDF** button gives visitors an A4 product sheet (logo, name, SKU, description, image and specifications) on product pages.

= Catalog =

* **Categories** — sub-categories are included automatically; untick products to remove them, add others manually
* **Drag and drop** — arrange products on the generated catalogue or in the admin list; remove with ×, with Undo; changes save automatically
* **Image zoom** — drag the corner of an image to zoom, drag the image to position it, double-click to reset; saved per layout and printed as shown
* **Editions** — Canada (CAD) or United States (USD); US prices come from [Price Based on Country for WooCommerce](https://wordpress.org/plugins/woocommerce-product-price-based-on-countries/) when installed
* **Prices** — any combination of Cost (dealer cost), List (regular price), MAP (sale price) and End-user (lowest of list and MAP), or no prices at all
* **Your own price labels** — e.g. "Dealer", "MSRP", "Street"
* **Product details** — image, brand, SKU and UPC, each on or off
* **Four layouts** — compact grid (about 30–36 products per page), list (about 34 per page), large cards (9 per page), and a table per category with the pictures you choose below it
* **Letter or A4**, optional new page per category

= Cover & pages =

* **Designed cover**, **full-page image** (your own artwork, edge to edge) or **no cover**
* **Every cover text is editable**, with placeholders such as `{title}`, `{year}`, `{market}`, `{prices}`, `{count}`
* **Logo, colours, background colour or image, title font and size**
* **Footer, page numbers** ("Page {page} of {pages}", left, centre or right) and **closing text**, each editable or hidden

= Design =

* Fonts for headings, body, labels and the product sheet: installed / system fonts (default, nothing is loaded), a stylesheet URL (self-hosted `@font-face`, Adobe Fonts) or Google Fonts
* Colours, italic headings, heading weight, uppercase labels, image background (blend, solid colour, none) and drop shadow
* Custom CSS for the catalogue and the product sheet

= Advanced Custom Fields (ACF) =

* **Logo from an ACF options page** — pick the image or URL field in **Cover & pages**; image arrays, attachment IDs and URLs all work. Falls back to the theme logo
* **Product data from ACF fields** — choose the ACF field (or any custom field) that holds the dealer costs (CAD and USD), the UPC and the brand. Your product field groups are suggested as you type
* Works without ACF: options and custom fields are read directly

= Multilingual =

* Works with **WPML**, **Polylang**, **TranslatePress** and **qTranslate-XT**, and with other plugins through filters
* One catalogue per language (product names, categories, brands, labels and dates), with one shared arrangement
* Custom texts are registered in WPML String Translation and Polylang; TranslatePress translates them like any other text

= Lightweight by design =

* Nothing runs on the front end unless the Product PDF button is enabled; its small script and stylesheet load only on pages that show the button (no jQuery)
* Admin assets load only on the plugin's screen; the catalogue uses one cached stylesheet and script
* No custom database tables: four options, not autoloaded
* No external service, no tracking; HPOS compatible

== Installation ==

1. Upload the `oli-catalog-generator` folder to `/wp-content/plugins/`, or install the zip from **Plugins > Add New > Upload Plugin**
2. Activate the plugin through the **Plugins** menu in WordPress (WooCommerce must be active)
3. Go to **WooCommerce > Catalog & Product PDF**
4. Pick categories, choose the edition and prices, then click **Save & generate catalogue**
5. In the print dialog: Margins Default, Headers and footers off, Background graphics on, then **Save as PDF**

== Frequently Asked Questions ==

= Can visitors see the catalogues? =

No. Catalogues are generated in the admin only. Catalogue links need a logged-in user with the `manage_woocommerce` capability (shop managers and administrators) and a valid nonce; anyone else gets a 403 error. Pages are sent with no-cache and noindex headers. Only the optional Product PDF button is public.

= Where do dealer costs come from? =

From the custom fields chosen in **Catalog > Data sources** — by default `_dealer_cost_cad` and `_dealer_cost_usd`, on simple products and variations. Pick an ACF field or type any meta key (for example one imported by WP All Import).

= Does it work with ACF? =

Yes. Choose the logo field of your ACF options page in **Cover & pages**, and the ACF fields holding dealer costs, UPC and brand in **Catalog > Data sources**. Fields inside ACF groups are supported. ACF is optional.

= How do I get a PDF? =

Click **Print / Save as PDF** on the catalogue, then choose **Save as PDF** in Chrome or Edge. Turn on **Background graphics** and turn off **Headers and footers**.

= Does it load Google Fonts? =

Only if you choose Google Fonts in the Design tab. By default the catalogue and the product sheet use installed / system fonts and nothing is loaded from another site.

= Is it compatible with HPOS? =

Yes. The plugin does not read or write orders, and compatibility with High-Performance Order Storage and the cart / checkout blocks is declared.

= How do I remove all data? =

Deleting the plugin from the Plugins screen removes its options (catalogue selection, cover, design, product PDF settings and version).

= Which languages are supported? =

The plugin is written in English and fully translatable (text domain `oli-catalog-generator`). French (France and Canada) translations are included.

== Privacy ==

The plugin does not collect personal data and does not send data to a third party. If you choose Google Fonts or an external font stylesheet in the Design tab, the browser of whoever opens a catalogue or a product sheet connects to that font service.

== Changelog ==

= 1.14.0 =
* New: Advanced Custom Fields integration — logo from an ACF options page field (image array, ID or URL), and dealer costs, UPC and brand from ACF fields or any custom field (Catalog > Data sources)
* New: the theme's custom logo is used when no logo is set
* New: "Settings" link on the Plugins screen; HPOS and cart / checkout blocks compatibility declared
* Privacy: new installs use installed / system fonts by default (Google Fonts is opt-in); existing installs keep their fonts
* Performance: the catalogue's styles and script are cached files instead of 40 KB of inline code; product PDF assets are registered only on pages that show the button
* WordPress.org review: no inline scripts or event handlers, enqueued resources only, WPML queries without `suppress_filters`, readme.txt, uninstall removes every option
* Requires WordPress 6.5 or later

= 1.13.0 =
* Editable footer, page numbers (format, position, cover counting) and closing text

= 1.12.0 =
* Cover & pages tab: editable cover texts, logo, colours, background colour or image, title font, full-page image cover; catalogue page background colour

= 1.11.0 =
* Custom labels for Cost / List / MAP / End-user

= 1.10.0 =
* Price list layout: a table per category with the pictures you choose below it

= 1.9.0 =
* Any combination of Cost / List / MAP / End-user prices; image, brand, SKU and UPC toggles

= 1.8.0 =
* Multilingual support (WPML, Polylang, TranslatePress, qTranslate-XT), one catalogue per language, French translation

= 1.7.0 =
* Image background options (blend, solid colour, none) and drop shadow

= 1.6.0 =
* Manual image zoom and positioning per product

= 1.5.0 =
* Drag-and-drop product order and remove on hover (catalogue and admin list)

= 1.4.0 =
* Design settings (fonts, colours, custom CSS); hardened private catalogue access

= 1.3.0 =
* Cost, List and MAP prices; brand display

= 1.2.0 =
* Compact grid and list layouts (30+ products per page)

= 1.1.0 =
* Renamed to Oli Catalog & Product PDF; optional product PDF download button

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.14.0 =
ACF integration (logo and product data fields), lighter catalogue assets and WordPress.org compliance. Requires WordPress 6.5.
