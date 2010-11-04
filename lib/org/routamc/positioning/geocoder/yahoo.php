<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: geocoder.php 11571 2007-08-13 11:07:02Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

require_once(MIDCOM_ROOT. '/midcom/helper/utf8_to_ascii.php');

/**
 * Position geocodeing class that uses the local city database
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_geocoder_yahoo extends org_routamc_positioning_geocoder
{
    /**
     * Initializes the class. The real startup is done by the initialize() call.
     */
    function __construct()
    {
         $this->_component = 'org.routamc.positioning';
         $_MIDCOM->load_library('org.openpsa.httplib');
         parent::__construct();
    }

    /**
     * Empty default implementation, this calls won't do much.
     *
     * @param Array $location Parameters to geocode with, conforms to XEP-0080
     * @return Array containing geocoded information
     */
    function geocode($location, $options=array())
    {
        $results = array();
        
        $parameters = array
        (
            'output' => 'xml',
            'appid' => $this->_config->get('yahoo_application_key'),
        );
        
        if (! empty($options))
        {
            foreach ($options as $key => $value)
            {
                if (isset($parameters[$key]))
                {
                    $parameters[$key] = $value;
                }
            }
        }

        if (   !isset($location['postalcode'])
            && !isset($location['city']))
        {
            $this->error = 'POSITIONING_MISSING_ATTRIBUTES';
            return null;
        }
        $params = array();

        if (isset($location['street']))
        {
            $params[] = 'street=' . urlencode(utf8_to_ascii($location['street']));
        }
        if (isset($location['city']))
        {
            $params[] = 'city=' . urlencode(utf8_to_ascii($location['city']));
        }
        if (isset($location['region']))
        {
            $params[] = 'state=' . urlencode(utf8_to_ascii($location['region']));
        }
        if (isset($location['postalcode']))
        {
            $params[] = 'zip=' . urlencode($location['postalcode']);
        }

        foreach ($parameters as $key => $value)
        {
            if (! is_null($value))
            {
                $params[] = "{$key}=" . urlencode($value);
            }
        }
               
        $http_request = new org_openpsa_httplib();
        $url = 'http://local.yahooapis.com/MapsService/V1/geocode?' . implode('&', $params);
        $response = $http_request->get($url);
        $simplexml = simplexml_load_string($response);

        if (   !isset($simplexml->Result)
            || empty($simplexml->Result))
        {
            $this->error = 'POSITIONING_DETAILS_NOT_FOUND';
            return null;
        }
        
        if (   !isset($options['maxRows'])
            || $options['maxRows'] < 0)
        {
            $options['maxRows'] = 1;
        }
        
        for ($i=0; $i<$options['maxRows']; $i++)
        {
            if (! isset($simplexml->Result[$i]))
            {
                break;
            }
            
            $entry = $simplexml->Result[$i];
            
            $position = array();
            $position['latitude'] = (float) $entry->Latitude;
            $position['longitude'] = (float) $entry->Longitude;
            $position['distance'] = array
            (
                'meters' => 0,
                'bearing' => null,
            );
            $position['street'] = (string) $entry->Address;
            $position['city'] = (string) $entry->City;
            $position['region'] = (string) $entry->State;
            $position['country'] = (string) $entry->Country;
            $position['postalcode'] = (string) $entry->Zip;
            $position['alternate_names'] = '';
            $position['accuracy'] = ORG_ROUTAMC_POSITIONING_ACCURACY_CITY;

            // Cleaner cases, Yahoo! returns uppercase
            $position['street'] = ucwords(strtolower($position['street']));
            
            $position['city'] = ucwords(strtolower($position['city']));
            if (   $position['country'] != 'US'
                && strpos($position['city'], ' ') !== false)
            {
                // Yahoo! for some reason puts Zip code into the city name, in format "00580 Helsinki"
                $city_parts = explode(' ', $position['city']);
                if (is_numeric($city_parts[0]))
                {
                    $city_parts = array_slice($city_parts, 1);
                    $position['city'] = implode(' ', $city_parts);
                }
            }

            foreach ($entry->attributes() as $key => $val)
            {
                if ($key == 'warning')
                {
                    $this->error = $val;
                }
                if ($key == 'precision')
                {
                    switch ($val)
                    {
                        case 'address':
                            $position['accuracy'] = ORG_ROUTAMC_POSITIONING_ACCURACY_ADDRESS;
                            break;
                        case 'street':
                            $position['accuracy'] = ORG_ROUTAMC_POSITIONING_ACCURACY_STREET;
                            break;                    
                        default:
                            $position['accuracy'] = ORG_ROUTAMC_POSITIONING_ACCURACY_CITY;
                            break;
                    }
                }
            }

            $results[] = $position;
        }

        return $results;
    }
    
    /**
     * Empty default implementation, this won't do anything yet
     * Temporarily added geonames reverse geocoding here so we get atleast some results...
     *
     * @param Array $coordinates Contains latitude and longitude values
     * @return Array containing geocoded information
     */
    function reverse_geocode($coordinates, $options=array())
    {
        // $this->error = 'METHOD_NOT_IMPLEMENTED';        
        // return null;
        $results = array();
        
        $parameters = array
        (
            'radius' => 10,
            'maxRows' => 1,
            'style' => 'FULL',
        );
        
        if (! empty($options))
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
        $params = array();
        
        $params[] = 'lat=' . urlencode($coordinates['latitude']);
        $params[] = 'lng=' . urlencode($coordinates['longitude']);
        
        foreach ($parameters as $key => $value)
        {
            if (! is_null($value))
            {
                $params[] = "{$key}=" . urlencode($value);
            }
        }
        
        $http_request = new org_openpsa_httplib();
        $url = 'http://ws.geonames.org/findNearbyPlaceName?' . implode('&', $params);
        $response = $http_request->get($url);
        $simplexml = simplexml_load_string($response);
        
        if (   !isset($simplexml->geoname)
            || count($simplexml->geoname) == 0)
        {
            $this->error = 'POSITIONING_DETAILS_NOT_FOUND';
            
            if (isset($simplexml->status))
            {
                $constant_name = strtoupper(str_replace(" ", "_",$simplexml->status));
                $this->error = $constant_name;
            }
            return null;
        }
        
        for ($i=0; $i<$parameters['maxRows']; $i++)
        {
            if (! isset($simplexml->geoname[$i]))
            {
                break;
            }
            
            $entry = $simplexml->geoname[$i];

            $entry_coordinates = array
            (
                'latitude'  => (float) $entry->lat,
                'longitude' => (float) $entry->lng,
            );

            $meters = round( org_routamc_positioning_utils::get_distance($coordinates, $entry_coordinates) * 1000 );
            $entry_meters = round( (float) $entry->distance * 1000 );
            
            if ($entry_meters < $meters)
            {
                $meters = $entry_meters;
            }
            
            $position = array();
            $position['latitude' ] = (float) $entry->lat;
            $position['longitude' ] = (float) $entry->lng;
            $position['distance'] = array
            (
                'meters' => $meters,
                'bearing' => org_routamc_positioning_utils::get_bearing($coordinates, $entry_coordinates),
            );
            $position['city'] = (string) $entry->name;
            $position['region'] = (string) $entry->adminName2;
            $position['country'] = (string) $entry->countryCode;
            $position['postalcode' ] = (string) $entry->postalcode;
            $position['alternate_names'] = (string) $entry->alternateNames;
            $position['accuracy'] = ORG_ROUTAMC_POSITIONING_ACCURACY_GPS;

            $results[] = $position;            
        }
        
        return $results;
    }
}