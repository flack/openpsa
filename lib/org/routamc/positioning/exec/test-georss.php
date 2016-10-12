<?php
midcom::get()->auth->require_valid_user();

$user = midcom::get()->auth->user->get_storage();

$html = org_routamc_positioning_importer::create('georss');
$coordinates = $html->get_georss_location($user);

if ($coordinates)
{
    printf('According to your GeoRSS feed your position is %s', org_routamc_positioning_utils::microformat_location($coordinates['latitude'], $coordinates['longitude']));
}
else
{
    echo "Failed to get position, last error is {$html->error}";
}
?>