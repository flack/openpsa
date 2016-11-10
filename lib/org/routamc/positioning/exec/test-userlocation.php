<?php
midcom::get()->auth->require_valid_user();

// Read location from session or user's location log
$user_location = org_routamc_positioning_user::get_location();
if (is_null($user_location))
{
    // No location found, try to geocode based on user IP
    $geocoder = org_routamc_positioning_geocoder::create('geoplugin');
    $location_parameters = array('ip' => $_SERVER['REMOTE_ADDR']);
    $user_location = $geocoder->geocode($location_parameters);
    if (!is_null($user_location))
    {
        // Store geocoded location to session or user's location log
        org_routamc_positioning_user::set_location($user_location);
    }
}

if (!is_null($user_location))
{
    printf('You\'re in %s, %s', $user_location['latitude'], $user_location['longitude']);
    // Will print "You're in 60.2345, 25.00456"
}
else
{
    if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1')
    {
        echo "You're here";
    }
    else
    {
        echo "No location found";
    }
}