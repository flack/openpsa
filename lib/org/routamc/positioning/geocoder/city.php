<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Position geocodeing class that uses the local city database
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_geocoder_city extends org_routamc_positioning_geocoder
{
    /**
     * Empty default implementation, this calls won't do much.
     *
     * @param array $location Parameters to geocode with, conforms to XEP-0080
     * @param array $options Implementation-specific configuration
     * @return array containing geocoded information
     */
    public function geocode(array $location, array $options = array())
    {
        $results = array();

        $parameters = array
        (
            'maxRows' => 1,
        );

        if (!empty($options))
        {
            foreach ($options as $key => $value)
            {
                if (isset($parameters[$key]))
                {
                    $parameters[$key] = $value;
                }
            }
        }

        if ($parameters['maxRows'] < 1)
        {
            $parameters['maxRows'] = 1;
        }

        if (!isset($location['city']))
        {
            $this->error = 'POSITIONING_MISSING_ATTRIBUTES';
            return null;
        }

        $city_entry = null;
        $qb = org_routamc_positioning_city_dba::new_query_builder();
        $qb->add_constraint('city', '=', $location['city']);

        if (isset($location['country']))
        {
            $qb->add_constraint('country', '=', $location['country']);
        }

        $qb->add_order('population', 'DESC');
        $qb->set_limit($parameters['maxRows']);
        $matches = $qb->execute();

        if (count($matches) < 1)
        {
            // Seek the city entry by alternate names via a LIKE query
            $qb = org_routamc_positioning_city_dba::new_query_builder();
            $qb->add_constraint('alternatenames', 'LIKE', "%|{$location['city']}|%");

            if (isset($location['country']))
            {
                $qb->add_constraint('country', '=', $location['country']);
            }

            $qb->set_limit($parameters['maxRows']);
            $matches = $qb->execute();

            if (count($matches) < 1)
            {
                $this->error = 'POSITIONING_CITY_NOT_FOUND';
                return null;
            }
        }

        foreach ($matches as $city_entry)
        {
            $results[] = array
            (
                'latitude' => $city_entry->latitude,
                'longitude' => $city_entry->longitude,
                'distance' => array
                (
                    'meters' => 0,
                    'bearing' => null,
                ),
                'city' => $city_entry->city,
                'region' => $city_entry->region,
                'country' => $city_entry->country,
                'postalcode' => null,
                'alternate_names' => $city_entry->alternatenames,
                'accuracy' => org_routamc_positioning_log_dba::ACCURACY_CITY
            );
        }

        return $results;
    }

    /**
     * @param array $coordinates Contains latitude and longitude values
     * @param array $options
     * @return Array containing geocoded information
     */
    function reverse_geocode($coordinates, $options=array())
    {
        $results = array();

        $parameters = array
        (
            'maxRows' => 1,
        );

        if (!empty($options))
        {
            foreach ($options as $key => $value)
            {
                if (isset($parameters[$key]))
                {
                    $parameters[$key] = $value;
                }
            }
        }

        if (   !isset($coordinates['latitude'])
            && !isset($coordinates['longitude']))
        {
            $this->error = 'POSITIONING_MISSING_ATTRIBUTES';
            return null;
        }

        $closest = org_routamc_positioning_utils::get_closest('org_routamc_positioning_city_dba', $coordinates, $parameters['maxRows']);

        if (empty($closest))
        {
            $this->error = 'POSITIONING_DETAILS_NOT_FOUND';
            return null;
        }

        foreach ($closest as $city)
        {
            $city_coordinates = array
            (
                'latitude'  => $city->latitude,
                'longitude' => $city->longitude,
            );

            $position = array();
            $position['latitude' ] = $city->latitude;
            $position['longitude' ] = $city->longitude;
            $position['distance'] = array
            (
                'meters' => round( org_routamc_positioning_utils::get_distance($coordinates, $city_coordinates) * 1000 ),
                'bearing' => org_routamc_positioning_utils::get_bearing($coordinates, $city_coordinates),
            );
            $position['city'] = $city->city;
            $position['region'] = $city->region;
            $position['country'] = $city->country;
            $position['alternate_names'] = $city->alternatenames;
            $position['accuracy'] = org_routamc_positioning_log_dba::ACCURACY_GPS;

            $results[] = $position;
        }

        return $results;
    }
}
