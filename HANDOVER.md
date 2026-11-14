# Melinda's Kitchen: Project Handover

**Client:** Melinda-Michelle Versluis, Melinda's Kitchen (melindaskitchen.co.za)
**Studio:** Operandi (Ashley Baloyi)
**Handover date:** 14 November 2026
**Status:** Staging site live and functional. Design fidelity incomplete. Commercial functions not yet connected.

---

## 1. Commercial position

| Item | Amount | Status |
|---|---|---|
| Commit 1 | R2,500 | **Paid**, delivered |
| Month 2 | R2,500 | Outstanding |
| Month 3 | R2,500 | Outstanding |
| **Total** | **R7,500** | R5,000 remaining |

Framed with the client as one continuous engagement, not separate contracts.

**Commit 1 scope (delivered):** checkout compatibility fixes, private staging environment, new checkout page, delivery at checkout via Delicate Courier.

**Remaining R5,000 covers stages 0 to 3** plus maintenance and monitoring for the build period.

Recurring costs are carried by the client and sit outside the build fee: hosting, payment gateway fees, Google Maps usage for address autocomplete, Cloudflare above free tier.

---

## 2. Infrastructure built

### Server

| | |
|---|---|
| Host | Vultr VPS, Johannesburg, Ubuntu 24.04 |
| Hostname | `web01.operandis.co.za` |
| Public IP | `139.84.250.19` |
| Tailscale IP | `100.66.152.34` |
| Panel | CloudPanel 6.0.8 on port 8443 |
| Stack | Nginx, PHP 8.3, MariaDB, Redis |

### Access

Administration runs over **Tailscale**, not the public internet. The VPS firewall whitelist approach failed repeatedly because Ashley works from a mobile hotspot with a rotating carrier NAT address.

Current state: Tailscale installed on both the server and Ashley's Windows machine, both on the same tailnet.

- Panel: `https://100.66.152.34:8443`
- SSH: `ssh root@100.66.152.34`

**Outstanding hardening:** ports 22 and 8443 should be closed to `0.0.0.0` in the Vultr firewall, leaving only 80 and 443 public. This has not been done yet.

### Staging site

| | |
|---|---|
| URL | `melindaskitchen.operandis.co.za` |
| System user | `operandis-melindaskitchen` |
| Path | `/home/operandis-melindaskitchen/htdocs/melindaskitchen.operandis.co.za` |
| WordPress | 7.1 |
| WooCommerce | 11.1.0 |
| PHP | 8.3 |
| Visibility | "Coming soon" mode, `blog_public = 0` |

Hosted under the Operandi domain rather than the client's, because her DNS is still controlled by the previous developer. See section 6.

### Server configuration applied

In `wp-config.php`:

```php
define( 'DISABLE_WP_CRON', true );
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PORT', 6379 );
define( 'WP_REDIS_DATABASE', 0 );
define( 'WP_POST_REVISIONS', 5 );
define( 'DISALLOW_FILE_EDIT', true );
```

- Redis object cache active
- Imagick installed (`php8.3-imagick`)
- Real system cron every 5 minutes replacing WP pseudo-cron
- Permalinks: `/%postname%/`
- Timezone: Africa/Johannesburg
- Currency ZAR, country ZA:GP, taxes off, guest checkout on
- HPOS (High-Performance Order Storage) enabled

### Plugin set installed

WooCommerce, Redis Object Cache, Rank Math SEO, Wordfence, FluentSMTP, Fluent Forms, UpdraftPlus, plus `mk-core` (custom).

Deliberately excluded versus the old site: WPBakery, Slider Revolution, Total Theme Core, String Locator, All-in-One WP Migration, InstaWP Connect, Classic Editor, CartFlows, Facebook for WooCommerce.

---

## 3. Technical audit of the old site

Findings from a full analysis of the 2026-09-10 database backup. Several contradict the original health report; where they do, the figure below is the measured one.

### Corrected findings

