OpenPSA [![Build Status](https://secure.travis-ci.org/flack/openpsa.svg?branch=master)](https://travis-ci.org/flack/openpsa)
=======

[OpenPSA](http://midgard-project.org/openpsa/) is a management suite for web agencies and consultants that provides a unified interface for handling many common business processes. It is built on a component architecture that makes it easy to integrate new components for specific requirements and is available as free software under the terms of the LGPL license.

OpenPSA 1.x was initially released as Open Source under the GNU GPL license by [Nemein](http://nemein.com/) on May 8th 2004 to support the [5th anniversary](http://www.midgard-project.org/updates/midgard-5th-anniversary.html) of the [Midgard Project](http://www.midgard-project.org/). The package was originally known as Nemein.Net.

The currently active branch (OpenPSA 9) is developed and supported by [CONTENT CONTROL](http://www.contentcontrol-berlin.de/).

Read more in <http://openpsa2.org/>

## Dependencies

OpenPSA interacts with database backends through the [Midgard](http://midgard-project.org/) API. There are currently 
three different environments supported:

* legacy Midgard1 8.09 (in which case you must use PHP 5.3, Apache and MySQL backend)

* [Midgard2 Content Repository](https://github.com/midgardproject/midgard-core), provided by `libmidgard2-2010` with 
  PHP bindings to Midgard2 (`php5-midgard2`), and GNOME database abstraction layer (`libgda-4.0`). No restrictions on
  webserver or database choice. PHP 5.4 is recommended

* [midgard-portable](https://github.com/flack/midgard-portable), which is based on [Doctrine](http://www.doctrine-project.org/), 
  so it will support all environments Doctrine supports.

The repository's `composer.json` installs `midgard-portable` by default, but you can still select one of the others to
run the application.

On the client side, all modern web browser should work

## Setup

You can either clone this repo or add `openpsa/midcom` to your `composer.json`

Then, change to your project's root dir and use Composer to install PHP dependencies

    $ wget http://getcomposer.org/installer && php installer
    $ php composer.phar install

Next you should make OpenPSA available under your document root:

    $ ln -s web /var/www/yourdomain

This will setup the project directory for OpenPSA usage. You can then create new database by running:

    $ ./vendor/bin/openpsa-installer midgard2:setup

See the [openpsa-installer](https://github.com/flack/openpsa-installer) documentation for more details.

## Setting up Lighttpd

Enable `rewrite` and `fastcgi` modules in your Lighttpd config (by default `/etc/lighttpd/lighttpd.conf`):

    server.modules += (
        "mod_fastcgi",
        "mod_rewrite"
    )

Also enable FastCGI to talk to your PHP installation:

    fastcgi.server = (
        ".php" => (
            (
                "bin-path" => "/usr/bin/php-cgi",
                "socket" => "/tmp/php.socket"
            )
        )
    )

Then just configure your Lighttpd to pass all requests to the OpenPSA "rootfile":

    url.rewrite-once = (
        "^/openpsa2-static/OpenPsa2/(.*)$" => "/openpsa/themes/OpenPsa2/static/$1",
        "^/openpsa2-static/(.*)$" => "/openpsa/static/$1",
        "^([^\?]*)(\?(.+))?$" => "openpsa/rootfile.php$2"
    )

*Note:* this rewrite rule is a bit too inclusive, to be improved.

Restart your Lighttpd and point your browser to the address you're using with the server. Default login to OpenPSA is `admin`/`password`.

## Setting up Apache

Alternatively, you can also run under Apache (or any other web server, for that matter). Just make sure that you have mod_rewrite enabled:

    a2enmod rewrite

And use something like this in your vhost config (or .htaccess file):

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ /openpsa/rootfile.php [QSA,L]

## Setting up a Midgard2 server

You need `php-cgi` (typically at `/etc/php5/cgi/conf.d/midgard2.ini`) with some settings that open a Midgard2 database connection:

    extension=midgard2.so

    [midgard2]
    midgard.engine = On
    midgard.http = On
    midgard.configuration_file="/etc/midgard2/conf.d/openpsa"
