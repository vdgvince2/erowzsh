# load all data on local
# 1. Create the local DB + local user
# CREATE USER 'IE'@'localhost' IDENTIFIED WITH mysql_native_password AS '***';GRANT USAGE ON *.* TO 'IE'@'localhost' REQUIRE NONE WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;CREATE DATABASE IF NOT EXISTS `IE`;GRANT ALL PRIVILEGES ON `IE`.* TO 'IE'@'localhost';
# 2. Change Country here below + change in the config.php file
# 3. run the script sh run.py 
country="FR"
echo "*** load mysql local data Export rom Mongo"
/Applications/MAMP/Library/bin/mysql -u "$country" -ptest "$country" < "/Users/vincentvandegans/Downloads/${country}_dump.sql"
echo "*** update categories SQL schema"
/Applications/MAMP/Library/bin/mysql -u "$country" -ptest "$country" < /Applications/MAMP/htdocs/SH/data/categories.sql
echo "*** update keywords SQL schema"
/Applications/MAMP/Library/bin/mysql -u "$country" -ptest "$country" < /Applications/MAMP/htdocs/SH/data/keywords.sql

# Run additionnal PHP scripts
echo "*** run php scripts"
# define the keyword URL slug and the keyword for homepage
/Applications/MAMP/bin/php/php8.2.0/bin/php keywordurl.php
# split the category path in 3 category levels in the ADS table
/Applications/MAMP/bin/php/php8.2.0/bin/php set_categories_levels.php
# create the categories in the CATEGORIES table
/Applications/MAMP/bin/php/php8.2.0/bin/php categoriesurl.php
# update the KEYWORDS table with the main category
/Applications/MAMP/bin/php/php8.2.0/bin/php set_main_category.php
# full export
echo "*** export du fichier mysql complet pour la production"
/Applications/MAMP/Library/bin/mysqldump "$country" -u "$country" -ptest > "/Applications/MAMP/htdocs/SH/data/${country}_dump.sql"