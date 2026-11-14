#!/bin/bash
# Creates the staging site on staging.melindaskitchen.co.za as a clone of the
# current Operandi staging site, on the same VPS.
#
# Run as ROOT on the server (CloudPanel's clpctl needs root). Every WordPress
# command inside is run as the new site user, never root.
#
#   ssh root@100.66.152.34
#   bash setup-staging.sh site      # 1. create site, database, clone files and DB
#   bash setup-staging.sh ssl       # 2. once DNS resolves: Let's Encrypt certificate
#   bash setup-staging.sh resync    # later: refresh staging DB and uploads from source
#
# Each stage is safe to re-run. Nothing on the source site is modified: the
# source database is only exported, and files are only read.
#
# DNS needed before the "ssl" stage, and nothing else:
#   staging   A   139.84.250.19
# Do not move nameservers or touch MX records for this.

set -euo pipefail

SRC_DOMAIN="melindaskitchen.operandis.co.za"
SRC_USER="operandis-melindaskitchen"
SRC_ROOT="/home/${SRC_USER}/htdocs/${SRC_DOMAIN}"

NEW_DOMAIN="staging.melindaskitchen.co.za"
NEW_USER="melindaskitchen-staging"
NEW_ROOT="/home/${NEW_USER}/htdocs/${NEW_DOMAIN}"
NEW_DB="mk_staging"
NEW_DB_USER="mk_staging"
CRED_FILE="/root/.mk-staging-credentials"   # root-only, never committed

PHP_VERSION="8.3"
REDIS_DB=1                  # source uses 0. Separate DB so the caches never mix.
SERVER_IP="139.84.250.19"

STAGE="${1:-}"

