#crontab
# daily crawler and notfound
# 0 0 * * * sh /var/www/vhosts/crawlers/daily.sh


# EROWZ
echo "EROWZ"
cd /var/www/vhosts/erowz.com/httpdocs/scripts/crawler/
/opt/plesk/php/8.3/bin/php pageAccessor.php com > /var/www/vhosts/crawlers/logs/ER_crawler.log



# SEARCH LOG FOR ALL COUNTRIES
echo "SEARCH LOG FOR ALL COUNTRIES"
cd /var/www/vhosts/for-sale.ie/
bash searchlog.sh

# update Blog articles
echo "update Blog"
cd /var/www/vhosts/used.forsale/httpdocs/scripts/
/opt/plesk/php/8.3/bin/php updateBlog.php forsale used.forsale
cd /var/www/vhosts/site-annonce.fr/httpdocs/scripts/
/opt/plesk/php/8.3/bin/php updateBlog.php FR site-annonce.fr
cd /var/www/vhosts/site-annonce.be/httpdocs/scripts/
/opt/plesk/php/8.3/bin/php updateBlog.php BE site-annonce.be
cd /var/www/vhosts/in-vendita.it/httpdocs/scripts/
/opt/plesk/php/8.3/bin/php updateBlog.php IT in-vendita.it
cd /var/www/vhosts/for-sale.co.uk/httpdocs/scripts/
/opt/plesk/php/8.3/bin/php updateBlog.php UK for-sale.co.uk
cd /var/www/vhosts/for-sale.ie/httpdocs/scripts/
/opt/plesk/php/8.3/bin/php updateBlog.php IE for-sale.ie
cd /var/www/vhosts/gebraucht-kaufen.de/httpdocs/scripts/
/opt/plesk/php/8.3/bin/php updateBlog.php DE gebraucht-kaufen.de