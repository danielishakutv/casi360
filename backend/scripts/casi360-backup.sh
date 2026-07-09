#!/usr/bin/env bash
#
# Nightly off-site backup of the CASI360 database.
#
#   /home/api.casi360.com/scripts/casi360-backup.sh
#
# Dumps the database named in the app's .env, gzips it, encrypts it with a
# passphrase, and copies the result to an rclone remote (Google Drive).
#
# WHY ENCRYPT: the dump contains password hashes, staff emails, salaries and
# the full approval trail. A compromised Google account must not equal a
# compromised HR database. The passphrase lives in a root-only file on this
# box AND in your password manager — if you lose it the backups are
# unrecoverable, and no one can help you.
#
# WHY IT NEVER DELETES: pruning old backups is the one operation that can
# destroy the thing you built this for. RETAIN_DAYS is 0 (never prune) unless
# you deliberately set it. Read the PRUNING section before you change that.
#
# The app stores documents as references, not files — storage/app is empty —
# so there is nothing but the database to capture. If a file-upload feature
# is ever added, this script must grow a tar of storage/app.
#
# Exit codes: 0 ok, non-zero means the backup did NOT happen. Cron mails you
# stderr, and every run appends to $LOG.

set -euo pipefail

# ---------------------------------------------------------------- config ---
APP_DIR="${APP_DIR:-/home/api.casi360.com}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/casi360}"
PASSPHRASE_FILE="${PASSPHRASE_FILE:-/root/.casi360-backup-passphrase}"
LOG="${LOG:-/var/log/casi360-backup.log}"

# rclone destination, e.g. "gdrive:casi360-backups". Empty = keep local only.
RCLONE_REMOTE="${RCLONE_REMOTE:-}"

# Days of backups to keep. 0 = NEVER DELETE ANYTHING (the default).
# See PRUNING at the bottom before changing this.
RETAIN_DAYS="${RETAIN_DAYS:-0}"

