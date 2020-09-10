
# https://laravel.com/docs/5.6#server-requirements

#
# Ubuntu 18.04
# Hint: Run 'export TERM=linux' to activate colors in an lxc-console session.

echo
echo "Don't run this script. It is documentation only. Read, edit, and review each command"
echo "Not all of the lines in this file are valid shell commands. Some of it is just text notes."
echo
exit 1


# ---------------------------------------------------
# 
# O.S. Basics
#
# Fresh Deployment on a new Ubuntu 18.04 server:
# First set up the LAMP environment and create the 'laravel' user
#
# ---------------------------------------------------

# First, install postfix silently. It defaults to using an interactive prompt.
# Choose either method: "Internet Site", or local delivery to /var/mail.

#
# This installs postfix silently by pre-answering the questions for "Internet Site".
#
#debconf-set-selections <<< "postfix postfix/mailname string your.hostname.com"
#debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"
#apt-get install -y postfix
$POSTFIX_HOSTNAME="localhost"
debconf-set-selections <<< "postfix postfix/mailname string $POSTFIX_HOSTNAME"
debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"
apt-get install -y postfix

#
# This configures the default localhost /var/mail setup.
#
#DEBIAN_FRONTEND=noninteractive apt-get install postfix

# real	11m54.709s
# real	3m53.723s
time sudo apt-get install -y \
 nano mysql-server mysql-client \
 apache2 libapache2-mod-php php curl wget mailutils \
 openssl ssl-cert ldap-utils \
 mcrypt zip unzip \
 bash-completion git ssh


# Laravel Requirements
# PHP >= 7.1.3
# OpenSSL PHP Extension  (verify built-in: php -i | grep -i openssl )
# PDO PHP Extension  (verify built-in: php -i | grep -i pdo )
# Mbstring PHP Extension
# Tokenizer PHP Extension  (verify built-in: php -i | grep -i token )
# XML PHP Extension
# Ctype PHP Extension  (verify built-in: php -i | grep -i ctype )
# JSON PHP Extension  (verify built-in: php -i | grep -i json )

# real	0m57.313s
time sudo apt-get -y install php php-cli php-xml php-gd php-opcache php-mbstring \
 php-mysql php-db php-pear php-curl php-ldap libphp-adodb php-db php-pear \
 php-curl php-imap php-ldap php-zip libgd-tools


#
# Note: Default user exists as ubuntu, with no password. It needs a new passwd
# to allow access via lxc-console, which is convenient for local development. 
#

# Enable the Laraval install as an SSL site.
#
# - Set DocumentRoot to /var/www/html/laravel/public
# - Add AllowOverride All to /var/www/html/laravel 
cd /etc/apache2/sites-available/
sudo cp -a default-ssl.conf laravel.conf

sudo sed -i "s|DocumentRoot |#DocumentRoot |" laravel.conf
sudo sed -i "s|</VirtualHost>|#</VirtualHost>|" laravel.conf
sudo sed -i "s|</IfModule>|#</IfModule>|" laravel.conf

echo "# " | sudo tee -a laravel.conf
echo "# Changes from default-ssl.conf for a Laravel installation:" | sudo tee -a laravel.conf
echo "# Added "`date +"%Y-%m-%d %T"` | sudo tee -a laravel.conf
echo "# " | sudo tee -a laravel.conf
echo "DocumentRoot /var/www/html/laravel/public" | sudo tee -a laravel.conf
echo "<Directory /var/www/html/laravel>
  AllowOverride All
</Directory>"  | sudo tee -a laravel.conf
echo "</VirtualHost>"  | sudo tee -a laravel.conf
echo "</IfModule>"  | sudo tee -a laravel.conf

# Enable the Laravel "site":

sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2dissite 000-default.conf
sudo a2ensite laravel.conf
sudo service apache2 restart


#
# Composer install to /usr/local/bin. Done via curl. 
# "Seems legit"
#
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Composer stores commands under $HOME, and complains when you use sudo.
# So we do the Laravel install as user "laravel", so that the Apache user
# www-data doesn't have write access to Laravel files.
sudo adduser --disabled-password --gecos "" laravel

# Create the install directory, writable by the new user laravel:
sudo chgrp laravel /var/www/html/
sudo chmod g+w /var/www/html/

