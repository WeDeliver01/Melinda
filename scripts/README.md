# Scripts

## Getting code and scripts onto a site

From the repo root, on a machine on the tailnet:

```bash
bash scripts/deploy.sh staging --dry-run
bash scripts/deploy.sh staging
```

This builds the theme, then syncs the theme, `mk-core`, the staging guard mu-plugin, and `scripts/` and `data/` into `~/melinda-ops/` for the site user. Every `.php` script below is then run from the WordPress root as the **site user, never root**:

```bash
ssh root@100.66.152.34
su - melindaskitchen-staging
cd htdocs/staging.melindaskitchen.co.za
```

**Every PHP script is report-only unless given `apply`.** Run it once without `apply`, read the output, then run it again with `apply`. Take a backup first: `wp db export ~/before-<script>.sql`.

## Phase 1 run order (roadmap steps)

| Step | Command | Notes |
|---|---|---|
| 1.1 | `bash setup-staging.sh site` (as root) | Creates `staging.melindaskitchen.co.za` as a clone of the Operandi staging. `ssl` stage after the DNS record exists. |
| 1.2 | `wp eval-file ~/melinda-ops/scripts/reconcile-products.php` | Restores the source publish status and trashes true repeats. Expect 10 drafts to be unpublished. |
| 1.3 | `wp eval-file ~/melinda-ops/scripts/import-addons.php` | Needs Product Add-Ons active. `force` overwrites configs edited in admin. |
| 1.4 | `wp eval-file ~/melinda-ops/scripts/setup-fulfilment.php apply address="..."` | Local Pickup, phone required, delivery-only wedding cakes. See `docs/runbooks/fulfilment.md`. |
| 1.5a | `wp eval-file ~/melinda-ops/scripts/apply-contact.php` | FAQ onto Contact. Fluent Forms embedded by title. |
| 1.5b | `wp eval-file ~/melinda-ops/scripts/assign-occasions.php` | From `data/occasion-map.csv`. Have Melinda review the PROPOSED rows first. |

## All scripts

| Script | Run as | Purpose |
|---|---|---|
| `deploy.sh` | you, locally | Build and sync code, scripts and data to `staging` or `interim`. |
| `setup-staging.sh` | root on server | Create, SSL and resync the staging site. |
| `reconcile-products.php` | site user | Shop vs `melindas-products.csv`: status fixes, repeat trashing, report of extras. |
| `import-addons.php` | site user | Product Add-Ons from `product-addons.json`, matched by normalised title. |
| `setup-fulfilment.php` | site user | Collection and delivery configuration. |
| `apply-contact.php` | site user | Contact page with FAQ from `page-content/contact.html`. |
| `assign-occasions.php` | site user | Occasion terms from `occasion-map.csv`. |
| `restore-uploads.sh` | site user | Media library from UpdraftPlus archives, skipping 225MB of plugin debris and generated thumbnails. |
| `import-brand.sh` | site user | Logos, contact details, product image sizes, thumbnail regeneration. |
| `import-home-images.sh` | site user | 19 homepage photographs. Records attachment IDs in options the theme reads. |
| `import-shop.sh` | site user | Shop parallax banner and intro content. |
| `import-pages2.sh` | site user | Weddings content, shop banner and intro. Also writes the standalone FAQ page, which is superseded by `apply-contact.php`. |
| `fix-images.sh` | site user | WooCommerce thumbnail sizes and regeneration. |

The `import-*.sh` scripts pull images from the live site, so run them while `melindaskitchen.co.za` is still up. They were run on the interim staging site and are carried over to `staging.` by `setup-staging.sh`, so they do not need to be run again.

## Tests

```bash
php wordpress/plugins/mk-core/tests/test-fulfilment-rules.php
```