# ---------------------------------------------------------------- helpers --
log() { printf '%s  %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" | tee -a "$LOG"; }
die() { log "ERROR: $*"; exit 1; }

# Read one KEY=value out of the Laravel .env, tolerating quotes and CRLF.
# Returns non-zero only when the key is ABSENT — a key set to an empty value
# returns 0 with an empty string, which is a legitimate (if unwise) password.
#
# The emptiness test is on the raw matched line, not on grep's exit status:
# `$(grep … | head -n1)` yields head's status, which is always 0. That made an
# earlier version report success for missing keys unless `set -o pipefail`
# happened to be in effect. Don't make a correctness check depend on a shell
# option set two hundred lines away.
env_get() {
    local key="$1" line
    line=$(grep -E "^${key}=" "$APP_DIR/.env" | head -n1) || true
    [ -n "$line" ] || return 1
    line="${line#*=}"
    line="${line%$'\r'}"                       # strip a stray CR
    line="${line%\"}"; line="${line#\"}"       # strip double quotes
    line="${line%\'}"; line="${line#\'}"       # strip single quotes
    printf '%s' "$line"
}

# ------------------------------------------------------------- preflight ---
[ -f "$APP_DIR/.env" ]        || die "no .env at $APP_DIR/.env"
[ -f "$PASSPHRASE_FILE" ]     || die "no passphrase file at $PASSPHRASE_FILE (see the header)"
[ -s "$PASSPHRASE_FILE" ]     || die "passphrase file $PASSPHRASE_FILE is empty"
command -v mysqldump >/dev/null || die "mysqldump not found"
command -v gpg >/dev/null       || die "gpg not found"

DB_HOST=$(env_get DB_HOST || echo 127.0.0.1)
DB_PORT=$(env_get DB_PORT || echo 3306)
DB_NAME=$(env_get DB_DATABASE) || die "DB_DATABASE missing from .env"
DB_USER=$(env_get DB_USERNAME) || die "DB_USERNAME missing from .env"
DB_PASS=$(env_get DB_PASSWORD) || die "DB_PASSWORD missing from .env"

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

stamp=$(date +%Y%m%d-%H%M%S)
target="$BACKUP_DIR/casi360-${DB_NAME}-${stamp}.sql.gz.gpg"

# Credentials go in a 0600 defaults-file, never on the command line — argv is
# world-readable via ps, so `mysqldump -p"$DB_PASS"` would leak the password
# to every user on the box for the life of the dump.
cnf=$(mktemp); chmod 600 "$cnf"
cleanup() { rm -f "$cnf"; }   # only ever removes this run's own temp file
trap cleanup EXIT
cat >"$cnf" <<EOF
[client]
host="$DB_HOST"
port=$DB_PORT
user="$DB_USER"
password="$DB_PASS"
EOF

# MariaDB's mysqldump has no --no-tablespaces; MySQL 8 needs it unless the
# dump user holds the PROCESS privilege, which an app user should not.
dump_opts=(--single-transaction --quick --default-character-set=utf8mb4)
if ! mysqldump --version | grep -qi mariadb; then
    dump_opts+=(--no-tablespaces)
fi

# ----------------------------------------------------------------- backup ---
log "starting backup of '$DB_NAME' -> $(basename "$target")"

# --pinentry-mode loopback is not optional: GnuPG 2.x otherwise ignores
# --passphrase-file for symmetric encryption and tries to open an interactive
# pinentry, which under cron has no terminal. The backup would fail nightly.
GPG_OPTS=(--batch --yes --pinentry-mode loopback --passphrase-file "$PASSPHRASE_FILE")

# Every stage of this pipe must succeed. Without pipefail (set above) a failed
# mysqldump would still produce a cheerful, encrypted, EMPTY archive.
mysqldump --defaults-extra-file="$cnf" "${dump_opts[@]}" "$DB_NAME" \
    | gzip -9 \
    | gpg "${GPG_OPTS[@]}" --symmetric --cipher-algo AES256 --output "$target" \
    || die "dump/encrypt pipeline failed — no backup was written"

chmod 600 "$target"
size=$(du -h "$target" | cut -f1)

# --------------------------------------------------------------- verify -----
# An unverified backup is a rumour. Decrypt it, decompress it, and confirm it
# actually contains schema — catching silent truncation and a wrong passphrase
# now, rather than during an emergency restore.
log "verifying archive"
if ! gpg "${GPG_OPTS[@]}" --quiet --decrypt "$target" 2>/dev/null | gunzip -t; then
    die "archive failed to decrypt or decompress: $target"
fi

tables=$(gpg "${GPG_OPTS[@]}" --quiet --decrypt "$target" 2>/dev/null \
         | gunzip -c | grep -c '^CREATE TABLE' || true)
tables=${tables:-0}
[ "$tables" -gt 0 ] || die "archive decrypts but contains no CREATE TABLE: $target"
log "verified: $size, $tables tables"

# ------------------------------------------------------------- off-site -----
if [ -n "$RCLONE_REMOTE" ]; then
    command -v rclone >/dev/null || die "RCLONE_REMOTE set but rclone not installed"
    log "uploading to $RCLONE_REMOTE"
    rclone copy "$target" "$RCLONE_REMOTE" --no-traverse \
        || die "rclone upload failed (the local copy at $target is intact)"

    # Confirm it actually landed, with the byte count we expect. rclone exits 0
    # on some partial conditions; trust the remote listing, not the exit code.
    # `rclone size remote:dir/file` would treat the file as a directory and
    # report 0 — list the directory and match the filename instead.
    want=$(stat -c %s "$target")
    got=$(rclone lsl "$RCLONE_REMOTE" 2>/dev/null \
          | awk -v f="$(basename "$target")" '$NF == f { print $1; exit }')
    got=${got:-0}
    [ "$got" = "$want" ] || die "remote copy is $got bytes, expected $want — local copy at $target is intact"
    log "off-site copy confirmed ($want bytes)"
else
    log "RCLONE_REMOTE unset — local copy only, this is NOT yet off-site"
fi

log "done: $target"

# ---------------------------------------------------------------- PRUNING ---
# Deletion is opt-in and off by default. Turn it on only once you have (1) done
# a real restore from one of these archives, and (2) confirmed the off-site
# copies are landing. Until then, disk is cheaper than regret: the whole
# database is under 77 MB and the server has 339 GB free.
#
# When you do enable it, RETAIN_DAYS applies to the LOCAL copies only. Remote
# retention is deliberately left to you — a bug here would delete the only
# copies that survive losing this machine.
if [ "$RETAIN_DAYS" -gt 0 ]; then
    log "pruning local backups older than $RETAIN_DAYS days"
    find "$BACKUP_DIR" -name 'casi360-*.sql.gz.gpg' -type f -mtime "+$RETAIN_DAYS" \
        -print -delete | while read -r f; do log "  pruned $f"; done
fi