**The old site was not stuck in 2020.** It runs WordPress 6.8.8 and WooCommerce 10.0.4, both reasonably current. What is old is PHP 7.4.33 and the theme.

**The theme is Total 5.1, renamed.** The folder was renamed from `total` to `broodenbotter` by the previous developer, almost certainly to dodge the ThemeForest licence. A renamed theme cannot receive updates, which is the actual reason the site is frozen at a 2020 theme version.

**The 3MB autoload "crisis" was one plugin.** Measured: 790 autoloaded rows at 2.76MB, of which **2.64MB is String Locator transients**. Genuine settings are 0.12MB. Deleting that plugin resolves it entirely.

**796 orders stuck at `wc-on-hold`.** This is the most commercially significant finding. Continuous from December 2024 through August 2026, 8 to 16 per month, with zero progressing to processing or completed. Either the payment gateway never fires its status callback, or Melinda confirms payment outside WooCommerce. Her analytics, stock and order emails have all been wrong for close to two years. **Not yet raised with the client.**

**Tables are MyISAM.** No transactions, table-level locking. A genuine integrity risk on a store.

**Fonts.** The site references a self-hosted Addington CF that returns 404 at the domain root. Its Adobe Typekit kit (`ooa6tle`) serves `sweet-sans-pro`, which appears only in a 2020 CSS revision and is unused today. **Her site has been rendering in browser-default serif for years.** No Addington CF files exist in any backup. This is a client decision: licence it from Connary Fagen, add it to her Adobe kit, or pick a replacement.

### Content scale

| | |
|---|---|
| Published products | 29 |
| Draft products | 10 |
| Variations | 123 |
| Products with add-on config | 36 (858 option rows) |
| Pages | 16 published |
| Media originals | 423 files, 313MB |
| Plugin debris in uploads | 1,794 files, 225MB |

No Portfolio, Staff or Testimonials content, so nothing is orphaned by dropping Total.

Her front page slug is `sample-page`, never renamed. Her homepage title tag is `Online celebration and wedding cake ordering Pretoria | Melinda's Kitchen` and must be preserved in Rank Math.

---

## 4. What has been built

### WordPress theme: `melindas-kitchen` (v1.0.0)

Classic PHP theme, modern CSS, Alpine.js, Vite build. Located at `wordpress/themes/melindas-kitchen/`.

**Templates:** `front-page.php`, `page.php`, `single.php`, `archive.php`, `index.php`, `404.php`, `searchform.php`, `header.php`, `footer.php`, plus `template-parts/site-header.php` and `site-footer.php`.

**WooCommerce overrides:** `archive-product.php`, `content-product.php`, `single-product.php`.

**Includes:** `setup.php` (theme supports, menus, image sizes), `assets.php` (Vite manifest loading, font links), `template-tags.php` (branding, split menu, card images, occasion thumbnails), `woocommerce.php` (presentation filters), `customizer.php` (hero image control).

**CSS architecture** in `src/css/`, compiled by Vite: `_tokens`, `_base`, `_grid`, `_layout`, `_components`, `_home`, `_shop`, `_woocommerce`, `_fonts`.

Every value in `_tokens.css` and `_grid.css` was measured from the client's actual stylesheets, not estimated. See section 5.

**Reproduced from the original:** Total header-five split menu (logo inside the list as a middle item, 40px padding), 1100px container at 90% max-width, the sage `#F8F9F4` statement band and footer, the 10px white picture-frame borders, the -80px story image overlap, the translucent plate banner, boxed overlay tile titles, the hover image swap on product cards, the parallax shop banner, and the three info cards.

**Custom Customizer control:** homepage hero image.

### WordPress plugin: `mk-core` (v1.0.0)

Business logic separated from presentation so it survives a theme change. Located at `wordpress/plugins/mk-core/`.

