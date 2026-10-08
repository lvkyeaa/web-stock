#!/bin/sh
set -e

# Go up two levels to get to the project root
BASEDIR="$(cd "$(dirname "$0")/../.." && pwd)"

# Load .env from project root
if [ -f "$BASEDIR/.env" ]; then
  set -a
  . "$BASEDIR/.env"
  set +a
else
  echo "❌ Missing .env file in $BASEDIR"
  exit 1
fi

# Set backup directory under project root (or change as needed)
BACKUP_DIR="$BASEDIR/backup"
DATE=$(date +%F)
mkdir -p "$BACKUP_DIR"

# Function to dump a single DB
dump_database() {
  DB_NAME="$1"
  DB_HOST="$2"
  DB_PORT="$3"
  DB_USER="$4"
  DB_PASS="$5"
  CUSTOM_NAME="$6"

  OUTFILE="$BACKUP_DIR/${CUSTOM_NAME}_${DATE}.sql"
  ZIPFILE="$BACKUP_DIR/${CUSTOM_NAME}_${DATE}.zip"

  echo "📦 Backing up $DB_NAME to $OUTFILE"
  export MYSQL_PWD="$DB_PASS"

  # 1. Dump all tables except the ones we want structure-only
  mysqldump --no-tablespaces \
    -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" \
    "$DB_NAME" \
    --ignore-table="$DB_NAME.pulse_aggregates" \
    --ignore-table="$DB_NAME.pulse_entries" \
    --ignore-table="$DB_NAME.pulse_values" \
    > "$OUTFILE"

  # 2. Append structure-only dump for the excluded tables
  mysqldump --no-tablespaces --no-data \
    -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" \
    "$DB_NAME" \
    pulse_aggregates pulse_entries pulse_values \
    >> "$OUTFILE"

  # 3. Zip the dump and delete the original .sql (only if zip succeeds)
  if command -v zip >/dev/null 2>&1; then
    echo "🗜️  Zipping $OUTFILE to $ZIPFILE"
    rm -f "$ZIPFILE"
    zip -9 -q -j "$ZIPFILE" "$OUTFILE"
    rm -f "$OUTFILE"
  else
    echo "❌ zip command not found; cannot compress backups"
    exit 1
  fi

  unset MYSQL_PWD
}

# Backup all 3 DBs with custom names
dump_database "$DB_MAIN_DATABASE" "$DB_MAIN_HOST" "$DB_MAIN_PORT" "$DB_MAIN_USERNAME" "$DB_MAIN_PASSWORD" "DB_MAIN"
dump_database "$DB_2_DATABASE" "$DB_2_HOST" "$DB_2_PORT" "$DB_2_USERNAME" "$DB_2_PASSWORD" "DB_2"
dump_database "$DB_3_DATABASE" "$DB_3_HOST" "$DB_3_PORT" "$DB_3_USERNAME" "$DB_3_PASSWORD" "DB_3"
