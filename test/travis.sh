COMPOSER=`which composer`;

${COMPOSER} self-update

sudo apt-get update

# MidCOM requires rcs
sudo apt-get install rcs

${COMPOSER} install
sudo chown -R travis var/
