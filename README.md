# Melinda's Kitchen

Custom WooCommerce build for Melinda's Kitchen, a studio bakery in Faerie Glen, Pretoria. Built by Operandi.

Read **[HANDOVER.md](HANDOVER.md)** first. It covers project state, commercial position, technical findings and everything outstanding.

## Live environments

| | |
|---|---|
| Staging | https://melindaskitchen.operandis.co.za (coming-soon mode) |
| Production | https://melindaskitchen.co.za (old site, still trading) |
| Server | `web01.operandis.co.za` / Tailscale `100.66.152.34` |
| Panel | https://100.66.152.34:8443 |

Server access is over Tailscale only. Both the server and the dev machine are on the tailnet.

```bash
ssh root@100.66.152.34
su - operandis-melindaskitchen
cd htdocs/melindaskitchen.operandis.co.za
```

## Stack

WordPress 7.1, WooCommerce 11.1.0, PHP 8.3, MariaDB, Redis, Nginx, CloudPanel on a Vultr VPS.

Frontend is a custom classic PHP theme with modern CSS, Alpine.js and Vite. Business logic lives in the `mk-core` plugin so it survives a theme change.

## Layout

```
wordpress/themes/melindas-kitchen/   theme source
wordpress/plugins/mk-core/           business logic
data/                                products, add-ons, copy, page content
scripts/                             migration and import scripts
docs/reference/                      design tokens, captured live pages
```

## Theme development

```bash
cd wordpress/themes/melindas-kitchen
npm install
npm run build          # production
npm run dev            # dev server on :5173
```

For `npm run dev` to be picked up, add this to `wp-config.php` and remove it before deploying:

```php
define( 'MK_VITE_DEV', true );
```

`dist/` is committed so the theme works on upload without a build step.

## Deploying the theme

```bash
rsync -av --delete \
  wordpress/themes/melindas-kitchen/ \
  operandis-melindaskitchen@100.66.152.34:~/htdocs/melindaskitchen.operandis.co.za/wp-content/themes/melindas-kitchen/
ssh ... "cd ~/htdocs/... && wp cache flush"
```

## Import scripts

Run from the WordPress root as the site user. Several pull assets from the live site, so run them while `melindaskitchen.co.za` is still up.

```bash
bash scripts/restore-uploads.sh <uploads.zip> <uploads2.zip>
bash scripts/import-brand.sh          # logos, contact details, image sizes
bash scripts/import-home-images.sh    # 19 homepage photographs
bash scripts/import-shop.sh           # shop banner and intro
bash scripts/import-pages2.sh         # FAQ and Weddings content
wp eval-file scripts/import-addons.php   # needs Product Add-Ons installed
```

## Immediate next steps

1. Install WooCommerce Product Add-Ons, then run `import-addons.php`. The site cannot take a real order without it.
2. Resolve the duplicate products (39 showing against 29 imported).
3. Connect an SMTP provider in FluentSMTP, with SPF and DKIM.
4. Start the DNS migration off the previous developer's nameservers.

Full detail in HANDOVER.md section 6.

## Conventions

- No em dashes in any client-facing copy.
- Presentation goes in the theme. Business rules go in `mk-core`.
- Design values come from `docs/reference/`, measured from the client's own stylesheets. Do not estimate them.
