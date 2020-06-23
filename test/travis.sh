COMPOSER=`which composer`;

${COMPOSER} self-update

sudo apt-get update

# MidCOM requires rcs
sudo apt-get install rcs memcached

printf "\n" | pecl install memcached

${COMPOSER} install
sudo chown -R travis var/
