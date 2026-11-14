# Roadmap: Melinda's Kitchen inside Commerce Engine

**Date:** 14 November 2026
**Owner:** Operandi
**Inputs:** `HANDOVER.md`, `docs/scope-of-work.md`, *Commerce Engine Enterprise Technical Blueprint v1.0*

Melinda's store is the priority. Commerce Engine is where we're heading long term. This document sets out how the two fit together and in what order the work happens, so every step on Melinda's site also moves the platform forward. None of it should delay her launch.

---

## 1. Two repositories, one direction

| | `WeDeliver01/Melinda` (this repo) | `WeDeliver01/commerce-engine` (new) |
|---|---|---|
| What it is | One client: Melinda's Kitchen | The reusable framework |
| Contains | Client config, brand tokens, client-only sections, `mk-core`, data, this client's deployment, the PHP theme (kept for rollback) | Storefront packages, typed Woo clients, contracts, theme engine, `commerce-engine-*` WordPress plugins, infrastructure templates, ADRs |
| Releases | Deployed to Melinda's servers | Tagged semver releases consumed by client repos |
| Who depends on whom | Pins a specific engine version | Knows nothing about any client |

This is blueprint section 5.3: one canonical framework repo plus a client repo for each store.

### How they talk to each other

**At build time, through versioned releases.** At runtime they never connect.

```
 commerce-engine repo                         Melinda repo
 --------------------                         ------------
 packages/*  --tag v0.x--> GitHub Packages --> apps/storefront/package.json (pinned)
 wordpress/plugins/* --release zip-------->    deploy step installs pinned plugin zips
 infrastructure/templates ---------------->    infrastructure/ (copied, then owned)


 Runtime (Melinda's server only, no Operandi service in the path)
 -------------------------------------------------------------------
 Browser -> melindaskitchen.co.za (Next.js storefront)
               |  Store API      /wp-json/wc/store/v1        (public catalogue, cart, checkout)
               |  Engine API     /wp-json/commerce-engine/v1 (config, add-ons, health)
               v
            cms.melindaskitchen.co.za (WordPress + WooCommerce + mk-core + engine plugins)
               |  signed webhooks -> storefront /api/revalidate
```

The rules that follow from this:

