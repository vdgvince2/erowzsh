#!/usr/bin/env bash
set -euo pipefail

# --- config ---
MYSQL_HOST="localhost"
MYSQL_PORT="3306"
MYSQL_PASS="DB8Fv?bbyul65g!w"

# db list (username == db name)
DBS=(FR EROWZ IE UK DE BE IT US)

SQL=$(cat <<'EOF'
update `keywords` set active= 1 where active=0;
update `subdomain_keywords` set active= 1 where active=0;
EOF
)

echo "== EXECUTE MYSQL QUERIES FOR ALL DATABASES =="
echo "== mysql maintenance =="

for DB in "${DBS[@]}"; do
  USER="$DB"

  echo "-> [$DB] running..."
  mysql \
  --host="$MYSQL_HOST" \
  --port="$MYSQL_PORT" \
  --user="$USER" \
  --password="$MYSQL_PASS" \
  --database="$DB" \
  --protocol=tcp \
  --batch --silent \
  -e "$SQL" || {
    echo "⚠️ [$DB] error, continuing..."
  }

  echo "✅ [$DB] success"
done

echo "✅ all databases done"
