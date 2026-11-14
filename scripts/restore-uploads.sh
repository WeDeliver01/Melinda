#!/bin/bash
# Restores Melinda's media library from the UpdraftPlus uploads archives.
#
# Extracts only the year folders (the real media). Skips revslider,
# product_addons_uploads, wc-logs, facebook_for_woocommerce, cartflows-logs
# and the other plugin debris: 1,794 files and 225 MB of it.
#
# Also strips WordPress-generated -WIDTHxHEIGHT copies, since the new install
# uses different image sizes and regenerates them anyway.
#
# Usage, from the WordPress root as the site user:
#   bash restore-uploads.sh ~/uploads.zip ~/uploads2.zip
set -e

DEST="wp-content/uploads"
[ -d "$DEST" ] || { echo "Run this from the WordPress root."; exit 1; }
[ $# -ge 1 ] || { echo "Usage: bash restore-uploads.sh <zip> [zip...]"; exit 1; }

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

for z in "$@"; do
  echo "Extracting $(basename "$z")..."
  unzip -q -o "$z" 'uploads/20*' -d "$TMP" || true
done

echo "Removing generated thumbnail sizes..."
find "$TMP/uploads" -type f -regextype posix-extended \
  -regex '.*-[0-9]+x[0-9]+\.(jpg|jpeg|png|webp|gif)$' -delete

echo "Copying into place..."
cp -rn "$TMP/uploads/." "$DEST/"

echo "Files restored: $(find "$TMP/uploads" -type f | wc -l)"
echo
echo "Registering with WordPress (this takes a few minutes)..."
wp media import "$DEST"/20*/*/*.{jpg,jpeg,png} --skip-copy --porcelain > /dev/null 2>&1 || true

echo "Regenerating thumbnails..."
wp media regenerate --yes

wp cache flush
echo "Done."
