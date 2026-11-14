#!/bin/bash
# Shop page banner + intro content. Run from the WordPress root as the site user.
set -e
BASE="https://melindaskitchen.co.za/wp-content/uploads"

echo "Shop banner..."
wp option update mk_img_shop_banner "$(wp media import "$BASE/2020/09/titlebar1.jpg" --porcelain)"

SHOP_ID=$(wp option get woocommerce_shop_page_id)
echo "Shop page id: $SHOP_ID"
wp post update "$SHOP_ID" shop-intro.html --post_content

wp cache flush
echo "Done."
