#!/usr/bin/env bash
# deploy.sh - Synchronise un code source PHP vers plusieurs vhosts Plesk
# Exclusions:
#  - data/ (et tout son contenu)
#  - tenants/ (et tout son contenu)
#  - inc/config.php
#
# Usage:
#   bash deploy.sh               # déploiement réel
#   bash deploy.sh --dry-run     # simulation (aucune écriture)
#
# Pré-requis: rsync, stat

set -euo pipefail

# --------- CONFIG ---------
SRC="/var/www/vhosts/for-sale.ie/httpdocs"

TARGETS=(
  "/var/www/vhosts/site-annonce.fr/httpdocs"
  "/var/www/vhosts/site-annonce.be/httpdocs"
  "/var/www/vhosts/for-sale.co.uk/httpdocs"
  "/var/www/vhosts/used.forsale/httpdocs"
  "/var/www/vhosts/gebraucht-kaufen.de/httpdocs"
  "/var/www/vhosts/in-vendita.it/httpdocs"
  "/var/www/vhosts/erowz.com/httpdocs"
)

# Exclusions (relatifs à la racine du SRC)
EXCLUDES=(
  "data/"
  "tenants/"
  ".htaccess"
  "mag/"
  # "inc/config.php"
  # Tu peux ajouter si besoin :
  # ".git/"
  # "node_modules/"
)
# --------------------------

DRY_RUN=0
if [[ "${1:-}" == "--dry-run" ]]; then
  DRY_RUN=1
fi

if ! command -v rsync >/dev/null 2>&1; then
  echo "Erreur: rsync n'est pas installé." >&2
  exit 1
fi

if [[ ! -d "$SRC" ]]; then
  echo "Erreur: dossier source introuvable: $SRC" >&2
  exit 1
fi

# On veut synchroniser le CONTENU du SRC, pas créer un sous-dossier.
SRC_TRAIL="${SRC%/}/"

RSYNC_BASE_OPTS=(
  -a              # archive: perms, times, symlinks, etc.
  --delete-after  # supprime les fichiers obsolètes après transfert
  --partial       # reprise possible si coupure
  --inplace       # réduit les pics d'espace disque
  --omit-dir-times
  --human-readable
  --checksum      # robustesse si timestamps non fiables
)

# Ajoute les exclusions
for pat in "${EXCLUDES[@]}"; do
  RSYNC_BASE_OPTS+=( --exclude="$pat" )
done

# Dry-run ?
if [[ $DRY_RUN -eq 1 ]]; then
  RSYNC_BASE_OPTS+=( -n -v )
  echo ">>> MODE SIMULATION -- aucun fichier ne sera écrit."
fi

echo "Source: $SRC_TRAIL"
echo "Début du déploiement ($(date))"
echo

for TGT in "${TARGETS[@]}"; do
  echo "-----"
  echo "Cible: $TGT"

  if [[ ! -d "$TGT" ]]; then
    echo "  -> Création du dossier cible (mkdir -p) ..."
    mkdir -p "$TGT"
  fi

  # Détecte user:group du dossier cible (propre à chaque abonnement Plesk)
  TGT_USER="$(stat -c '%U' "$TGT" 2>/dev/null || stat -f '%Su' "$TGT")"
  TGT_GROUP="$(stat -c '%G' "$TGT" 2>/dev/null || stat -f '%Sg' "$TGT")"

  if [[ -z "${TGT_USER:-}" || -z "${TGT_GROUP:-}" ]]; then
    echo "  !! Impossible de détecter user:group pour $TGT. Abandon de cette cible."
    continue
  fi

  echo "  -> Propriétaire cible détecté: ${TGT_USER}:${TGT_GROUP}"

  # Exécute rsync avec --chown pour que les fichiers appartiennent au bon user Plesk
  rsync \
    "${RSYNC_BASE_OPTS[@]}" \
    --chown="${TGT_USER}:${TGT_GROUP}" \
    "${SRC_TRAIL}" \
    "${TGT%/}/"

  echo "  -> OK"
done

echo
echo "Terminé ($(date))"
