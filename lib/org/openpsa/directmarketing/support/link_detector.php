<?php
/**
 * @package org.openpsa.directmarketing
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 *
 *
 * Copyright (c) 2006 Jukka Zitting <jz@yukatan.fi>
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

if (isset($_GET["token"])) {
    $token = $_GET["token"];
} else {
    error_log("token parameter not given");
    header("Status: 400 Bad request");
    exit(0);
}
if (isset($_GET["link"])) {
    $link = $_GET["link"];
} else {
    error_log("link parameter not given");
    header("Status: 400 Bad request");
    exit(0);
}
if (isset($_ENV["logger"])) {
    $logger = $_ENV["logger"];
} else {
    $logger = "";
}
if (isset($_ENV["domains"])) {
    $domains = explode(",", $_ENV["domains"]);
} else {
    $domains = array();
}

// Domain control
if ($domains) {
    $found = false;
    foreach ($domains as $domain) {
        if (strpos($link, $domain) !== false) {
            $found = true;
        }
    }
    if (!$found) {
        error_log("Forbidden redirect: $link");
        header("Status: 403 Forbidden");
        exit(0);
    }
}

// Send the redirect
header("Location: $link");

// Log the link to the configured link logger
if (substr($logger, 0, strlen("file://")) == "file://") {
    $fh = fopen(substr($logger, strlen("file://")), "w+");
    if ($fh) {
        fwrite($fh, "$token $link\n");
        fclose($fh);
    }
} elseif (preg_match('/https?:\/\//', $logger)) {
    $client = new HTTP_Client();
    $client->post($logger, array("token" => $token, "link" => $link));
} else {
    error_log("link detected: $token $link");
}
exit(0);
