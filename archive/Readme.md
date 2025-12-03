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


# déployer en production
1. tout déployer via FTP, sauf : data, scripts, tenants, htaccess
2. lancer le script "bash /var/www/vhosts/for-sale.ie/deploy.sh"
3. update SQL si nécessaire
4. update htaccess si nécessaire