log()  { printf '\n\033[1m== %s\033[0m\n' "$*"; }
die()  { printf '\033[31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }
# Plain (non-login) shell: profile output must never end up inside a SQL dump.
as_src() { sudo -u "$SRC_USER" -H bash -c "cd '$SRC_ROOT' && $*"; }
as_new() { sudo -u "$NEW_USER" -H bash -c "cd '$NEW_ROOT' && $*"; }

[ "$(id -u)" -eq 0 ] || die "Run as root."
command -v clpctl >/dev/null || die "clpctl not found. Is this the CloudPanel server?"
[ -d "$SRC_ROOT" ] || die "Source site not found at $SRC_ROOT"

gen_pass() { tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32; }

load_or_create_creds() {
	if [ -f "$CRED_FILE" ]; then
		# shellcheck disable=SC1090
		source "$CRED_FILE"
	else
		NEW_USER_PASS="$(gen_pass)"
		NEW_DB_PASS="$(gen_pass)"
		umask 077
		printf 'NEW_USER_PASS=%q\nNEW_DB_PASS=%q\n' "$NEW_USER_PASS" "$NEW_DB_PASS" >"$CRED_FILE"
		echo "Credentials written to $CRED_FILE (root-only)."
	fi
}

# Copies the database and files from source into the new site, then rewrites
# the URL and applies the staging configuration. Used by "site" and "resync".
clone_from_source() {
	local dump="/tmp/mk-staging-$$.sql"

	log "Exporting source database (read-only)"
	as_src "wp db export - --single-transaction --quick" >"$dump"
	chmod 600 "$dump"

	log "Copying files (wp-config.php excluded)"
	rsync -a --delete \
		--exclude 'wp-config.php' \
		--exclude 'wp-content/cache/' \
		--exclude 'wp-content/updraft/' \
		--exclude 'wp-content/upgrade/' \
		--exclude '.user.ini' \
		"$SRC_ROOT/" "$NEW_ROOT/"
	chown -R "$NEW_USER:$NEW_USER" "$NEW_ROOT"

	if [ ! -f "$NEW_ROOT/wp-config.php" ]; then
		log "Writing wp-config.php"
		as_new "wp config create --dbname='$NEW_DB' --dbuser='$NEW_DB_USER' --dbpass='$NEW_DB_PASS' --dbhost=127.0.0.1 --skip-check"
	fi

	log "Applying staging configuration"
	as_new "wp config set DISABLE_WP_CRON true --raw"
	as_new "wp config set WP_MEMORY_LIMIT 512M"
	as_new "wp config set WP_POST_REVISIONS 5 --raw"
	as_new "wp config set DISALLOW_FILE_EDIT true --raw"
	as_new "wp config set WP_REDIS_HOST 127.0.0.1"
	as_new "wp config set WP_REDIS_PORT 6379 --raw"
	as_new "wp config set WP_REDIS_DATABASE $REDIS_DB --raw"
	as_new "wp config set WP_REDIS_PREFIX mkstg_"
	as_new "wp config set WP_ENVIRONMENT_TYPE staging"
	# mk-staging-guard.php blocks all outgoing mail unless MK_STAGING_MAIL_TO is
	# set, in which case everything is redirected there. Set it by hand:
	#   wp config set MK_STAGING_MAIL_TO you@example.com

	log "Importing database"
	cp "$dump" "$NEW_ROOT/.mk-import.sql"
	chown "$NEW_USER:$NEW_USER" "$NEW_ROOT/.mk-import.sql"
	rm -f "$dump"
	as_new "wp db import .mk-import.sql && rm -f .mk-import.sql"

	log "Rewriting URLs ($SRC_DOMAIN -> $NEW_DOMAIN)"
	as_new "wp search-replace 'https://$SRC_DOMAIN' 'https://$NEW_DOMAIN' --all-tables-with-prefix --skip-columns=guid --precise --report-changed-only"
	as_new "wp search-replace '//$SRC_DOMAIN' '//$NEW_DOMAIN' --all-tables-with-prefix --skip-columns=guid --precise --report-changed-only"

	log "Staging safety settings"
	as_new "wp option update blog_public 0"
	# The guard also hides every online payment gateway on staging unless it is
	# listed in MK_STAGING_ALLOW_GATEWAYS (put it in sandbox mode first).
	install_guard

	as_new "wp redis enable" || echo "  (redis-cache drop-in not enabled, check Redis Object Cache plugin)"
	as_new "wp rewrite flush"
	as_new "wp cache flush"
}

install_guard() {
	local here guard
	here="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
	guard="$here/../wordpress/mu-plugins/mk-staging-guard.php"
	[ -f "$guard" ] || guard="$here/mk-staging-guard.php"
	if [ -f "$guard" ]; then
		install -d -o "$NEW_USER" -g "$NEW_USER" "$NEW_ROOT/wp-content/mu-plugins"
		install -o "$NEW_USER" -g "$NEW_USER" -m 644 "$guard" "$NEW_ROOT/wp-content/mu-plugins/mk-staging-guard.php"
		echo "  staging guard mu-plugin installed"
	else
		echo "  WARNING: mk-staging-guard.php not found next to this script or in the repo. Outgoing mail is NOT redirected."
	fi
}

install_cron() {
	local line="*/5 * * * * cd $NEW_ROOT && $(command -v wp) cron event run --due-now >/dev/null 2>&1"
	if ! crontab -u "$NEW_USER" -l 2>/dev/null | grep -qF "$NEW_ROOT"; then
		( crontab -u "$NEW_USER" -l 2>/dev/null || true; echo "$line" ) | crontab -u "$NEW_USER" -
		echo "  system cron installed for $NEW_USER"
	fi
}

case "$STAGE" in
site)
	load_or_create_creds

	if [ -d "/home/$NEW_USER" ]; then
		log "Site user $NEW_USER already exists, skipping site creation"
	else
		log "Creating CloudPanel site $NEW_DOMAIN"
		clpctl site:add:php \
			--domainName="$NEW_DOMAIN" \
			--phpVersion="$PHP_VERSION" \
			--vhostTemplate='WordPress' \
			--siteUser="$NEW_USER" \
			--siteUserPassword="$NEW_USER_PASS"
	fi

	if mysql -e "USE \`$NEW_DB\`" 2>/dev/null; then
		log "Database $NEW_DB already exists, skipping"
	else
		log "Creating database $NEW_DB"
		clpctl db:add \
			--domainName="$NEW_DOMAIN" \
			--databaseName="$NEW_DB" \
			--databaseUserName="$NEW_DB_USER" \
			--databaseUserPassword="$NEW_DB_PASS"
	fi

	clone_from_source
	install_cron

	log "Done"
	echo "Site files:   $NEW_ROOT"
	echo "Next:         add DNS 'staging A $SERVER_IP', then: bash $0 ssl"
	echo "Recommended:  CloudPanel > $NEW_DOMAIN > Security > Basic Auth, with the"
	echo "              payment gateway's callback IPs whitelisted once a gateway is chosen."
	;;

ssl)
	resolved="$(getent ahostsv4 "$NEW_DOMAIN" | awk 'NR==1{print $1}')"
	[ "$resolved" = "$SERVER_IP" ] || die "$NEW_DOMAIN resolves to '${resolved:-nothing}', expected $SERVER_IP. Add the DNS record first."
	log "Issuing Let's Encrypt certificate"
	clpctl lets-encrypt:install:certificate --domainName="$NEW_DOMAIN"
	echo "Check: curl -sI https://$NEW_DOMAIN | head -1"
	;;

resync)
	load_or_create_creds
	[ -d "$NEW_ROOT" ] || die "Staging site does not exist yet. Run: bash $0 site"
	read -r -p "This OVERWRITES the staging database and files with the source. Continue? [y/N] " ok
	[ "$ok" = "y" ] || exit 1
	clone_from_source
	;;

*)
	sed -n '2,16p' "$0"
	exit 1
	;;
esac
