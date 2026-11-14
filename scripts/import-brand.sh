#!/bin/bash
# Logos, footer assets, menu and image sizes. Run from the WordPress root
# as the site user, while melindaskitchen.co.za is still online.
set -e
BASE="https://melindaskitchen.co.za/wp-content/uploads"

echo "Logos..."
HEADER_LOGO=$(wp media import "$BASE/2020/09/Logo-01-2.png" --porcelain)
FOOTER_LOGO=$(wp media import "$BASE/2020/09/logo2-01.png" --porcelain)
wp theme mod set custom_logo "$HEADER_LOGO"
wp option update mk_img_footer_logo "$FOOTER_LOGO"
echo "  header $HEADER_LOGO / footer $FOOTER_LOGO"

echo "Contact details..."
wp option update mk_phone '071 676 6777'
wp option update mk_email 'info@melindaskitchen.co.za'
wp option update mk_typekit_id ''   # kit serves sweet-sans-pro, which is unused

echo "Product image sizes (matching the old site)..."
wp option update woocommerce_thumbnail_image_width 300
wp option update woocommerce_single_image_width 600
wp option update woocommerce_thumbnail_cropping '1:1'

echo "Rebuilding thumbnails..."
wp media regenerate --yes --only-missing=0 --skip-delete 2>/dev/null || wp media regenerate --yes

wp cache flush
echo "Done."
