#!/bin/bash
# Sideloads the homepage photography from the live site and records the
# attachment IDs the theme reads. Run from the WordPress root as the site
# user, while melindaskitchen.co.za is still online.
set -e
BASE="https://melindaskitchen.co.za/wp-content/uploads"

echo "Hero slider..."
SLIDES=""
for f in Z1B0058 Z1B0006-2 Z1B0091 Z1B0071 Z1B0015 Z1B0022 Z1B0030 Z1B0081 Z1B0104 Z1B0116 SS1-190-1 SS2-38 Styled-Shoot-Hello-Timber-16-Aug-2020-110; do
  ID=$(wp media import "$BASE/2020/09/$f.jpg" --porcelain 2>/dev/null) || { echo "  skipped $f"; continue; }
  SLIDES="$SLIDES,$ID"
  echo "  $f -> $ID"
done
wp option update mk_hero_slides "${SLIDES#,}"

echo "Story image..."
wp option update mk_img_story "$(wp media import "$BASE/2020/09/Z1B0150-2.jpg" --porcelain)"

echo "Banner..."
wp option update mk_img_banner "$(wp media import "$BASE/2020/09/homepage2-1.jpg" --porcelain)"

echo "Tiles..."
wp option update mk_img_tile_weddings "$(wp media import "$BASE/2020/09/Z1B0004-2.jpg" --porcelain)"
wp option update mk_img_tile_shop     "$(wp media import "$BASE/2020/09/Z1B0110.jpg"   --porcelain)"
wp option update mk_img_tile_contact  "$(wp media import "$BASE/2020/09/Z1B0181.jpg"   --porcelain)"

wp cache flush
echo
echo "Done."
wp option get mk_hero_slides
