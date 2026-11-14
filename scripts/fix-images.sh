#!/bin/bash
# Product image sizes. Run from the WordPress root as the site user.
set -e
wp option update woocommerce_thumbnail_image_width 600
wp option update woocommerce_single_image_width 800
wp option update woocommerce_thumbnail_cropping '1:1'
echo "Regenerating (this takes a few minutes)..."
wp media regenerate --yes
wp cache flush
echo
echo "Registered sizes:"
wp eval 'foreach (wp_get_registered_image_subsizes() as $n=>$s) printf("  %-24s %dx%d %s\n", $n, $s["width"], $s["height"], $s["crop"]?"crop":"");'
