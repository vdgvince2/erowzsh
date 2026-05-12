#crontab
# hourly crawler and notfound
# 1 * * * * /var/www/vhosts/crawlers/hourly.sh


# USA
echo "USA"
cd /var/www/vhosts/used.forsale/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php forsale > /var/www/vhosts/crawlers/logs/US_crawler.log
/opt/plesk/php/8.3/bin/php pageAccessorSubDom.php forsale > /var/www/vhosts/crawlers/logs/US_subdom_crawler.log
#/opt/plesk/php/8.3/bin/php deals_history_crawler.php forsale >> /var/www/vhosts/crawlers/logs/US_crawler.log
#/opt/plesk/php/8.3/bin/php notfound.php forsale > /var/www/vhosts/crawlers/logs/US_notfound.log

# UK
echo "UK"
cd /var/www/vhosts/for-sale.co.uk/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php UK > /var/www/vhosts/crawlers/logs/UK_crawler.log
/opt/plesk/php/8.3/bin/php pageAccessorSubDom.php UK > /var/www/vhosts/crawlers/logs/UK_subdom_crawler.log
#/opt/plesk/php/8.3/bin/php deals_history_crawler.php UK >> /var/www/vhosts/crawlers/logs/UK_crawler.log
#/opt/plesk/php/8.3/bin/php notfound.php UK > /var/www/vhosts/crawlers/logs/UK_notfound.log

# IE
echo "IE"
cd /var/www/vhosts/for-sale.ie/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php IE > /var/www/vhosts/crawlers/logs/IE_crawler.log
/opt/plesk/php/8.3/bin/php pageAccessorSubDom.php IE > /var/www/vhosts/crawlers/logs/IE_subdom_crawler.log
#/opt/plesk/php/8.3/bin/php deals_history_crawler.php IE >> /var/www/vhosts/crawlers/logs/IE_crawler.log

# FR
echo "FR"
cd /var/www/vhosts/site-annonce.fr/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php FR > /var/www/vhosts/crawlers/logs/FR_crawler.log
/opt/plesk/php/8.3/bin/php pageAccessorSubDom.php FR > /var/www/vhosts/crawlers/logs/FR_subdom_crawler.log
#/opt/plesk/php/8.3/bin/php deals_history_crawler.php FR >> /var/www/vhosts/crawlers/logs/FR_crawler.log

# DE
echo "DE"
cd /var/www/vhosts/gebraucht-kaufen.de/httpdocs/scripts/crawler/
#/opt/plesk/php/8.3/bin/php deals_history_crawler.php DE >> /var/www/vhosts/crawlers/logs/DE_crawler.log

# BE
echo "BE"
cd /var/www/vhosts/site-annonce.be/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php BE > /var/www/vhosts/crawlers/logs/BE_crawler.log
#/opt/plesk/php/8.3/bin/php deals_history_crawler.php BE >> /var/www/vhosts/crawlers/logs/BE_crawler.log

# IT
echo "IT"
cd /var/www/vhosts/in-vendita.it/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php IT > /var/www/vhosts/crawlers/logs/IT_crawler.log
/opt/plesk/php/8.3/bin/php pageAccessorSubDom.php IT > /var/www/vhosts/crawlers/logs/IT_subdom_crawler.log
#/opt/plesk/php/8.3/bin/php deals_history_crawler.php IT >> /var/www/vhosts/crawlers/logs/IT_crawler.log
#/opt/plesk/php/8.3/bin/php notfound.php IT > /var/www/vhosts/crawlers/logs/IT_notfound.log