- **Melinda's store keeps running if the engine repo breaks.** A bad engine commit only reaches her when someone deliberately bumps her pinned version, and that bump goes through her staging first.
- **No central Operandi server sits in her request path.** The blueprint rules out a shared SaaS control plane for the first release, and a single point of failure across clients is exactly what that rule is there to prevent.
- **npm scope.** GitHub Packages requires the package scope to match the org, so packages publish as `@wedeliver01/ce-*`, for example `@wedeliver01/ce-commerce-client`. PHP namespace `CommerceEngine\`, plugin slugs `commerce-engine-*`, REST namespace `commerce-engine/v1`.

### Where code starts, and when it moves

**Build it in Melinda first, then extract it once it has worked in production** (blueprint 1.3). There is one exception: code that wraps a third-party plugin and contains no client data starts in the engine.

| Today | Home now | Destination | When |
|---|---|---|---|
| SA checkout localisation (Province, Postal code) | `mk-core` | `commerce-engine-za` extension | Phase 2. Every SA client needs it. |
| Billing phone required | `mk-core` | Engine checkout setting (feature flag) | Phase 2 |
| "Place order" button text | `mk-core` | Dropped | Phase 5. The headless checkout owns its own button. |
| `mk_occasion` taxonomy | `mk-core` | Stays client-specific | Revisit once a second client needs a second browse axis |
| Collect / local pickup | Commit 1 checkout plugin | `mk-core` now, engine fulfilment extension later | Phase 1, then Phase 7 |
| Product Add-Ons Store API bridge | Does not exist | `commerce-engine-addons` (exception rule: generic wrapper) | Phase 3 |
| Design tokens (`docs/reference/tokens.css`) | This repo | Stays here as Melinda's token values. The engine owns the token *schema*. | Phase 4 |
| PHP theme `melindas-kitchen` | This repo | Stays here as the rollback path after cutover | Retire one release cycle after Phase 5 |

---

## 2. Domains and environments

| Purpose | Phase 1 (PHP build) | After headless cutover (Phase 5) |
|---|---|---|
| Production storefront | `melindaskitchen.co.za` (WordPress, PHP theme) | `melindaskitchen.co.za` (Next.js) |
| Production WordPress | same host | `cms.melindaskitchen.co.za` (admin, API, payment returns) |
| Staging storefront | `staging.melindaskitchen.co.za` (WordPress, PHP theme) | `staging.melindaskitchen.co.za` (Next.js) |
| Staging WordPress | same host | `cms-staging.melindaskitchen.co.za` |
| Old interim staging | `melindaskitchen.operandis.co.za` | Retired after Phase 1 step 1.1 |

**Why `staging.melindaskitchen.co.za` and not `melindaskitchen.co.za/staging`.** A path on the apex is served by whatever server the apex points at, and until the DNS cutover that is still the previous developer's host. `/staging` would have to live on their server, which defeats the purpose. Even after cutover, WordPress in a subdirectory of the live store shares cookies with production, needs its own Nginx location rules and is easy to get indexed by accident. The subdomain needs one DNS record and none of those workarounds.

The staging subdomain needs only one `A` record (`staging` -> `139.84.250.19`) on whichever nameservers are authoritative today. It doesn't require moving nameservers and doesn't touch MX, so her email is not at risk from this step. The full nameserver move stays in step 1.8, done the careful way.

---

## 3. Phases

The sizes are relative: S is days, M is one to two weeks, L is several weeks. "Blueprint" refers to the phase numbers in the Commerce Engine document.

### Phase 1: Launch Melinda on the PHP build (now)

*Covers the agreed brief and the remaining R5,000. Corresponds to blueprint Phase 0: discovery, staging, backup, payment proof.*

Steps run in this order. Each step leaves the site working.

| # | Step | Size | Notes |
|---|---|---|---|
| 1.1 | **Staging on `staging.melindaskitchen.co.za`** | S | One A record, a new CloudPanel site, clone the current Operandi staging (files + DB), `wp search-replace`, SSL, basic auth, `blog_public = 0`. A script lives in `scripts/` so it can be repeated. |
| 1.2 | **Remove duplicate products** | S | Dry-run report first, then move the duplicates to trash. Never force-delete. |
| 1.3 | **Import product add-ons** | S | Buy and install WooCommerce Product Add-Ons, then run `import-addons.php`. Hardened importer: dry-run, idempotent, reports unmatched titles. |
| 1.4 | **Collect / pickup in `mk-core`, Delicate Courier installed** | M | Port pickup from the Commit 1 plugin into `mk-core`. Install and configure Delicate Courier. |
| 1.5 | **FAQ on Contact, occasion terms assigned** | S | Content script plus an occasion-term mapping file that Melinda can review before it's applied. |
| 1.6 | **Payments** | M | (a) Diagnose the 796 orders stuck on hold: the BobPay callback URL, IPN logs, and whether the return reaches `wc-api`. (b) Choose the international-friendly gateway. See the readiness rule below. **Raise (a) with Melinda before launch.** |
| 1.7 | **Launch hygiene** | S | SMTP provider plus SPF/DKIM, page caching (not WP Rocket), Rank Math redirect map, off-host backups with **one tested restore**, close ports 22/8443 publicly. |
| 1.8 | **DNS migration and launch** | M | The HANDOVER section 6 sequence: capture every record, build the Cloudflare zone, verify MX/TXT by hand, then move nameservers. Cut over, `blog_public = 1`, watch orders for 72 hours. |

**Headless-readiness rules for Phase 1.** These are cheap to follow now and expensive to fix later:

1. **Choose the new gateway on Checkout Block support.** The block checkout is built on the Store API, so a gateway that supports it has effectively passed the blueprint's go/no-go test (section 7.1) on a live site. Candidates to evaluate: Paystack, Peach Payments, Yoco, PayFast. Confirm block support for the *current* plugin versions before choosing.
2. **Test the Checkout Block on staging,** covering add-ons, pickup and Delicate Courier. Note that `mk-core`'s `woocommerce_checkout_fields` filter (billing phone required) only applies to the classic checkout. Under blocks, that rule has to move to a WooCommerce setting or Store API validation. If all three work under blocks, they will work headless.
3. **Keep marketing pages in core Gutenberg blocks.** This is already the convention. It means the Phase 4 renderer can read them through the API.
4. **Keep HPOS on, and no direct order-table queries** in any new code.

**Exit criteria:** a real order moves from paid to processing without manual intervention, the confirmation email lands in an inbox and not spam, a restore has been tested, the old host has been decommissioned, and Melinda has signed off.

### Phase 2: Commerce Engine foundation (new repo)

*Blueprint Phases 1 and 4, partly. Tickets CE-003 to CE-005 and CE-011, plus the ZA extension.*

Can start in parallel once Phase 1 reaches step 1.6, because it only *reads* from Melinda's staging.

- Create `WeDeliver01/commerce-engine`: pnpm workspaces, Turborepo, strict TypeScript, GitHub Actions CI (lint, typecheck, test, build, secret scan).
- `packages/contracts` (Zod), `packages/commerce-client` (typed Store API), `packages/wp-admin-client` (server-only, lint-enforced), `packages/config` (validated env).
- `wordpress/plugins/commerce-engine-core` (bootstrap, feature registry, `/health`) and `commerce-engine-za` (localisation extracted from `mk-core`).
- ADRs 001 to 005: cart session strategy, gateway, theme config storage, client isolation, deployment. Version compatibility matrix.
- Publish `v0.1.0` to GitHub Packages.

**Exit criteria:** a scratch Next.js app installs `@wedeliver01/ce-commerce-client@0.1.0` and renders Melinda's staging catalogue with no secrets in the browser bundle.

### Phase 3: Melinda headless vertical slice

*Blueprint Phase 2. The highest-risk technical work in the whole plan.*

- Add `apps/storefront` to this repo: a thin Next.js app that depends on the pinned engine packages.
- `commerce-engine-addons` plugin in the engine: exposes `_product_addons` through a Store API extension (`ExtendSchema`) for reading, and validates add-on selections server-side at add-to-cart. Pricing is always computed by WooCommerce. Covers flat, percentage and quantity-based prices and file uploads. 36 products and 858 option rows make the test fixture.
- Vertical slice: product list, then product detail with add-ons, cart, checkout with pickup or courier, sandbox payment, then the order visible in WooCommerce with the correct add-on lines.
- Cart-Token held in an HttpOnly cookie by the storefront server.

**Exit criteria:** a sandbox purchase of a cake with five add-ons produces an order whose total matches the PHP site to the cent. Duplicate submit, back button and payment failure are all tested.

### Phase 4: Design, content and SEO parity

*Blueprint Phase 3.*

- Engine: `design-tokens` schema, `ui` primitives, `theme-engine` section registry.
- Melinda: her token values from `docs/reference/tokens.css`, and client sections reproducing the measured layouts (split header, statement band, framed images, overlay tiles, parallax banner). These are checked against `docs/reference/live-html/`, not inferred.
- WordPress pages rendered from core blocks through the API, so Melinda keeps editing her pages in WordPress.
- SEO: exact title tags (homepage title preserved), canonical URLs, sitemap, Product JSON-LD, the redirect map enforced in Next.js middleware or Nginx, and SSR/SSG on every public route.

**Exit criteria:** visual diff against the PHP build passes on five reference pages, every URL in `data/url-list.txt` resolves or redirects correctly, and Core Web Vitals are at least as good as the PHP build on a mid-range phone.

### Phase 5: Melinda headless cutover and hardening

*Blueprint Phase 6.*

- WordPress moves to `cms.melindaskitchen.co.za`, with wp-admin restricted. The storefront takes the apex.
- Monitoring and alerting cover uptime, 5xx, checkout and payment failures, webhook failures and disk. Security headers and rate limits. Runbooks.
- **Rollback:** the PHP theme is still installed on the CMS, so rolling back means pointing the apex back at WordPress. This is tested before cutover.

**Exit criteria:** blueprint Appendix B checklist signed off for Melinda's store.

### Phase 6: Owner control panel

*Blueprint Phase 5.* `commerce-engine-theme` plugin (draft/publish, revisions), plus owner UI for hero, homepage sections, navigation and banners. Products and orders stay in WooCommerce admin. Melinda is the first user and gives the feedback.

### Phase 7: Template and second client

*Blueprint Phase 7.* Tag `v1.0.0`, create a client template repo, automate per-client Docker Compose provisioning, and extract pickup/fulfilment into the engine. Decide (ADR) whether Melinda's WordPress moves from CloudPanel into Compose so that she runs the same template as every other client.

**Exit criteria:** a demo client is provisioned from the template without copying secrets or editing shared code.

---

## 4. Commercial note

The agreed R5,000 covers **Phase 1 only**. Phases 3 to 5 rebuild her frontend, which is outside the brief. Two clean options:

- **Operandi pilot.** Operandi funds the headless rebuild as the engine's reference implementation, and Melinda is told her site is moving to a new platform at no extra cost. This needs her written approval before her live store changes (blueprint 1.3).
- **New engagement.** Price Phases 3 to 5 separately once Phase 1 has launched.

Phases 2, 6 and 7 are Operandi's own platform investment in either case.

---

## 5. Decisions needed

**From Melinda (Phase 1):**
1. How does she currently know an order is paid? (the 796 orders on hold)
2. Approval to buy WooCommerce Product Add-Ons, and approval and billing for the Google Maps API key.
3. Addington CF licence, which gold to standardise on, justified body copy.
4. Written approval for her store to be the Commerce Engine pilot (if that option is chosen).

**From Operandi:**
1. Pilot or new engagement (section 4).
2. Which payment gateway to choose (step 1.6). This decides the headless checkout as well.
3. Create the `WeDeliver01/commerce-engine` repository when Phase 2 starts.

---

## 6. Next actions

1. Add DNS record `staging A 139.84.250.19` on the currently authoritative nameservers.
2. Run step 1.1 using `scripts/setup-staging.sh` (to be written next).
3. Continue with steps 1.2 to 1.5 in order.
