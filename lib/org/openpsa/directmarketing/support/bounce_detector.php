ˆ<?php
/**
 * @package org.openpsa.directmarketing
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 *
 *  Copyright (c) 2006 Jukka Zitting <jz@yukatan.fi>
 * This file is part of OpenPSA.
 *
 * OpenPSA is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Foobar is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Foobar; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Get the configuration arguments
if (count($argv) != 3) {
    error_log("usage: php bounce_detector.php BOUNCE_ADDRESS BOUNCE_LOGGER");
    exit(1);
}
$BOUNCE_ADDRESS = $argv[1];
$BOUNCE_LOGGER = $argv[2];

// Find the TOKEN position in the address template
$template = explode("TOKEN", $BOUNCE_ADDRESS, 2);
if (count($template) != 2) {
    error_log("Invalid address template: $BOUNCE_ADDRESS");
    exit(1);
}
$prefix = $template[0];
$suffix = $template[1];

// Match the recipient address to the address template
if (!isset($_ENV["RECIPIENT"])) {
    error_log("Recipient address not set, unable to get bounce token");
    exit(1);
}
$recipient = $_ENV["RECIPIENT"];
$token_length = strlen($recipient) - strlen($prefix) - strlen($suffix);
if (   $token_length <= 0
    || substr($recipient, 0, strlen($prefix)) != $prefix
    || substr($recipient, strlen($prefix) + $token_length) != $suffix) {
    error_log("Recipient address does not match address template: $recipient");
    exit(1);
}
$token = substr($recipient, strlen($prefix), $token_length);

// Log the bounce to the configured bounce logger
if (substr($BOUNCE_LOGGER, 0, strlen("file://")) == "file://") {
    $fh = fopen(substr($BOUNCE_LOGGER, strlen("file://")), "w+");
    if ($fh) {
        fwrite($fh, "$token\n");
        fclose($fh);
        exit(0);
    }
} elseif (   substr($BOUNCE_LOGGER, 0, strlen("http://")) == "http://"
          || substr($BOUNCE_LOGGER, 0, strlen("https://")) == "https://") {
    $client = new org_openpsa_httplib();
    $client->post($BOUNCE_LOGGER, ["token" => $token]);
    exit(0);
}
error_log("Error logging bounce token: $BOUNCE_LOGGER");
exit(1);
