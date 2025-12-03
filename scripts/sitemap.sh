#!/usr/bin/env bash
set -euo pipefail

for CC in BE FR IT DE IE US; do
  echo "==> Processing $CC"
  /Applications/MAMP/bin/php/php8.2.0/bin/php sitemap_generator.php --country="$CC" || {
    echo "Error on $CC" >&2
    exit 1
  }
done

echo "All done."