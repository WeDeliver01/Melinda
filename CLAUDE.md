# Project instructions

Custom WooCommerce build for Melinda's Kitchen, a premium studio bakery in Pretoria. Read `HANDOVER.md` for full state before starting work.

## Non-negotiables

**Design values are measured, never estimated.** `docs/reference/tokens.css` and HANDOVER section 5 hold the real numbers, extracted from the client's own stylesheets. `docs/reference/live-html/` holds five rendered pages from her live site. When something looks wrong, read those files. Six consecutive design passes were wrong because they were inferred from database values instead of rendered output.

**Presentation in the theme, business rules in `mk-core`.** If a theme change would delete the functionality, it is in the wrong place. The occasion taxonomy, checkout localisation and order rules live in the plugin so they survive a redesign.

**Melinda must be able to add a flavour without a developer.** Product options, variations and add-ons live in WordPress admin. Marketing page content is core Gutenberg blocks. Only structure is hardcoded. Do not move editable content into templates.

**No em dashes or en dashes** in any client-facing copy, documentation or commit message. Use hyphens, colons, or restructure the sentence.

## Editing files

Use real file edits. Do not patch PHP with scripted string replacement: an earlier pass duplicated a function 3,800 times and served the source as plain text. After any change to a PHP file:

```bash
php -l <file>
```

After any CSS change, rebuild and confirm the rule landed in the bundle:

```bash
npm run build && grep -o "<selector>[^{]*{[^}]*}" dist/assets/*.css
```

Watch for duplicate rules across `src/css/` partials. `_components.css` and `_woocommerce.css` have conflicted on `ul.products` before.

## Brand

Three golds exist in the original and are one to two points apart. This is in her CSS, not a transcription error.

- `--mk-gold` `#B69778` headings
- `--mk-accent` `#B69576` buttons and links
- `--mk-rule` `#B49778` footer border
- `--mk-ink` `rgb(99 99 99)` body
- `--mk-sage` `#F8F9F4` statement band and footer

Recurring devices: 10px white picture-frame borders on photography, translucent white plates over images, square corners except the shop info cards at 5px, wide letter-spacing, weight 300.

Addington CF is the intended display face but is not licensed on this install. Cormorant Garamond stands in. The stack resolves to Addington automatically once a Typekit ID containing it is set in `mk_typekit_id`.

## Server

Access over Tailscale only.

```bash
ssh root@100.66.152.34
su - operandis-melindaskitchen
cd htdocs/melindaskitchen.operandis.co.za
```

WP-CLI runs as the site user, never root, or file ownership breaks plugin updates.

## Data

`data/` holds extracted client content: products CSV, add-ons JSON, raw page copy, Gutenberg page content. These were derived from a database analysis and are the source of truth for any re-import. Do not regenerate them from the live site without checking HANDOVER section 3 first; several of the obvious readings are wrong.

## Things that are known-wrong and should not be re-litigated

- The old site runs current WordPress and WooCommerce. Only PHP and the theme are old.
- The old theme is Total 5.1 renamed to `broodenbotter` to dodge licensing. That is why it cannot update.
- The 3MB autoload figure is 96% String Locator transients, not architectural bloat.
- Her site renders in browser-default serif. The fonts 404 and have for years.
- 796 orders sit at `wc-on-hold`, going back to December 2024. This is unresolved and the client has not been told.
