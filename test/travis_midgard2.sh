# Install Pake
pyrus channel-discover pear.indeyets.ru
pyrus install -f http://pear.indeyets.ru/get/pake-1.6.3.tgz

# FIXME: We should be able to remove these once
# https://github.com/composer/composer/pull/775 lands
pyrus install HTML_Common

# Install Midgard2 library dependencies
sudo apt-get install -y dbus libgda-4.0-4 libgda-4.0-dev libxml2-dev libdbus-1-dev libdbus-glib-1-dev

# MidCOM also requires rcs
sudo apt-get install rcs

# Build Midgard2 core from recent tarball
wget -q https://github.com/midgardproject/midgard-core/tarball/${MIDGARD_EXT_VERSION}
tar -xzf ratatoskr
sh -c "cd midgardproject-midgard-core-*&&./autogen.sh --prefix=/usr; make; sudo make install"
rm -f ${MIDGARD_EXT_VERSION}

# Build and install Midgard2 PHP extension
wget -q https://github.com/midgardproject/midgard-php5/tarball/${MIDGARD_EXT_VERSION}
tar zxf ${MIDGARD_EXT_VERSION}
sh -c "cd midgardproject-midgard-php5-*&&php `pyrus get php_dir|tail -1`/pake.php install"
echo "extension=midgard2.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
