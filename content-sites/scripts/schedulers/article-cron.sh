#!/bin/bash
#
# article-cron.sh — Génère et publie un article par pays à chaque déclenchement.
#
# Crontab suggéré (1 article par pays toutes les 4h) :
#  PROD : bash /httpdocs/content-sites/scripts/schedulers/article-cron.sh
#   0 */4 * * * /bin/bash /path/to/content-sites/scripts/schedulers/article-cron.sh >> /path/to/logs/article-cron.log 2>&1
#
# Chaque run génère exactement 1 article par pays configuré.
# La sélection de la sous-niche suivante est gérée par cs_next_pending_subniche().

PHP_BIN="/usr/bin/php"
SCRIPT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
LOG_DATE=$(date '+%Y-%m-%d %H:%M:%S')

echo "========================================"
echo "[article-cron] ${LOG_DATE}"
echo "========================================"

# en pause pour l'instant : "FR" "DE" "IT" "BE" "IE"
COUNTRIES=("GB")

for CC in "${COUNTRIES[@]}"; do
    echo ""
    echo "--- Pays: ${CC} ---"

    # Génération
    /opt/plesk/php/8.3/bin/php "${SCRIPT_DIR}/scripts/generate-article.php" "${CC}"
    GEN_STATUS=$?

    if [ $GEN_STATUS -eq 0 ]; then
        # Publication immédiate après génération réussie
        /opt/plesk/php/8.3/bin/php "${SCRIPT_DIR}/scripts/publish-article.php" "${CC}"
    else
        echo "[article-cron] WARN: Génération échouée pour ${CC}, publication ignorée."
    fi
done

echo ""
echo "[article-cron] Cycle terminé."