#
#
# Become user laravel for the rest of the install:
#
#
sudo su - laravel

#
# Add this path so the new "laravel" (composer) command works.
# FIXME: 
# On first test, this went to /home/laravel/.composer/vendor/bin
# On second test, this went to /home/laravel/.config/composer
#export PATH=$PATH:~/.composer/vendor/bin
#echo 'export PATH=$PATH:~/.composer/vendor/bin' >> ~/.bashrc
# Which is correct? Why was it different?
export PATH=$PATH:~/.config/composer/vendor/bin
echo 'export PATH=$PATH:~/.config/composer/vendor/bin' >> ~/.bashrc

# Download the "laravel" command.
#
# I don't think this is necessary for a git-based deployment, but it is 
# necessary for a fresh install.
composer global require "laravel/installer"


#symfony/console suggests installing psr/log-implementation (For using the console logger)
#symfony/console suggests installing symfony/event-dispatcher ()
#symfony/console suggests installing symfony/lock ()
#guzzlehttp/guzzle suggests installing psr/log (Required for using the Log middleware)

# FIXME: I think these commands are unnecessary. Test and post updates here.
##composer require psr/log
##composer require symfony/event-dispatcher
##composer require symfony/lock
#composer require psr/log-implementation
# Error: Could not find a version of package psr/log-implementation matching your minimum-stability (stable).


#
#
# Git-based application deployment to a fresh server
#
#

#
# Become the laravel user for the git checkout, if not already:
#
##sudo su - laravel

cd /var/www/html/

# Load the ssh-agent, if necessary:
#eval `ssh-agent`

# use the correct file path here
ssh-add ~/.ssh/id_rsa_plai
# confirm the list has just this one key
ssh-add -l


# Finally, do a git clone:
REPO="plaitoday@vs-ssh.visualstudio.com:v3/plaitoday/platform1/platform1"
git clone $REPO laravel

# After installing Laravel, you may need to configure some permissions. 
# Directories within the  storage and the bootstrap/cache directories 
# should be writable by your web server (user www-data) or Laravel will not run.
#
# (This needs to be done as root.)
# Exit user session as laravel, so sudo works again:
exit

# As user root:
cd /var/www/html/laravel

# Create the log file, writable by www-data but owned by laravel.
sudo mkdir -p storage/logs/
sudo touch storage/logs/laravel.log
sudo chown laravel.www-data storage/logs/laravel.log
sudo chmod g+w ./storage/logs/laravel.log

sudo chmod -R g+w ./storage
sudo chgrp -R www-data ./storage

sudo chmod g+w ./bootstrap/cache
sudo chgrp www-data ./bootstrap/cache

# Make avatars writable by laravel and www-data:
sudo mkdir -p storage/app/public/avatars/
sudo chown laravel.www-data storage/app/public/avatars/
sudo chmod g+w storage/app/public/avatars/


# Create the symlink  public/storage -> storage/app/public/
# NOTE: This command makes an absolute path link, not a relative link. So
# we make a relative one instead.
#php artisan storage:link
cd public
ln -s ../storage/app/public storage
cd ..


#
# Set up the .env config.
# This replaces the unique security keys
#    DB_PASSWORD
#    JWT_SECRET
#    APP_KEY
cp -a .env.example .env


# Set a new, random database password:
DB_PASSWORD=$(head --bytes=16 /dev/urandom | base64)
# Using | as the delimiter because base64 has / in it sometimes
sed -i 's|^DB_PASSWORD=.*|DB_PASSWORD='"$DB_PASSWORD"'|' .env

#
# Create the MySQL database
#
#
# For more advanced setups (such as networked clusters or failover) this
# should be a managed token over a secure channel (SSH or similar), to mitigate
# network-based attack vectors.
#
mysql -uroot -e "create database homestead;"
mysql -uroot -e "GRANT ALL PRIVILEGES ON homestead.* to homestead@localhost identified by '$DB_PASSWORD';"

/etc/init.d/mysql restart


# Update the config as user laravel
sudo su - laravel
cd /var/www/html/laravel

