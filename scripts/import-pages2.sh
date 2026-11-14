#!/bin/bash
# FAQ and Weddings content, plus the shop banner and intro.
# Run from the WordPress root as the site user.
set -e
BASE="https://melindaskitchen.co.za/wp-content/uploads"

echo "Shop banner and intro..."
wp option update mk_img_shop_banner "$(wp media import "$BASE/2020/09/titlebar1.jpg" --porcelain)"
SHOP_ID=$(wp option get woocommerce_shop_page_id)
wp post update "$SHOP_ID" shop-intro.html --post_content

echo "Weddings..."
WED=$(wp post list --post_type=page --name=weddings --field=ID)
[ -n "$WED" ] && wp post update "$WED" weddings.html --post_content
WED_IMG=$(wp media import "$BASE/2020/09/Z1B0004-2.jpg" --porcelain)
[ -n "$WED" ] && wp post meta update "$WED" _thumbnail_id "$WED_IMG"
[ -n "$WED" ] && wp post update "$WED" --post_title='Celebrate your beautiful day' --post_excerpt='with a beautiful cake'

echo "FAQ..."
FAQ_ID=$(wp post list --post_type=page --name=faq --field=ID)
if [ -z "$FAQ_ID" ]; then
  FAQ_ID=$(wp post create --post_type=page --post_title='FAQ' --post_name=faq --post_status=publish --porcelain)
fi
wp post update "$FAQ_ID" faq.html --post_content

wp cache flush
echo
echo "Done. Weddings: $WED   FAQ: $FAQ_ID"
