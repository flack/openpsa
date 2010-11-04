if (navigator.geolocation)
{
    navigator.geolocation.getCurrentPosition(org_routamc_positioning_update_location);
}

function org_routamc_positioning_update_location(location)
{   
    jQuery.post
    (
        MIDCOM_PAGE_PREFIX + 'midcom-exec-org.routamc.positioning/geolocation-json.php',
        {
            latitude: location.coords.latitude,
            longitude: location.coords.longitude,
        }
    );
}
