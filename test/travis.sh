COMPOSER=`which composer`;

# MidCOM requires rcs
sudo apt-get install rcs

${COMPOSER} install
sudo chown -R travis var/
