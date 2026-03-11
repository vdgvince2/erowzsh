#!/usr/bin/env bash
set -euo pipefail

DEST_DIR="/var/www/vhosts/crawlers/logs"
LOG_FILES=("ebay_browse_debug.log" "searches.log")

declare -A SOURCES=(
  [IE]="/var/www/vhosts/for-sale.ie/crawler-log"
  [FR]="/var/www/vhosts/site-annonce.fr/crawler-log"
  [BE]="/var/www/vhosts/site-annonce.be/crawler-log"
  [UK]="/var/www/vhosts/for-sale.co.uk/crawler-log"
  [US]="/var/www/vhosts/used.forsale/crawler-log"
  [DE]="/var/www/vhosts/gebraucht-kaufen.de/crawler-log"
  [IT]="/var/www/vhosts/in-vendita.it/crawler-log"
  [ER]="/var/www/vhosts/erowz.com/crawler-log"
)

mkdir -p "$DEST_DIR"

for CODE in "${!SOURCES[@]}"; do
  SRC_DIR="${SOURCES[$CODE]}"

  if [[ ! -d "$SRC_DIR" ]]; then
    echo "WARN: dossier introuvable: $SRC_DIR (code $CODE) — skip"
    continue
  fi

  for LF in "${LOG_FILES[@]}"; do
    SRC_FILE="$SRC_DIR/$LF"
    DEST_FILE="$DEST_DIR/${CODE}_${LF}"

    if [[ ! -f "$SRC_FILE" ]]; then
      echo "WARN: fichier introuvable: $SRC_FILE — skip"
      continue
    fi

    # Ajout en fin de fichier (sans écraser). Crée le fichier destination s'il n'existe pas.
    cat "$SRC_FILE" >> "$DEST_FILE"
    echo "OK: $SRC_FILE  ->  $DEST_FILE (+append)"
  done
done
