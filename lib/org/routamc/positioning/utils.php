<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Position handling utils, use static methods
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_utils extends midcom_baseclasses_components_purecode
{
    /**
     * Get distance between to positions in kilometers
     *
     * Code from http://www.corecoding.com/getfile.php?file=25
     */
    function get_distance($from, $to, $unit = 'K', $round = true)
    {
        $theta = $from['longitude'] - $to['longitude'];
        $dist = sin(deg2rad($from['latitude'])) * sin(deg2rad($to['latitude'])) + cos(deg2rad($from['latitude'])) * cos(deg2rad($to['latitude'])) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $dist = $dist * 60 * 1.1515;

        if ($unit == "K")
        {
            $dist *= 1.609344;
        }
        else if ($unit == "N")
        {
            $dist *= 0.8684;
        }

        if ($round)
        {
            $dist = round($dist, 1);
        }
        return $dist;
    }

    /**
     * Get bearing from position to another
     *
     * Code from http://www.corecoding.com/getfile.php?file=25
     */
    function get_bearing($from, $to)
    {
        if (round($from['longitude'], 1) == round($to['longitude'], 1))
        {
            if ($from['latitude'] < $to['latitude'])
            {
                $bearing = 0;
            }
            else
            {
                $bearing = 180;
            }
        }
        else
        {
            $dist = org_routamc_positioning_utils::get_distance($from, $to, 'N');
            $arad = acos((sin(deg2rad($to['latitude'])) - sin(deg2rad($from['latitude'])) * cos(deg2rad($dist / 60))) / (sin(deg2rad($dist / 60)) * cos(deg2rad($from['latitude']))));
            $bearing = $arad * 180 / pi();
            if (sin(deg2rad($to['longitude'] - $from['longitude'])) < 0)
            {
                $bearing = 360 - $bearing;
            }
        }

        $dirs = array("N","E","S","W");

        $rounded = round($bearing / 22.5) % 16;
        if (($rounded % 4) == 0)
        {
            $dir = $dirs[$rounded / 4];
        }
        else
        {
            $dir = $dirs[2 * floor(((floor($rounded / 4) + 1) % 4) / 2)];
            $dir .= $dirs[1 + 2 * floor($rounded / 8)];
        }

        return $dir;
    }

    /**
     * Converts DMS ( Degrees / minutes / seconds ) to decimal format longitude / latitude
     *
     * Code from http://www.web-max.ca/PHP/misc_6.php
     */
    function coordinate_to_decimal($deg, $min, $sec)
    {
        return $deg+((($min*60)+($sec))/3600);
    }

    /**
     * Converts decimal longitude / latitude to DMS ( Degrees / minutes / seconds )
     *
     * Code from http://www.web-max.ca/PHP/misc_6.php
     */
    function decimal_to_coordinate($dec)
    {
        // This is the piece of code which may appear to
        // be inefficient, but to avoid issues with floating
        // point math we extract the integer part and the float
        // part by using a string function.
        $vars = explode(".", $dec);
        $deg = $vars[0];
        $tempma = "0.{$vars[1]}";
        $tempma = $tempma * 3600;
        $min = floor($tempma / 60);
        $sec = $tempma - ($min * 60);
        $coordinate = array
        (
            'deg' => $deg,
            'min' => $min,
            'sec' => $sec
        );
        return $coordinate;
    }

    /**
     * Pretty-print a coordinate value (latitude or longitude)
     *
     * Code from http://en.wikipedia.org/wiki/Geographic_coordinate_conversion
     *
     * @return string
     */
    function pretty_print_coordinate($coordinate)
    {
        return sprintf("%0.0f° %2.3f",
                 floor(abs($coordinate)),
                 60*(abs($coordinate)-floor(abs($coordinate)))
        );
    }

    /**
     * Pretty-print a full coordinate (longitude and latitude)
     *
     * Code from http://en.wikipedia.org/wiki/Geographic_coordinate_conversion
     *
     * @return string
     */
    function pretty_print_coordinates($latitude, $longitude)
    {
        return sprintf("%s %s, %s %s",
                 ($latitude>0)?"N":"S",  org_routamc_positioning_utils::pretty_print_coordinate($latitude),
                 ($longitude>0)?"E":"W", org_routamc_positioning_utils::pretty_print_coordinate($longitude)
        );
    }

    /**
     * Pretty print a position mapping either to a city or cleaned coordinates
     *
     * @return string
     */
    function pretty_print_location($latitude, $longitude)
    {
        $coordinates = array
        (
            'latitude'  => $latitude,
            'longitude' => $longitude,
        );
        $closest = org_routamc_positioning_utils::get_closest('org_routamc_positioning_city_dba', $coordinates, 1);
        $city_string = org_routamc_positioning_utils::pretty_print_coordinates($coordinates['latitude'], $coordinates['longitude']);
        foreach ($closest as $city)
        {
            $city_coordinates = array
            (
                'latitude'  => $city->latitude,
                'longitude' => $city->longitude,
            );
            $city_distance = round(org_routamc_positioning_utils::get_distance($coordinates, $city_coordinates));
            if ($city_distance <= 4)
            {
                $city_string = "{$city->city}, {$city->country}";
            }
            else
            {
                $bearing = org_routamc_positioning_utils::get_bearing($city_coordinates, $coordinates);
                $city_string = sprintf(midcom::get()->i18n->get_string('%skm %s of %s', 'org.routamc.positioning'), $city_distance, $bearing, "{$city->city}, {$city->country}");
            }
        }
        return $city_string;
    }

    /**
     * Pretty print a position mapping Microformatted city name or other label
     *
     * @return string
     */
    function microformat_location($latitude, $longitude)
    {
        $coordinates = array
        (
            'latitude'  => $latitude,
            'longitude' => $longitude,
        );
        $closest = org_routamc_positioning_utils::get_closest('org_routamc_positioning_city_dba', $coordinates, 1);

        $latitude_string = org_routamc_positioning_utils::pretty_print_coordinate($latitude);
        $latitude_string .= ($latitude > 0) ? " N" : " S";
        $longitude_string = org_routamc_positioning_utils::pretty_print_coordinate($longitude);
        $longitude_string .= ($longitude > 0) ? " E" : " W";

        if (count($closest) == 0)
        {
            // No city found, generate only geo microformat

            $coordinates_string  = "<span class=\"geo\">";
            $coordinates_string .= "<abbr class=\"latitude\" title=\"{$latitude}\">{$latitude_string}</abbr> ";
            $coordinates_string .= "<abbr class=\"longitude\" title=\"{$longitude}\">{$longitude_string}</abbr>";
            $coordinates_string .= "</span>";

            return $coordinates_string;
        }

        foreach ($closest as $city)
        {
            // City found, combine it and geo

            $city_string  = "<span class=\"geo adr\">";
            $city_string .= "<abbr class=\"latitude\" title=\"{$latitude}\">{$latitude_string}</abbr> ";
            $city_string .= "<abbr class=\"longitude\" title=\"{$longitude}\">{$longitude_string}</abbr> ";

            $city_coordinates = array
            (
                'latitude'  => $city->latitude,
                'longitude' => $city->longitude,
            );

            $city_distance = round(org_routamc_positioning_utils::get_distance($coordinates, $city_coordinates));

            $city_label  = "<span class=\"locality\">{$city->city}</span>, ";
            $city_label .= "<span class=\"country-name\">{$city->country}</span>";

            if ($city_distance <= 4)
            {
                $city_string .= $city_label;
            }
            else
            {
                $bearing = org_routamc_positioning_utils::get_bearing($city_coordinates, $coordinates);
                $city_string .= sprintf(midcom::get()->i18n->get_string('%skm %s of %s', 'org.routamc.positioning'), $city_distance, $bearing, $city_label);
            }

            $city_string .= "</span>";
        }
        return $city_string;
    }

    /**
     * Figure out which class to use for positioning
     * @param string $class MidCOM class name
     * @param string $classname
     */
    function get_positioning_class($class)
    {
        // See what kind of object we're querying for
        switch ($class)
        {
            case 'org_routamc_positioning_log_dba':
            case 'org_routamc_positioning_city_dba':
            case 'org_routamc_positioning_aerodrome_dba':
                // Real position entry, query it directly
                $classname = $class;
                break;
            default:
                // Non-positioning MidCOM DBA object, query it through location cache
                $classname = 'org_routamc_positioning_location_dba';
                break;
        }
        return $classname;
    }

    /**
     * Get closest items
     *
     * Note: If you set a max distance you may not always get the number of items specified in the limit.
     *
     * @param string $class MidCOM DBA class to query
     * @param array $center Center position
     * @param integer $limit How many results to return
     * @param integer $max_distance Maximum distance of returned objects in kilometers, or null if any
     * @param float $modifier
     * @return array array of MidCOM DBA objects sorted by proximity
     */
    function get_closest($class, array $center, $limit, $max_distance = null, $modifier = 0.15)
    {
        $classname = org_routamc_positioning_utils::get_positioning_class($class);
        $direct = ($classname == $class);
        $qb =  midcom::get()->dbfactory->new_query_builder($classname);

        if (!$direct)
        {
            // We're querying a regular DBA object through a location object
            $qb->add_constraint('parentclass', '=', $class);
        }

        static $rounds = 0;
        $rounds++;

        // Limit to earth coordinates
        $from['latitude'] = $center['latitude'] + $modifier;
        if ($from['latitude'] > 90)
        {
            $from['latitude'] = 90;
        }
        $from['longitude'] = $center['longitude'] - $modifier;
        if ($from['longitude'] < -180)
        {
            $from['longitude'] = -180;
        }
        $to['latitude'] = $center['latitude'] - $modifier;
        if ($to['latitude'] < -90)
        {
            $to['latitude'] = -90;
        }
        $to['longitude'] = $center['longitude'] + $modifier;
        if ($to['longitude'] > 180)
        {
            $to['longitude'] = 180;
        }

        if (!isset($current_locale))
        {
            $current_locale = setlocale(LC_NUMERIC, '0');
            setlocale(LC_NUMERIC, 'C');
        }

        $qb->begin_group('AND');
        $qb->add_constraint('latitude', '<', (float) $from['latitude']);
        $qb->add_constraint('latitude', '>', (float) $to['latitude']);
        $qb->end_group();
        $qb->begin_group('AND');
        $qb->add_constraint('longitude', '>', (float) $from['longitude']);
        $qb->add_constraint('longitude', '<', (float) $to['longitude']);
        $qb->end_group();
        $result_count = $qb->count();

        if ($result_count == 0)
        {
            // Check that there are any in the DB before proceeding further
            $qb_check =  midcom::get()->dbfactory->new_query_builder($classname);
            if ($qb_check->count_unchecked() == 0)
            {
                return array();
            }
        }

        if ($result_count < $limit)
        {
            if (   $from['latitude'] == 90
                && $from['longitude'] == -180
                && $to['latitude'] == -90
                && $to['longitude'] == 180)
            {
                // We've queried the entire globe so we return whatever we got
                $results = $qb->execute();
                $closest = array();
                foreach ($results as $result)
                {
                    $result_coordinates = array
                    (
                        'latitude' => $result->latitude,
                        'longitude' => $result->longitude,
                    );

                    $distance = sprintf("%05d", round(org_routamc_positioning_utils::get_distance($center, $result_coordinates)));

                    if (   !is_null($max_distance)
                        && $distance > $max_distance)
                    {
                        // This entry is too far
                        continue;
                    }

                    if (!$direct)
                    {
                        // Instantiate the real object as the result
                        $located_object = new $class($result->parent);
                        if (!$located_object->guid)
                        {
                            // This one has been deleted
                            midcom::get()->auth->request_sudo('org.routamc.positioning');
                            $result->delete();
                            midcom::get()->auth->drop_sudo();
                            continue;
                        }
                        $result = $located_object;
                        $result->latitude = $result_coordinates['latitude'];
                        $result->longitude = $result_coordinates['longitude'];
                        $result->distance = (int) $distance;
                    }

                    $closest[$distance . $result->guid] = $result;
                }
                ksort($closest);
                reset($closest);
                return $closest;
            }

            $modifier = $modifier * 1.05;
            setlocale(LC_NUMERIC, $current_locale);
            return self::get_closest($class, $center, $limit, $max_distance, $modifier);
        }

        $results = $qb->execute();
        $closest = array();
        foreach ($results as $result)
        {
            $result_coordinates = array
            (
                'latitude'  => $result->latitude,
                'longitude' => $result->longitude,
            );
            $distance = sprintf("%05d", round(self::get_distance($center, $result_coordinates)));

            if (   !is_null($max_distance)
                && $distance > $max_distance)
            {
                // This entry is too far
                continue;
            }

            if (!$direct)
            {
                // Instantiate the real object as the result
                $located_object = new $class($result->parent);
                if (!$located_object->guid)
                {
                    // This one has been deleted
                    midcom::get()->auth->request_sudo('org.routamc.positioning');
                    $result->delete();
                    midcom::get()->auth->drop_sudo();
                    continue;
                }
                $result = $located_object;
                $result->latitude = $result_coordinates['latitude'];
                $result->longitude = $result_coordinates['longitude'];
                $result->distance = (int) $distance;
            }

            $closest[$distance . $result->guid] = $result;
        }

        ksort($closest);
        reset($closest);
        while (count($closest) > $limit)
        {
            array_pop($closest);
        }
        setlocale(LC_NUMERIC, $current_locale);
        return $closest;
    }
}
