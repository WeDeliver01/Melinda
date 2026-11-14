#!/bin/bash
# Pushes the theme, mk-core, mu-plugins, scripts and data from this repo to a
# site on the VPS. Run from the repo root on a machine that is on the tailnet.
#
#   bash scripts/deploy.sh staging           # staging.melindaskitchen.co.za
#   bash scripts/deploy.sh interim           # melindaskitchen.operandis.co.za
#   bash scripts/deploy.sh staging --dry-run # show what would change
#
# Code goes into wp-content. Scripts and data go to ~/melinda-ops in the site
# user's home, so import scripts can be run from the WordPress root with:
#   wp eval-file ~/melinda-ops/scripts/<name>.php
#
# Production is deliberately not a target until launch.

set -euo pipefail

HOST="root@100.66.152.34"
TARGET="${1:-}"
DRY=""
[ "${2:-}" = "--dry-run" ] && DRY="--dry-run"

case "$TARGET" in
	staging) USER_="melindaskitchen-staging";    DOMAIN="staging.melindaskitchen.co.za"; GUARD=1 ;;
	interim) USER_="operandis-melindaskitchen";  DOMAIN="melindaskitchen.operandis.co.za"; GUARD=0 ;;
	*) sed -n '2,13p' "$0"; exit 1 ;;
esac

[ -f HANDOVER.md ] || { echo "Run from the repo root."; exit 1; }

ROOT="/home/$USER_/htdocs/$DOMAIN"
OPS="/home/$USER_/melinda-ops"
RS1=(rsync -az $DRY --chown="$USER_:$USER_" --exclude node_modules --exclude .DS_Store)
RS=("${RS1[@]}" --delete)

echo "Deploying to $DOMAIN ($TARGET)${DRY:+ [dry run]}"

# dist/ is gitignored, so always build from source before shipping the theme.
( cd wordpress/themes/melindas-kitchen && npm ci --silent && npm run build --silent )
[ -f wordpress/themes/melindas-kitchen/dist/.vite/manifest.json ] || { echo "Theme build produced no manifest."; exit 1; }

"${RS[@]}" --exclude src wordpress/themes/melindas-kitchen/ "$HOST:$ROOT/wp-content/themes/melindas-kitchen/"
"${RS[@]}" wordpress/plugins/mk-core/ "$HOST:$ROOT/wp-content/plugins/mk-core/"

if [ "$GUARD" = 1 ]; then
	ssh "$HOST" "install -d -o $USER_ -g $USER_ $ROOT/wp-content/mu-plugins"
	"${RS1[@]}" wordpress/mu-plugins/mk-staging-guard.php "$HOST:$ROOT/wp-content/mu-plugins/"
fi

ssh "$HOST" "install -d -o $USER_ -g $USER_ $OPS"
"${RS[@]}" scripts data "$HOST:$OPS/"

if [ -z "$DRY" ]; then
	ssh "$HOST" "sudo -u $USER_ -H bash -c 'cd $ROOT && wp plugin activate mk-core --quiet; wp cache flush --quiet'"
fi
echo "Done."
