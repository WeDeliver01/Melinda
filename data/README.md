# Extracted client data

Derived from the 2026-09-10 database backup. Source of truth for any re-import.

| File | Contents |
|---|---|
| `melindas-products.csv` | 160 rows: 40 products, 120 variations. WooCommerce native import format. Prices, SKUs, stock, descriptions, categories, attributes, 116 image URLs. |
| `product-addons.json` | 36 products' Product Add-Ons configuration, 858 option rows. Keyed by product title so it survives ID changes. Import with `scripts/import-addons.php`. |
| `page-content/` | Rebuilt page content as Gutenberg blocks. `291` Home, `292` Weddings, `293` Contact, `294` Terms, `295` Privacy, plus `faq.html`, `weddings.html`, `shop-intro.html`. |
| `copy/` | All 18 pages of raw copy, WPBakery shortcodes stripped, as markdown. Filenames carry the original post ID. |
| `image-manifest.txt` | 116 product image URLs. |
| `url-list.txt` | 16 live page URLs, for the redirect map. |

## Notes

Prices are baked into variation attribute labels on the old site, e.g.
`Single tier (15cm) 16 servings R1195`. Every price change means editing attribute
terms, and the label can drift out of sync with the actual variation price. On the
rebuild, put size and servings in the attribute and let WooCommerce render the price.

`copy/0002-sample-page.md` is the homepage. The slug was never renamed from WordPress's
default.

Weddings copy has two typos in the original, "scracth" and "diary butter", corrected in
`page-content/weddings.html`. Confirm with the client before publishing.
