<?php
midcom::get()->auth->require_valid_user();

$cities = Array();
/*
$cities_to_add = Array(
    Array(
        'city'      => 'Helsinki',
        'country'   => 'FI',
        'latitude'  => 60.175555,
        'longitude' => 24.9341667,
    ),
    Array(
        'city'      => 'London',
        'country'   => 'GB',
        'latitude'  => 51.5,
        'longitude' => -0.1166667,
    ),
    Array(
        'city'      => 'Linköping',
        'country'   => 'SE',
        'latitude'  => 58.4166667,
        'longitude' => 15.6166667,
    ),
    Array(
        'city'      => 'Linköping',
        'country'   => 'SE',
        'latitude'  => 58.4166667,
        'longitude' => 15.6166667,
    ),
    Array(
        'city'      => 'Curitiba',
        'country'   => 'BR',
        'latitude'  => -25.4166667,
        'longitude' => -49.25,
    ),
    Array(
        'city'      => 'Bratsk',
        'country'   => 'RU',
        'latitude'  => 56.35,
        'longitude' => 101.9166667,
    ),
    Array(
        'city'      => 'Cape Town',
        'country'   => 'ZA',
        'latitude'  => -33.9166667,
        'longitude' => 18.4166667,
    ),
    Array(
        'city'      => 'Wellington',
        'country'   => 'NZ',
        'latitude'  => -41.3,
        'longitude' => 174.7833333,
    ),
);

foreach ($cities_to_add as $city)
{
    $new_city = new org_routamc_positioning_city_dba();
    $new_city->city      = $city['city'];
    $new_city->country   = $city['country'];
    $new_city->latitude  = $city['latitude'];
    $new_city->longitude = $city['longitude'];

    $stat = $new_city->create();
    if ($stat)
    {
        $cities[] = $new_city;
    }
}*/

$user = midcom::get()->auth->user->get_storage();
$user_position = new org_routamc_positioning_person($user);
$coordinates = $user_position->get_coordinates();

if (!$coordinates)
{
    throw new midcom_error("Failed to get your current position.");
}

echo "<p>".sprintf('Your position is %s', org_routamc_positioning_utils::pretty_print_coordinates($coordinates['latitude'], $coordinates['longitude']))."</p>\n";

$run_times = 1;
$run = 0;
$total_time = 0;
while ($run < $run_times)
{
    $run++;
    $start = microtime();

    $closest = org_routamc_positioning_utils::get_closest('org_routamc_positioning_city_dba', $coordinates, 10);

    echo "<p>Closest places to you are:<br />";
    echo "<ol>\n";
    foreach ($closest as $city)
    {
        $city_coordinates = Array(
            'latitude'  => $city->latitude,
            'longitude' => $city->longitude,
        );
        echo "<li>{$city->city}, {$city->country} is " . round(org_routamc_positioning_utils::get_distance($coordinates, $city_coordinates)) . " kilometers " . org_routamc_positioning_utils::get_bearing($coordinates, $city_coordinates) . " from you</li>";
    }
    echo "</ol>\n";

    $end = microtime();
    $end_parts = explode(' ', $end);
    $end = $end_parts[1] + $end_parts[0];
    $start_parts = explode(' ', $start);
    $start = $start_parts[1] + $start_parts[0];
    $time_used = $end - $start;
    $total_time = $time_used + $total_time;
}
$average = $total_time / $run_times;
echo "<p>Query took {$total_time} seconds (on average {$average} seconds per query).</p>";