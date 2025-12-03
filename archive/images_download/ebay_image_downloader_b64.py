#!/usr/bin/env python3
# (See previous cell for full docstring and details)
"""
Example : https://i.ebayimg.com/images/g/8KgAAeSwNyto7S7Q/s-l225.jpg


- Lit un fichier TXT contenant une URL d'image par ligne (défaut: data/US/images.txt).
- Télécharge chaque image (headers réalistes, retries, pause aléatoire).
- Construit le chemin local à partir du token entre /images/g/ et le nom de fichier,
  découpé en 3 parties (case-sensitive) pour créer 3 niveaux de dossiers.
- Écrit l'image sous: IMAGES_DIR/<part1>/<part2>/<part3>/<filename>
- Met à jour le fichier TXT *après chaque URL* (eager prune):
    - succès : la ligne est retirée du TXT
    - échec  : la ligne est conservée
- Zéro argument CLI. Personnalisation via variables en haut de fichier ou variables d'environnement.

Variables d'environnement supportées (optionnel) :
    INPUT_FILE  (defaut: data/US/images.txt)
    IMAGES_DIR  (defaut: images)

"""

import os
import sys
import re
import time
import random
import logging
from pathlib import Path
from urllib.parse import unquote
import requests
from requests.adapters import HTTPAdapter, Retry

# ------------------------ Config (modifiez ici si besoin) ---------------------
INPUT_FILE = os.environ.get("INPUT_FILE", "data/UK/images.txt")
IMAGES_DIR = os.environ.get("IMAGES_DIR", "images")
# désactivé plus bas.
MIN_DELAY = 1.0
MAX_DELAY = 1.2
TIMEOUT = 20
MAX_RETRIES = 2
LOG_LEVEL = logging.INFO  # ou logging.DEBUG

# User-Agents réalistes
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 13_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36",
]

# Regex pour extraire token + filename: /images/g/<TOKEN>/<FILENAME>
TOKEN_RE = re.compile(r"/images/g/([^/]+)/([^/?#]+)", re.IGNORECASE)

# -------------------------- Utilitaires --------------------------------------
def split_token_three_parts(token: str):
    """Découpe le token en 3 parties quasi égales."""
    n = len(token)
    if n == 0:
        return ("_", "_", "_")
    a = n // 3
    r = n % 3
    sizes = [a + (1 if i < r else 0) for i in range(3)]
    p1 = token[0:sizes[0]]
    p2 = token[sizes[0]:sizes[0]+sizes[1]]
    p3 = token[sizes[0]+sizes[1]:]
    return (p1 or "_", p2 or "_", p3 or "_")

def parse_url_for_token_and_filename(url: str):
    """Retourne (token, filename) à partir de l'URL eBay CDN."""
    m = TOKEN_RE.search(url)
    if not m:
        raise ValueError("URL ne correspond pas au motif attendu /images/g/<TOKEN>/<FILENAME>")
    token = m.group(1)
    filename = unquote(m.group(2))
    if not filename:
        raise ValueError("Nom de fichier absent dans l'URL")
    return token, filename

def create_session(max_retries=MAX_RETRIES) -> requests.Session:
    s = requests.Session()
    retries = Retry(
        total=max_retries,
        backoff_factor=0.5,
        status_forcelist=(429, 500, 502, 503, 504),
        allowed_methods=["HEAD", "GET", "OPTIONS"],
    )
    adapter = HTTPAdapter(max_retries=retries, pool_maxsize=10)
    s.mount("http://", adapter)
    s.mount("https://", adapter)
    return s

def download_one(session: requests.Session, url: str, target_path: Path, referer: str = None, timeout: int = TIMEOUT) -> bool:
    """Télécharge une image dans target_path (écriture atomique). Pas de HEAD, pas de vérif d'existence."""
    target_path.parent.mkdir(parents=True, exist_ok=True)
    tmp_path = target_path.with_suffix(target_path.suffix + ".part")

    headers = {
        "User-Agent": random.choice(USER_AGENTS),
        "Accept": "image/avif,image/webp,image/apng,image/*,*/*;q=0.8",
        "Accept-Language": "en-US,en;q=0.9",
        "Referer": referer or "https://www.ebay.com/",
    }

    try:
        with session.get(url, headers=headers, stream=True, timeout=timeout) as r:
            if r.status_code != 200:
                logging.warning("GET %s -> %s", url, r.status_code)
                return False

            with open(tmp_path, "wb") as fh:
                for chunk in r.iter_content(chunk_size=8192):
                    if chunk:
                        fh.write(chunk)

        # Remplace (écrase) atomiquement le fichier final, sans aucune vérification
        tmp_path.replace(target_path)
        logging.info("OK: %s", target_path)
        return True

    except Exception as e:
        logging.warning("Fail %s : %s", url, e)
        try:
            if tmp_path.exists():
                tmp_path.unlink()
        except Exception:
            pass
        return False

# ------------------------------ Programme ------------------------------------
def main():
    logging.basicConfig(stream=sys.stdout, level=LOG_LEVEL, format="%(asctime)s %(levelname)s: %(message)s")

    input_file = Path(INPUT_FILE)
    if not input_file.exists():
        logging.error("Fichier introuvable: %s", input_file)
        sys.exit(1)

    base_out = Path(IMAGES_DIR)
    base_out.mkdir(parents=True, exist_ok=True)

    # Lecture en mémoire des lignes pour pouvoir faire le eager prune proprement
    lines = input_file.read_text(encoding="utf-8").splitlines()

    session = create_session()
    total = 0
    succeeded = 0
    failed = 0

    for idx, raw in enumerate(lines):
        url = raw.strip()
        if not url:
            continue
        total += 1

        try:
            token, filename = parse_url_for_token_and_filename(url)
            p1, p2, p3 = split_token_three_parts(token)
            target_path = base_out.joinpath(p1, p2, p3, filename)
            ok = download_one(session, url, target_path)
        except Exception as e:
            logging.warning("Erreur traitement %s : %s", url, e)
            ok = False

        if ok:
            succeeded += 1
        else:
            failed += 1

        # --- EAGER PRUNE: mise à jour immédiate du TXT ---
        remainder = lines[idx+1:]
        new_lines = ([] if ok else [url]) + remainder
        tmp_path = input_file.with_suffix(input_file.suffix + ".part")
        tmp_path.write_text("\n".join(new_lines) + ("\n" if new_lines else ""), encoding="utf-8")
        tmp_path.replace(input_file)

        # Pause polie
        #delay = random.uniform(MIN_DELAY, MAX_DELAY)
        #logging.debug("Sleep %.2fs", delay)
        #time.sleep(delay)

    logging.info("Terminé. total=%d succès=%d échecs=%d", total, succeeded, failed)

if __name__ == "__main__":
    main()

