#crontab
# daily crawler and notfound
# 0 0 * * * /var/www/vhosts/crawlers/daily.sh


# EROWZ
echo "EROWZ"
cd /var/www/vhosts/erowz.com/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php EROWZ > /var/www/vhosts/crawlers/logs/ER_crawler.log
# pas de notfound en raison des images CDN/STATIC qui génère un keyword.
#/opt/plesk/php/8.3/bin/php notfound.php EROWZ > /var/www/vhosts/crawlers/logs/ER_notfound.log
