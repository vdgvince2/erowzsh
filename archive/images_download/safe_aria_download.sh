#!/usr/bin/env bash
set -euo pipefail

# ====== CONFIG ======
RUN="/var/www/US/run"
OUT="/var/www/US/images"
INPUT="images.txt"
BATCH_URLS=50000

# Concurrence raisonnable (ajuste selon ta machine / réseau)
J=48    # téléchargements simultanés
X=12    # connexions max par fichier
S=12    # segments par fichier

RETRY_WAIT=5
MAX_TRIES=12

# ====== PREP ======
mkdir -p "$RUN"/{batches,logs,sessions,tmp} "$OUT"
ulimit -n 65536 || true

# ====== Générateur Python : URLs -> fichier aria2 (URL + \tout=...) ======
PYGEN="$RUN/tmp/build_with_out.py"
cat > "$PYGEN" <<'PY'
#!/usr/bin/env python3
import sys, re, urllib.parse, hashlib, os

inp  = sys.argv[1]
outp = sys.argv[2]

def san(s: str) -> str:
    return re.sub(r'[^A-Za-z0-9._-]+', '', s)

with open(inp, 'r', encoding='utf-8', errors='ignore') as fin, \
     open(outp, 'w', encoding='utf-8') as fout:
    for line in fin:
        url = line.strip()
        if not url:
            continue
        url_noq = url.split('?', 1)[0]
        parts = url_noq.split('/')
        if len(parts) < 2:
            continue
        fname = san(urllib.parse.unquote(parts[-1] or "file"))
        gid   = san(urllib.parse.unquote(parts[-2]))
        if not gid:
            gid = hashlib.sha1(url.encode()).hexdigest()[:16]
        # Structure recommandée: sous-dossier par ID
        outname = f"{gid}/{fname}"
        fout.write(url + "\n")
        fout.write("\tout=" + outname + "\n")
PY
chmod +x "$PYGEN"

# ====== Split d'entrée en lots + génération with_out par lot (idempotent) ======
if [ ! -d "$RUN/batches/raw" ]; then
  mkdir -p "$RUN/batches/raw"
  # Pas de dédup globale (pour rester léger en RAM). On filtre juste lignes vides.
  awk 'NF' "$INPUT" | split -l "$BATCH_URLS" -d - "$RUN/batches/raw/urls_"
fi

# Génère les fichiers aria2 "with_out_*" s'ils n'existent pas encore
for raw in "$RUN"/batches/raw/urls_*; do
  [ -f "$raw" ] || continue
  base=$(basename "$raw")
  outp="$RUN/batches/with_out_${base}.txt"
  if [ ! -f "$outp" ]; then
    "$PYGEN" "$raw" "$outp"
  fi
done

# ====== Fonction d’exécution d’un lot ======
run_batch() {
  local in_file="$1"                               # RUN/batches/with_out_urls_XX.txt
  local base="$(basename "$in_file")"              # with_out_urls_XX.txt
  local log="$RUN/logs/${base}.log"
  local sess="$RUN/sessions/${base}.session"

  # Si une session existe ET est vide => lot déjà terminé
  if [ -e "$sess" ] && [ ! -s "$sess" ]; then
    echo ">>> Skip $base (déjà terminé)"
    return 0
  fi

  echo ">>> Téléchargement: $base"

  # Choix de la source d'entrée:
  # - si session non vide -> reprendre depuis la session
  # - sinon -> lire le fichier with_out (URL + out=)
  if [ -s "$sess" ]; then
    aria2c --input-file="$sess" \
      -d "$OUT" \
      -j "$J" -x "$X" -s "$S" \
      --continue=true \
      --always-resume=false --max-resume-failure-tries=3 \
      --allow-overwrite=true --auto-file-renaming=false \
      --file-allocation=none \
      --http-accept-gzip=false \
      --header="Accept-Encoding: identity" --header="Accept: image/*" \
      --conditional-get=true \
      --timeout=20 --connect-timeout=10 \
      --retry-wait="$RETRY_WAIT" --max-tries="$MAX_TRIES" \
      --summary-interval=60 \
      --console-log-level=warn \
      --log="$log" \
      --save-session="$sess" --save-session-interval=60 \
      --stop-with-process=$$
  else
    aria2c -i "$in_file" \
      -d "$OUT" \
      -j "$J" -x "$X" -s "$S" \
      --continue=true \
      --always-resume=false --max-resume-failure-tries=3 \
      --allow-overwrite=true --auto-file-renaming=false \
      --file-allocation=none \
      --http-accept-gzip=false \
      --header="Accept-Encoding: identity" --header="Accept: image/*" \
      --conditional-get=true \
      --timeout=20 --connect-timeout=10 \
      --retry-wait="$RETRY_WAIT" --max-tries="$MAX_TRIES" \
      --summary-interval=60 \
      --console-log-level=warn \
      --log="$log" \
      --save-session="$sess" --save-session-interval=60 \
      --stop-with-process=$$
  fi

  # Si la session est vide après exécution -> lot terminé
  if [ ! -s "$sess" ]; then
    echo ">>> Lot terminé: $base"
  else
    echo ">>> Lot incomplet (reste en session): $base"
  fi
}

# ====== Boucle sur lots (idempotente) ======
PARTS=( "$RUN"/batches/with_out_urls_* )
TOTAL=${#PARTS[@]}
idx=0
for p in "${PARTS[@]}"; do
  [ -f "$p" ] || continue
  idx=$((idx+1))
  echo "=============================="
  echo "Lot $idx / $TOTAL: $(basename "$p")"
  echo "=============================="
  run_batch "$p"
done

# ====== Compteur rapide ======
DONE=$(find "$OUT" -type f ! -name '*.aria2' | wc -l | tr -d ' ')
# Total prévu ~ somme des lignes / 2 (URL + out=)
TOTAL_EST=0
for p in "${PARTS[@]}"; do
  [ -f "$p" ] || continue
  TOTAL_EST=$(( TOTAL_EST + $(($(wc -l < "$p") / 2)) ))
done
echo ">>> Téléchargées: $DONE / ~${TOTAL_EST}"
echo ">>> Fini."