#
# Download all dependencies, based on the specific git ids in composer.lock
# Note that running "composer update" will update all dependency versions.
# That is something that should only be done on development servers, not production.
#
# This must be run as user laravel
composer install

# Create a new APP_KEY in .env:
php artisan key:generate

# Create a new JWT_SECRET in .env:
php artisan jwt:secret --force


# Create initial tables
php artisan migrate:refresh --seed

#
# Flush and rebuild the config and route caches:
#
php artisan config:cache
php artisan route:cache

#
# OPTIONAL: If you want to deploy with some demo data:
#
php artisan db:seed --class=PlaiDemoSeeder


# ---------------------------------------------------
# 
# To update the app code on a deployed server:
#
# ---------------------------------------------------
sudo su - laravel
cd /var/www/html/laravel

# Get the latest app code:
git pull

#
# In case there are any new dependencies in the composer.json and composer.lock:
#
composer install

#
# In case there are any new tables w/seeds
#
php artisan migrate:refresh --seed


#
# Flush and rebuild the config and route caches:
#
php artisan config:cache
php artisan route:cache


#
# ---------------------------------------------------
# Developer setup:
#
# To create a local LXC development container:
# ---------------------------------------------------
#

# To manually create the LXC container:
# sudo lxc-create --template download --name t1
# NOTE: Specify distro, etc.    :  ubuntu bionic amd64
# sudo lxc-start -d -n t1
# sudo lxc-console -n t1   (for user)
# sudo lxc-attach -n t1    (for root)
#

# To update an older container with the latest versions of packages, run these:
# The 'dist-upgrade' updates packages and resolves new or expired dependencies.
# The 'dist-upgrade' does not upgrade to a newer version of the O.S.
#sudo apt-get update 
#sudo apt-get dist-upgrade
#sudo apt-get autoremove

#  ... finally, follow instructions for a new server (above).
#


# ---------------------------------------------------
#
# Initial Project Creation Notes
#
# This is how the intitial project was built. This could be referenced if building
# an all-new laravel project from scratch. These commands are not necessary if
# deploying to a fresh server
#
# ---------------------------------------------------

#
# Create a new Laravel project, with name "laravel"
#
cd /var/www/html/

# This will install the Application Key in laravel/.env for you:
laravel new laravel
# Same as: composer create-project --prefer-dist laravel/laravel laravel


# Set up Laravel with the basic tables and Auth routes:
sudo su - laravel
cd /var/www/html/laravel

# Enable routes for default laravel auth (HTML login and home web pages):
php artisan make:auth

# Create initial tables: migrations, password_resets, users
php artisan migrate



#
# Install JWT Auth for API auth:
# Use the "1.0.*" version, it is not the default yet as of 2018-08-13
#
composer require tymon/jwt-auth "1.0.*"

php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"

# Now there is a config/jwt.php

# Create a JWT_SECRET in .env:
php artisan jwt:secret


#
# Avatar support
#

# Add the avatar package.
# NOTE: This also requires editing the default User files (migration, Controller, etc.)
# But those files are already edited and committed to Git in the Plai Platform, so there
# is no need to do it again on a fresh server install. The git source has those changes.
#
composer require laravolt/avatar
php artisan vendor:publish --provider="Laravolt\Avatar\ServiceProvider"

#
# Add friendships package
#
# NOTE: This package went out of maintenance, so we now use a custom "path" repository
# entry in composer.json. After adding that repository, we installed the local copy of
# source code with:
#
#   composer require hootlex/laravel-friendships @dev
#
# I believe the respository must be added manually for any from-scratch build.
# See composer.json for the working example.
#
###composer require hootlex/laravel-friendships @dev
###php artisan vendor:publish --provider="Hootlex\Friendships\FriendshipsServiceProvider"

# Add UUID Generation
composer require "webpatser/laravel-uuid:^3.0"

# Add advert model:
composer require adumskis/laravel-advert dev-master
php artisan vendor:publish --provider="Adumskis\LaravelAdvert\AdvertServiceProvider" --tag=config
php artisan vendor:publish --provider="Adumskis\LaravelAdvert\AdvertServiceProvider" --tag=views
php artisan vendor:publish --provider="Adumskis\LaravelAdvert\AdvertServiceProvider" --tag=migrations

php artisan migrate


