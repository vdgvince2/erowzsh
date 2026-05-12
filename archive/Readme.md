# Plan d'importation des données d'un pays
1. se connecter sur bcdotnet@prod-mongo02-cl02, répertoire secret
2. modifier le country dans preparedb.sh et puis lancer sh preparedb.sh
3. Télécharger en SFTP via RoyalTSX
4. Charger les sources via Plesk + modifier htaccess 
5. créer le tenant xx.php et adapter le country code
6. créer la db dans phpmyadmin avec privilèges GRANT
7. Modifier le config.php local avec le default countryCode.
7. modifier run.sh avec le bon country code
8. lancer le script run.sh en local
9. configurer cloudflare et ajouter la règle de sécurité "BAN asia"

# update SQL pour tous les pays ensemble
1. il faut lancer bash bash /var/www/vhosts/for-sale.ie/scripts/crawler/schedulers/sqlupdate.sh

# déployer en production
0. compiler le CSS avec NPX
1. tout déployer via FTP, sauf : archive, data, tenants, htaccess, node_modules, monitoring
2. lancer le script "bash /var/www/vhosts/for-sale.ie/deploy.sh"
3. update SQL si nécessaire
4. update htaccess si nécessaire
5. déployer les crawlers dans /var/www/vhosts/crawlers/

# compilation Tailwind CSS
npx tailwindcss -i /Applications/MAMP/htdocs/SH/archive/input.css -o /Applications/MAMP/htdocs/SH/assets/tailwind.css --minify

# configure a subdomain
0. charger les mots-clés dans la DB via insertKeyword.php + lancer le crawler pageAccessorSubDom.php
1. in plesk, create the wildcard subdomain *.for-sale.ie
2. dans documentRoot du * , laisser httpdocs
3. configurer le cronTAB de pageAccessorSubDom.php
4. enlever redirect www dans htaccess

# insert keywords in main domain
0. create a file with INSERT INTO notfound(keywordname) VALUES ('american standard bath tub');
1. run /opt/plesk/php/8.3/bin/php notfound.php forsale

# how to find keywords for subdomain?
1. go to ahrefs and look for ebay or picclick
2. select DA < 10 + KD < 5 + "used"
3. or search with GPT : "donne moi 50 noms de marques de produits qui valent plus de 500€ neufs et qui peuvent être revendus d'occasion. ces marques doivent être connues et les objets doivent faire moins de 10kg. qui ne sont pas dans la fashion ni l'electronique. Sans détail, prend des marques internationnales qui vendent partout dans le monde."

# other method to find keywords
1. check classifieds ads website in ahrefs
    1.1 for Craigslist : newyork.craigslist.org/search/msa (msa/ata/ppa/bia/cba/sya/ela/fua/pha/maa/tla)
2. take the competitors
3. find the review websites that rank
4. copy the keywords and exclude bad terms : reviews, buy, etc.