- **`mk_occasion` taxonomy.** Separate from product category. The old catalogue mixed product type (Cupcakes, Macaron) with occasion (Christmas, Mother's Day) in one list, which forced duplicate products. Splitting the axis is what makes the occasion dropdown in the brief possible.
- **South African checkout localisation.** Province, Postal code, Town or city, replacing the generic State/County and Postcode/ZIP that were flagged for client presentation.
- **"Place order" button text**, ported from a loose filter in the old theme's `functions.php`.
- **Billing phone required.** Every made-to-order cake needs a confirmation call.

### Data extracted and migrated

| File | Contents |
|---|---|
| `data/melindas-products.csv` | 160 rows: 40 products, 120 variations, with prices, SKUs, stock, descriptions, categories, attributes and 116 image URLs. WooCommerce native import format. |
| `data/product-addons.json` | 36 products' complete add-on configuration, 858 option rows. **Not yet imported.** |
| `data/copy/` | All 18 pages of the client's real copy, WPBakery shortcodes stripped, as clean markdown. |
| `data/page-content/` | Rebuilt page content as Gutenberg blocks: Home, Weddings, Contact, Terms, Privacy, FAQ (12 core Details blocks), shop intro. |
| `data/image-manifest.txt` | 116 product image URLs. |
| `data/url-list.txt` | 16 live page URLs for the redirect map. |

### Scripts

All in `scripts/`, run from the WordPress root as the site user.

| Script | Purpose |
|---|---|
| `restore-uploads.sh` | Extracts the media library from UpdraftPlus archives, skipping 225MB of plugin debris and generated thumbnails. |
| `import-brand.sh` | Logos, contact details, product image sizes, thumbnail regeneration. |
| `import-home-images.sh` | 19 homepage photographs: slider, story, banner, tiles. |
| `import-shop.sh` | Shop banner and intro content. |
| `import-pages2.sh` | FAQ and Weddings content, shop intro. |
| `fix-images.sh` | Sets thumbnail sizes and regenerates. |
| `import-addons.php` | Restores product add-ons, matched by product title. Run with `wp eval-file`. |

---

## 5. Design system

Extracted from the client's live stylesheets, not approximated. Full token file at `docs/reference/tokens.css`.

### Colour

Three golds exist in the original, one to two points apart. This is in her CSS, not an error in transcription.

| Token | Value | Used for |
|---|---|---|
| `--mk-gold` | `#B69778` | Headings, overlay titles |
| `--mk-accent` | `#B69576` | Buttons, links |
| `--mk-rule` | `#B49778` | Footer top border |
| `--mk-ink` | `rgb(99 99 99)` | Body copy |
| `--mk-sage` | `#F8F9F4` | Statement band, footer |
| Surface | `#FFFFFF` | Base |

A client decision is pending on consolidating to one gold.

### Type

Fixed px, not scaled. h1 24px italic gold at 0.1em tracking. h2 18px uppercase ink at 0.2em. h3 14px Montserrat uppercase. Body 16px, line-height 2, letter-spacing 0.4px, **justified**.

Display face is Addington CF, which is unlicensed on this install (see section 3). Cormorant Garamond stands in. The stack is `'addington-cf', 'Cormorant Garamond', Georgia, serif`, so setting `mk_typekit_id` to an Adobe kit containing Addington takes over with no code change.

### Layout

Two grid systems, both reproduced exactly:

- **WPBakery rows** (page sections): `-15px` side margins, `15px` column padding, stacking below 768px.
- **Total rows** (product and image grids): `-10px` side margins, `10px` column padding, `20px` bottom margin, `span_1_of_N` widths.

Container 1100px with `max-width: 90%`. Logo 200px. Headings `margin: 30px 0 20px`. Paragraphs `margin: 0 0 20px`. Hover grow is `scale(1.2)`. Shop paginates at **12 per page**, four across.

### Reference material

`docs/reference/live-html/` holds five full page captures from the live site: homepage, shop, Weddings, FAQ, Gallery. These are the ground truth for any further design work. Working from the database alone produced six consecutive wrong results; working from rendered HTML plus the real stylesheets did not.

**Not included in this repo:** Total's `style.css`, `js_composer.min.css`, `wpex-woocommerce.css` and the other commercial stylesheets used to measure these values. They are licensed third-party code and should not sit in the client's repository. They can be re-captured from the live site with Chrome's "Webpage, Complete" save.

---

## 6. Outstanding work, against the agreed brief

### Blocking

**Product add-ons are not imported.** 36 products need their flavour, filling, colour and decoration options. The site cannot take a real cake order without them. Requires the WooCommerce Product Add-Ons plugin (paid extension, not present in the backups) to be installed first, then `wp eval-file scripts/import-addons.php`.

**DNS migration.** `melindaskitchen.co.za` is registered at GoDaddy but its nameservers point at the old host, so Ashley cannot add records. This is Stage 0 of the brief and the critical path to launch. Sequence:

1. Get GoDaddy account access from Melinda, who is the registrar account holder.
2. Capture every existing record, especially MX, SPF, DKIM and TXT verification. `dig` gets most of it; the old host's export is better.
3. Build the zone in Cloudflare and verify MX and TXT by hand.
4. Change nameservers at GoDaddy only once the zone is complete.

If MX records are lost in this step, her email stops. This is the single highest-risk action remaining.

**Duplicate products.** The shop reports 39 results against 29 imported published products, and there is a product titled "Build Your Own Gingerbread House (Copy)". The CSV was likely imported twice. Diagnose with:

```bash
wp post list --post_type=product --post_status=publish --field=post_title | sort | uniq -d
```

### Per the original scope of work

| Stage | Item | Status |
|---|---|---|
| 0 | Host migration, Cloudflare, DNS takeover | Server and staging done. DNS blocked. |
| 1 | Security, plugin updates, staging environment | Done on the new build. |
| 2 | Merge Home and About | Done |
| 2 | Remove Gallery page | Done (omitted) |
| 2 | Move FAQ block to Contact | FAQ built as editable blocks. Placement on Contact pending. |
| 2 | Occasion-based product dropdown | Taxonomy and dropdown built. **Terms not assigned to products.** |
| 3 | International-friendly payment gateway | **Not started.** BobPay is the current gateway. |
| 3 | Collect / local pickup option | Built in the Commit 1 checkout plugin, not yet ported to this build. |
| 3 | Delivery integrated at checkout | Delicate Courier plugin not yet installed on the new site. |
| 3 | Address autocomplete (Google Maps) | **Not started.** Needs client to approve and bill the API key. |
| 4 | Delicate Courier delivery option | Pending |
| 4 | Performance monitoring and optimisation | Redis, cron and caching done. Page caching plugin not installed. |

### Also outstanding

- Email deliverability: FluentSMTP installed but no provider connected. A cold Vultr IP will land order confirmations in spam. Needs Brevo, Postmark or similar, plus SPF and DKIM.
- Page caching: FlyingPress or equivalent. **Do not use WP Rocket**, which has a critical conflict with WordPress 7.1 that locks you out of admin.
- Redirect map in Rank Math from `data/url-list.txt`: `/about-me/` to `/`, `/gallery/` to `/shop/`, `/faq/` to `/contact/`, `/sample-page/` to `/`.
- Homepage hero image selection, which is a client decision.
- Copy refresh. Her About text still frames the split from Melinda's Catering as recent; it was 2020. The Weddings copy has typos in the original ("scracth", "diary butter") which have been corrected in `data/page-content/weddings.html`.
- Close ports 22 and 8443 to the public in Vultr.
- Shop visibility: `blog_public` back to 1 at launch.

### Known open questions for the client

1. The 796 on-hold orders. How does she currently know an order is paid?
2. Addington CF licensing.
3. Which gold to standardise on.
4. Product tabs were hidden site-wide on the old build. Deliberate or a workaround?
5. Whether to keep the justified body copy, which produces rivers of whitespace on mobile.
6. Whether the designer credit in the footer should read Operandi.

---

## 7. Moving to a headless Next.js frontend

The decision to go headless is made. This section covers what carries over and what does not.

### What transfers

- **All content data.** Products CSV, add-ons JSON, page copy, image manifest. Format-agnostic.
- **The design system.** `docs/reference/tokens.css` and section 5 of this document are the complete specification. Every value is measured.
- **The reference HTML.** Five rendered pages remain the ground truth for layout.
- **`mk-core` business logic**, with modification. The occasion taxonomy and SA checkout localisation stay server-side and are exposed through the API. The "Place order" text filter becomes irrelevant.
- **Infrastructure.** Same VPS. WordPress moves behind the frontend.

### What does not transfer

- **The PHP theme.** All 20 template files and the Vite pipeline become reference material for the React components rather than running code. The CSS is directly portable.
- **WooCommerce's rendered checkout.** You rebuild it.

### Things that will bite, in order of severity

**1. Product Add-Ons has no Store API representation.** The WooCommerce Store API does not expose Product Add-Ons fields, and add-on pricing is calculated server-side at cart time. With 36 products carrying 858 option rows including percentage-based and flat-fee pricing and file uploads, this is the largest single piece of work in the migration. You will need custom REST endpoints in `mk-core` that read `_product_addons` meta and a matching cart-validation path. Budget for this properly; it is not a weekend.

**2. Payment.** BobPay is a redirect gateway. A headless checkout has to hand off to the gateway and handle the return, and the existing 796-on-hold problem suggests the callback path is already unreliable. Fix the callback before building on top of it.

**3. SEO.** The site currently ranks. Next.js needs SSR or SSG on every product and page route, the exact title tags preserved, and the redirect map honoured at the edge rather than in Rank Math. Getting this wrong costs the client real revenue.

**4. Melinda's editing.** The brief's requirement that she can add a flavour without a developer still holds. WordPress admin keeps working for products and add-ons. But any page content built as React components is no longer hers to edit. Keep marketing pages in WordPress and render them through the API, as the current build does with core blocks.

### Suggested repo shape

```
melindas-kitchen/
├── apps/
│   ├── web/              # Next.js frontend
│   └── wordpress/        # headless WP: mk-core, minimal theme
├── packages/
│   ├── design-tokens/    # from docs/reference/tokens.css
│   └── woo-client/       # typed Store API + custom endpoints
├── data/                 # as in this repo
└── docs/
```

### Recommended sequence

1. Stand up the current PHP build fully first. Import add-ons, fix the gateway, migrate DNS, launch. This discharges the agreed brief and the remaining R5,000.
2. Build the headless frontend against a working, paid-for store rather than against a half-built one.

Launching twice is slower than launching once, but it means the client has a working store while the frontend is rebuilt, and the add-on and payment problems are solved in the simpler environment before they are solved in the harder one.

---

## 8. Repository contents

```
.
├── HANDOVER.md                  this document
├── README.md                    quick start
├── CLAUDE.md                    instructions for Claude Code
├── docs/
│   ├── scope-of-work.md         client brief, both versions
│   └── reference/
│       ├── tokens.css           measured design system
│       └── live-html/           5 captured pages from the live site
├── wordpress/
│   ├── themes/melindas-kitchen/ full theme source
│   └── plugins/mk-core/         business logic plugin
├── data/
│   ├── melindas-products.csv    160 rows
│   ├── product-addons.json      36 products, 858 options
│   ├── page-content/            Gutenberg blocks
│   ├── copy/                    18 pages of raw copy
│   ├── image-manifest.txt
│   └── url-list.txt
└── scripts/                     7 migration scripts
```

**Not in this repo, deliberately:**

- The database backup (`-db.gz`, 2.6MB). Contains customer names, addresses and order history. Keep it archived outside version control. SARS requires business records for five years; that file is the retention.
- The uploads archives (654MB across two zips). Too large for git. Restore with `scripts/restore-uploads.sh`.
- Total theme and WPBakery stylesheets. Licensed third-party code.
- Credentials. None are committed. Server access is via Tailscale; database credentials are in `wp-config.php` on the server only.
