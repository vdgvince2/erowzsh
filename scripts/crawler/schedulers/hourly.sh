#crontab
# hourly crawler and notfound
# 1 * * * * /var/www/vhosts/crawlers/hourly.sh


# USA
echo "USA"
cd /var/www/vhosts/used.forsale/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php forsale > /var/www/vhosts/crawlers/logs/US_crawler.log
/opt/plesk/php/8.3/bin/php notfound.php forsale > /var/www/vhosts/crawlers/logs/US_notfound.log

# UK
echo "UK"
cd /var/www/vhosts/for-sale.co.uk/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php UK > /var/www/vhosts/crawlers/logs/UK_crawler.log
/opt/plesk/php/8.3/bin/php notfound.php UK > /var/www/vhosts/crawlers/logs/UK_notfound.log

# IT
echo "IT"
cd /var/www/vhosts/in-vendita.it/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php IT > /var/www/vhosts/crawlers/logs/IT_crawler.log
/opt/plesk/php/8.3/bin/php notfound.php IT > /var/www/vhosts/crawlers/logs/IT_notfound.log