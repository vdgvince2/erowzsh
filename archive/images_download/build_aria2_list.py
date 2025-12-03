#!/usr/bin/env python3
import re, hashlib, urllib.parse

inp = "/Applications/MAMP/htdocs/SH/data/images/US/images.txt"
outp = "/Applications/MAMP/htdocs/SH/data/images/US/images_with_out.txt"

def sanitize(s: str) -> str:
    # garde lettres/chiffres/._- uniquement (compatible macOS)
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
        fname = sanitize(urllib.parse.unquote(parts[-1] or "file"))
        gid   = sanitize(urllib.parse.unquote(parts[-2]))
        if not gid:
            gid = hashlib.sha1(url.encode()).hexdigest()[:16]

        # === Nom de sortie ===
        # 1) TON CHOIX ACTUEL : concaténer l'ID et le nom (ex: --0AAeSw...s-l225.jpg)
        outname = f"{gid}{fname}"
        # 2) (Option conseillé FS) : sous-dossier par ID -> décommente si tu préfères
        # outname = f"{gid}/{fname}"

        fout.write(url + "\n")
        fout.write("\tout=" + outname + "\n")

print("OK ->", outp)
