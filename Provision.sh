#!/bin/sh

################################################################################
# APTITUDE MIRRORS
# ------------------------------------------------------------------------------
# Make it use closest mirrors
################################################################################

sudo mv /etc/apt/sources.list /etc/apt/sources.list.bak

echo 'deb mirror://mirrors.ubuntu.com/mirrors.txt CODENAME main restricted universe multiverse' | sed -e "s/CODENAME/$(cat /etc/lsb-release | grep CODENAME | cut -d= -f2)/" | sudo tee -a /etc/apt/sources.list
echo 'deb mirror://mirrors.ubuntu.com/mirrors.txt CODENAME-updates main restricted universe multiverse' | sed -e "s/CODENAME/$(cat /etc/lsb-release | grep CODENAME | cut -d= -f2)/" | sudo tee -a /etc/apt/sources.list
echo 'deb mirror://mirrors.ubuntu.com/mirrors.txt CODENAME-backports main restricted universe multiverse' | sed -e "s/CODENAME/$(cat /etc/lsb-release | grep CODENAME | cut -d= -f2)/" | sudo tee -a /etc/apt/sources.list
echo 'deb mirror://mirrors.ubuntu.com/mirrors.txt CODENAME-security main restricted universe multiverse' | sed -e "s/CODENAME/$(cat /etc/lsb-release | grep CODENAME | cut -d= -f2)/" | sudo tee -a /etc/apt/sources.list

sudo apt-get update
sudo apt-get upgrade -y


################################################################################
# ElasticSearch
# ------------------------------------------------------------------------------
#
################################################################################

wget -O - http://packages.elasticsearch.org/GPG-KEY-elasticsearch | sudo apt-key add -
echo 'deb http://packages.elasticsearch.org/elasticsearch/1.1/debian stable main' | sudo tee -a /etc/apt/sources.list.d/elasticsearch.list
sudo apt-get update
sudo apt-get install openjdk-7-jre-headless elasticsearch -y
sudo update-rc.d elasticsearch defaults 95 10
sudo /etc/init.d/elasticsearch start


################################################################################
# MySQL
# ------------------------------------------------------------------------------
# root password is set to "root"
################################################################################

echo mysql-server mysql-server/root_password password root | sudo debconf-set-selections
echo mysql-server mysql-server/root_password_again password root | sudo debconf-set-selections
sudo apt-get install mysql-server mysql-client -y

# allow remote connections
sudo sed -i -e 's/127.0.0.1/0.0.0.0/' /etc/mysql/my.cnf
mysql -u root -proot -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY 'root'"
sudo service mysql restart

# sakila sample database
sudo apt-get install wget unzip -y
wget http://downloads.mysql.com/docs/sakila-db.zip
unzip sakila-db.zip
mysql -u root -proot < sakila-db/sakila-schema.sql
mysql -u root -proot < sakila-db/sakila-data.sql
rm -rf sakila-db*





################################################################################
# phpMyAdmin
# ------------------------------------------------------------------------------
# root password is set to "root"
################################################################################

echo 'phpmyadmin phpmyadmin/dbconfig-install boolean true' | sudo debconf-set-selections
echo 'phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2' | sudo debconf-set-selections
echo 'phpmyadmin phpmyadmin/app-password-confirm password root' | sudo debconf-set-selections
echo 'phpmyadmin phpmyadmin/mysql/admin-pass password root' | sudo debconf-set-selections
echo 'phpmyadmin phpmyadmin/password-confirm password root' | sudo debconf-set-selections
echo 'phpmyadmin phpmyadmin/setup-password password root' | sudo debconf-set-selections
echo 'phpmyadmin phpmyadmin/database-type select mysql' | sudo debconf-set-selections
echo 'phpmyadmin phpmyadmin/mysql/app-pass password root' | sudo debconf-set-selections
echo 'dbconfig-common dbconfig-common/mysql/app-pass password root' | sudo debconf-set-selections
echo 'dbconfig-common dbconfig-common/mysql/app-pass password' | sudo debconf-set-selections
echo 'dbconfig-common dbconfig-common/password-confirm password root' | sudo debconf-set-selections
echo 'dbconfig-common dbconfig-common/app-password-confirm password root' | sudo debconf-set-selections
echo 'dbconfig-common dbconfig-common/app-password-confirm password root' | sudo debconf-set-selections
echo 'dbconfig-common dbconfig-common/password-confirm password root' | sudo debconf-set-selections
sudo apt-get install phpmyadmin -y





################################################################################
# php
# ------------------------------------------------------------------------------
#
################################################################################

# Display errors
sudo apt-get install php5-curl -y
sudo sed -i -e 's/display_errors = Off/display_errors = On/' /etc/php5/apache2/php.ini
sudo sed -i -e 's/display_startup_errors = Off/display_startup_errors = On/' /etc/php5/apache2/php.ini
sudo sed -i -e 's/display_errors = Off/display_errors = On/' /etc/php5/cli/php.ini
sudo sed -i -e 's/display_startup_errors = Off/display_startup_errors = On/' /etc/php5/cli/php.ini
sudo service apache2 restart

# Enable some modules (from html 5 boilerplate .htaccess)
# cat /var/www/.htaccess | grep "<IfModule" | grep -v "#" | sed -e "s/\s*<IfModule mod_/sudo a2enmod /" | sed -e "s/.c>//" | sort | uniq | grep -v autoindex
sudo a2enmod deflate
sudo a2enmod expires
sudo a2enmod filter
sudo a2enmod headers
sudo a2enmod mime
sudo a2enmod rewrite
sudo a2enmod setenvif

# Add www-data to vagrant user
sudo usermod -a -G vagrant www-data

# Remove default www files
sudo rm -rf /var/www/html

# Symlink our folder to www
sudo ln -fs /vagrant/www /var/www/html

sudo service apache2 restart

# install composer - php package manager
sudo apt-get install git -y
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer


cd /vagrant
composer update
php /vagrant/setup.php
