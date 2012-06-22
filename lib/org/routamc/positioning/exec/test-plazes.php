<?php
midcom::get('auth')->require_valid_user();

$user = midcom::get('auth')->user->get_storage();

$plazes = org_routamc_positioning_importer::create('plazes');
$coordinates = $plazes->get_plazes_location($user);

if ($coordinates)
{
    echo sprintf('According to Plazes your position since %s is %s', strftime('%x %X', $coordinates['date']), org_routamc_positioning_utils::microformat_location($coordinates['latitude'], $coordinates['longitude']));
}
else
{
    echo "Failed to get position, last error is {$plazes->error} {$plazes->error_string}";
}
?>