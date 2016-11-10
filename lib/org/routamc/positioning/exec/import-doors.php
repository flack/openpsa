<?php
midcom::get()->auth->require_admin_user();

midcom::get()->disable_limits();

$json = file_get_contents('/tmp/doors.json');

$doors = json_decode($json);

echo "<pre>\n";
foreach ($doors->entrances as $entrance) {
    $location = new org_routamc_positioning_location_dba();
    $location->latitude = (float) ($entrance->lat  / 20037508.34) * 180;
    $location->latitude = 180 / M_PI * (2 * atan(exp($location->latitude * M_PI / 180)) - M_PI / 2);
    $location->longitude = (float) ($entrance->lon / 20037508.34) * 180;
    $location->building = (string) $entrance->title;
    $location->street = (string) $entrance->address;

    if (isset($entrance->descr)) {
        $location->description = (string) $entrance->descr;
    }

    if (isset($entrance->url)) {
        $location->uri = (string) $entrance->url;
    }

    // Other metadata
    $location->relation = 20;
    $location->create();

    echo "{$location->building}: " . midcom_connection::get_error_string() . "\n";
}
echo "</pre>\n";