# Migration scripts

Run from the WordPress root as the site user, not root.

```bash
ssh root@100.66.152.34
su - operandis-melindaskitchen
cd htdocs/melindaskitchen.operandis.co.za
```

Several scripts pull assets from the live site. Run them while `melindaskitchen.co.za`
is still reachable.

| Script | Purpose |
|---|---|
| `restore-uploads.sh` | Restores the media library from UpdraftPlus archives. Extracts year folders only, skipping 1,794 files and 225MB of plugin debris, and strips generated thumbnail sizes. Takes both uploads zips as arguments. |
| `import-brand.sh` | Header and footer logos, contact details, product image sizes, thumbnail regeneration. |
| `import-home-images.sh` | 19 homepage photographs: 13 slider images, story image, banner, three tiles. Records attachment IDs in options the theme reads. |
| `import-shop.sh` | Shop parallax banner and intro content. |
| `import-pages2.sh` | FAQ and Weddings content, shop banner and intro. |
| `fix-images.sh` | Sets WooCommerce thumbnail sizes and regenerates. Run if product images render at the wrong size. |
| `import-addons.php` | Restores Product Add-Ons configuration onto imported products, matched by title. Run with `wp eval-file scripts/import-addons.php` from the directory holding `product-addons.json`. Requires the Product Add-Ons plugin installed and active. Safe to re-run. |

## Order

```bash
bash scripts/restore-uploads.sh ~/uploads.zip ~/uploads2.zip
bash scripts/import-brand.sh
bash scripts/import-home-images.sh
bash scripts/import-shop.sh
bash scripts/import-pages2.sh
cp data/product-addons.json . && wp eval-file scripts/import-addons.php
```
