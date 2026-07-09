# Restoring the CASI360 database

An unverified backup is a rumour. Do the drill in §3 **once now**, while nothing
is on fire, and again after any change to `casi360-backup.sh`.

Backups are written by [`casi360-backup.sh`](casi360-backup.sh) as
`casi360-<db>-<YYYYmmdd-HHMMSS>.sql.gz.gpg` — gzipped SQL, symmetrically
encrypted with AES256.

## 0. What you need

| | |
|---|---|
| The archive | `/var/backups/casi360/` on the server, or the `rclone` remote |
| The passphrase | `/root/.casi360-backup-passphrase` **and your password manager** |

If the server is gone, the passphrase file is gone with it. **The copy in your
password manager is the one that matters.** Without it the archives are
mathematically unrecoverable — not "hard to recover", unrecoverable.

## 1. Fetch an archive

Local:

```bash
ls -lt /var/backups/casi360/ | head
```

From Google Drive:

```bash
rclone ls gdrive:casi360-backups | sort -k2 | tail -5
rclone copy gdrive:casi360-backups/casi360-api_casi360_com-20260710-021500.sql.gz.gpg /tmp/
```

## 2. Decrypt and inspect before touching any database

```bash
cd /tmp
gpg --batch --pinentry-mode loopback \
    --passphrase-file /root/.casi360-backup-passphrase \
    --decrypt casi360-api_casi360_com-20260710-021500.sql.gz.gpg \
    | gunzip -c > restore.sql

grep -c '^CREATE TABLE' restore.sql     # sanity: expect dozens, not zero
head -n 5 restore.sql                   # expect a mysqldump banner
```

`--pinentry-mode loopback` is required on GnuPG 2.x — without it, `gpg` ignores
`--passphrase-file` and tries to prompt you interactively.

If you are restoring on a machine that does *not* have the passphrase file, drop
`--batch --pinentry-mode loopback --passphrase-file …` and `gpg` will simply ask
you for the passphrase. Get it from your password manager.

If `grep` returns 0, stop. The archive is not what you think it is.

## 3. The drill — restore into a scratch database

This is the whole point. It proves the archive is restorable **without touching
production**. Run it now, and after any change to the backup script.

```bash
mysql -e "CREATE DATABASE casi360_restore_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql casi360_restore_test < /tmp/restore.sql

# Does it look like the real thing?
mysql casi360_restore_test -e "SHOW TABLES;" | wc -l
mysql casi360_restore_test -e "SELECT COUNT(*) AS users FROM users;"
mysql casi360_restore_test -e "SELECT COUNT(*) AS requisitions FROM requisitions;"
```

Compare those counts against production:

```bash
mysql api_casi360_com -e "SELECT COUNT(*) AS users FROM users;"
```

They should match, allowing for rows written since the dump.

When you're satisfied, drop the scratch database — and **only** the scratch
database. Read the name twice before you press enter:

```bash
mysql -e "DROP DATABASE casi360_restore_test;"
```

## 4. Real restore into production

Only when production is actually broken. This **overwrites live data.**

```bash
# 1. Take a fresh dump of the current broken state first. You may need it,
#    and it costs 30 seconds. Never skip this.
mysqldump --single-transaction --quick api_casi360_com \
    | gzip > /root/pre-restore-$(date +%F-%H%M%S).sql.gz

# 2. Put the app in maintenance mode so nothing writes mid-restore.
cd /home/api.casi360.com && /usr/bin/php8.2 artisan down

# 3. Restore. mysql applies the dump's own DROP TABLE / CREATE TABLE per table.
mysql api_casi360_com < /tmp/restore.sql

# 4. Bring it back.
/usr/bin/php8.2 artisan optimize:clear
/usr/bin/php8.2 artisan up
```

Then log in and check something recent — an approval, a message — before
telling anyone it's fixed.

## 5. Tearing down

Remove the decrypted plaintext when you're done. It contains password hashes
and salaries:

```bash
shred -u /tmp/restore.sql
```

## Notes

- The dump is `--single-transaction`, so it's a consistent InnoDB snapshot
  taken without locking the app.
- There are **no file uploads** to restore. The app stores documents as
  references; `storage/app` is empty. If that ever changes, both this runbook
  and `casi360-backup.sh` must grow a `storage/app` tarball.
- `casi360-backup.sh` never deletes anything unless you set `RETAIN_DAYS`.
  Don't set it until this drill has passed at least once.
