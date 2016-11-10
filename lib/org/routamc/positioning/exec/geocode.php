<?php
if (!isset($_GET['service']))
{
    $service = 'city';
}
else
{
    $service = $_GET['service'];
}

if (!isset($_GET['dir']))
{
    $direction = '';
}
else
{
    $direction = 'reverse_';
}

$options = array();
if (   isset($_GET['options'])
    && is_array($_GET['options']))
{
    foreach ($_GET['options'] as $key => $value)
    {
        $options[$key] = $value;
    }
}

$geocoder = org_routamc_positioning_geocoder::create($service);

$location = array();
foreach ($_GET as $key => $value)
{
    switch ($key)
    {
        case 'longitude':
        case 'latitude':
            if (!empty($direction))
            {
                $location[$key] = $value;
            }
        // Accept only XEP-0080 values
        case 'area':
        case 'building':
        case 'country':
        case 'description':
        case 'floor':
        case 'city':
        case 'postalcode':
        case 'region':
        case 'room':
        case 'street':
        case 'text':
        case 'uri':
            $location[$key] = $value;
            break;
    }
}

$method_name = "{$direction}geocode";

$positions = $geocoder->$method_name($location, $options);
if (is_null($positions))
{
    $error_str = 'unknown';
    if (isset($geocoder->error))
    {
        $error_str = $geocoder->error;
    }

    throw new midcom_error("Geocoding failed: {$error_str}");
}

midcom::get()->cache->content->content_type("text/xml; charset=UTF-8");
midcom::get()->header("Content-type: text/xml; charset=UTF-8");

echo "<results>\n";
foreach ($positions as $position)
{
    echo "    <position>\n";
    foreach ($position as $key => $value)
    {
        if (is_array($value))
        {
            echo "        <{$key}>\n";
            foreach ($value as $sub_key => $sub_value)
            {
                echo "            <{$sub_key}>{$sub_value}</{$sub_key}>\n";
            }
            echo "        </{$key}>\n";
        }
        else
        {
            echo "        <{$key}>{$value}</{$key}>\n";
        }
    }
    echo "    </position>\n";
}
echo "</results>\n";