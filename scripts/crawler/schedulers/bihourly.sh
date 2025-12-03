# crontab every 2 hours
# daily crawler and notfound
# 0 */2 * * *   sh /var/www/vhosts/crawlers/bihourly.sh

# FR
echo "FR"
cd /var/www/vhosts/site-annonce.fr/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php FR > /var/www/vhosts/crawlers/logs/FR_crawler.log
/opt/plesk/php/8.3/bin/php notfound.php FR > /var/www/vhosts/crawlers/logs/FR_notfound.log


# IE
echo "IE"
cd /var/www/vhosts/for-sale.ie/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php IE > /var/www/vhosts/crawlers/logs/IE_crawler.log
/opt/plesk/php/8.3/bin/php notfound.php IE > /var/www/vhosts/crawlers/logs/IE_notfound.log

# DE
echo "DE"
cd /var/www/vhosts/gebraucht-kaufen.de/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php DE > /var/www/vhosts/crawlers/logs/DE_crawler.log
/opt/plesk/php/8.3/bin/php notfound.php DE > /var/www/vhosts/crawlers/logs/DE_notfound.log

# BE
echo "BE"
cd /var/www/vhosts/site-annonce.be/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php BE > /var/www/vhosts/crawlers/logs/BE_crawler.log
/opt/plesk/php/8.3/bin/php notfound.php BE > /var/www/vhosts/crawlers/logs/BE_notfound.log