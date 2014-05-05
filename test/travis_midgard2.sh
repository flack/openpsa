COMPOSER=`which composer`;

${COMPOSER} self-update

# MidCOM requires rcs
sudo apt-get install rcs

if [ "$MIDGARD_EXT_VERSION" != "portable" ]
then
    # Install Pake
    pyrus channel-discover pear.indeyets.ru
    pyrus channel-discover pear.symfony-project.com
    pyrus install -f http://pear.indeyets.ru/get/pake-1.7.2.tgz

    # Install Midgard2 library dependencies
    sudo apt-get install -y dbus libgda-4.0-4 libgda-4.0-dev libxml2-dev libdbus-1-dev libdbus-glib-1-dev

    # Build Midgard2 core from recent tarball
    wget -q https://github.com/midgardproject/midgard-core/tarball/${MIDGARD_EXT_VERSION}
    tar -xzf ${MIDGARD_EXT_VERSION}
    sh -c "cd midgardproject-midgard-core-*&&./autogen.sh --prefix=/usr; make; sudo make install"
    rm -f ${MIDGARD_EXT_VERSION}

    # Build and install Midgard2 PHP extension
    wget -q https://github.com/midgardproject/midgard-php5/tarball/${MIDGARD_EXT_VERSION}
    tar zxf ${MIDGARD_EXT_VERSION}
    sh -c "cd midgardproject-midgard-php5-*&&php `pyrus get php_dir|tail -1`/pake.php install"
    echo "extension=midgard2.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
else
    ${COMPOSER} require openpsa/midgard-portable:dev-master
fi

${COMPOSER} install
sudo chown -R travis var/
