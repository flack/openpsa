<?php
midcom::get()->auth->require_admin_user();

midcom::get()->disable_limits();

$http_request = new org_openpsa_httplib();

$csv = $http_request->get('http://weather.gladstonefamily.net/cgi-bin/location.pl/pjsg_all_location.csv?csv=1');
$csv = str_replace('"', '', $csv);
$lines = explode("\n", $csv);
foreach ($lines as $line)
{
    $aerodromeinfo = explode(',', $line);

    // Skip the non-ICAO ones
    if (   empty($aerodromeinfo[0])
        || strlen($aerodromeinfo[0]) != 4)
    {
        continue;
    }

    // Skip non-WMO ones
    if (empty($aerodromeinfo[1]))
    {
        continue;
    }

    echo "<br />Importing {$aerodromeinfo[0]} {$aerodromeinfo[2]}...\n";
    $aerodrome = new org_routamc_positioning_aerodrome_dba();
    $aerodrome->icao = $aerodromeinfo[0];
    $aerodrome->wmo = $aerodromeinfo[1];
    $aerodrome->name = $aerodromeinfo[2];
    $aerodrome->country = substr($aerodromeinfo[4], 0, 2);
    $aerodrome->latitude = (float) $aerodromeinfo[5];
    $aerodrome->longitude = (float) $aerodromeinfo[6];
    $aerodrome->altitude = (int) $aerodromeinfo[7];
    $aerodrome->create();
    echo midcom_connection::get_error_string();
    flush();
}