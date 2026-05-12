# crontab every 2 hours
# daily crawler and notfound
# 0 */2 * * *   sh /var/www/vhosts/crawlers/bihourly.sh

# FR
echo "FR"
cd /var/www/vhosts/site-annonce.fr/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php FR > /var/www/vhosts/crawlers/logs/FR_crawler.log
/opt/plesk/php/8.3/bin/php pageAccessorSubDom.php FR > /var/www/vhosts/crawlers/logs/FR_subdom_crawler.log
#/opt/plesk/php/8.3/bin/php notfound.php FR > /var/www/vhosts/crawlers/logs/FR_notfound.log
#/opt/plesk/php/8.3/bin/php /var/www/vhosts/site-annonce.fr/httpdocs/scripts/ping_deals_all.php FR >> /var/www/vhosts/crawlers/logs/FR_deals_ping.log

# IE
echo "IE"
cd /var/www/vhosts/for-sale.ie/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php IE > /var/www/vhosts/crawlers/logs/IE_crawler.log
/opt/plesk/php/8.3/bin/php pageAccessorSubDom.php IE > /var/www/vhosts/crawlers/logs/IE_subdom_crawler.log
#/opt/plesk/php/8.3/bin/php notfound.php IE > /var/www/vhosts/crawlers/logs/IE_notfound.log
#/opt/plesk/php/8.3/bin/php /var/www/vhosts/for-sale.ie/httpdocs/scripts/ping_deals_all.php IE >> /var/www/vhosts/crawlers/logs/IE_deals_ping.log

# DE
echo "DE"
cd /var/www/vhosts/gebraucht-kaufen.de/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php DE > /var/www/vhosts/crawlers/logs/DE_crawler.log
/opt/plesk/php/8.3/bin/php pageAccessorSubDom.php DE > /var/www/vhosts/crawlers/logs/DE_subdom_crawler.log
#/opt/plesk/php/8.3/bin/php notfound.php DE > /var/www/vhosts/crawlers/logs/DE_notfound.log
#/opt/plesk/php/8.3/bin/php /var/www/vhosts/gebraucht-kaufen.de/httpdocs/scripts/ping_deals_all.php DE >> /var/www/vhosts/crawlers/logs/DE_deals_ping.log

# BE
echo "BE"
cd /var/www/vhosts/site-annonce.be/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php BE > /var/www/vhosts/crawlers/logs/BE_crawler.log
#/opt/plesk/php/8.3/bin/php pageAccessorSubDom.php IE > /var/www/vhosts/crawlers/logs/BE_subdom_crawler.log
#/opt/plesk/php/8.3/bin/php notfound.php BE > /var/www/vhosts/crawlers/logs/BE_notfound.log
#r/opt/plesk/php/8.3/bin/php /var/www/vhosts/site-annonce.be/httpdocs/scripts/ping_deals_all.php BE >> /var/www/vhosts/crawlers/logs/BE_deals_ping.log