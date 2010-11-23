<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: geocoder.php 11571 2007-08-13 11:07:02Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Position geocoding class that uses http://tinygeocoder.com/
 *
 * @see http://tinygeocoder.com/blog/how-to-use/
 * @package org.routamc.positioning
 */
class org_routamc_positioning_geocoder_tiny extends org_routamc_positioning_geocoder
{
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
    function geocode($location)
    {
        $field_order = array
        (
            'street',
            'city',
            'postalcode',
            'country',
        );
        $q  = '';
        if (!isset($location['country']))
        {
            $location['country'] = $this->_config->get('geocoder_default_country');
            if (empty($location['country']))
            {
                unset($location['country']);
            }
        }
        foreach($field_order as $field)
        {
            if (   isset($location[$field])
                && trim($location[$field]) !== '')
            {
                $q .= ',' . trim($location[$field]);
            }
        }
        if (strlen($q) === 0)
        {
            $this->error = 'POSITIONING_MISSING_ATTRIBUTES';
            return null;
        }
        $q = substr($q, 1);
        $url = 'http://tinygeocoder.com/create-api.php?q=' . rawurlencode($q);
        $http_request = new org_openpsa_httplib();
        $response = $http_request->get($url);
        if (empty($response))
        {
            $this->error = 'POSITIONING_SERVICE_NOT_AVAILABLE';
            return null;
        }
        if (!preg_match('/^(-?[0-9.]+),(-?[0-9.]+)$/', $response, $coordinate_matches))
        {
            $this->error = 'POSITIONING_DETAILS_NOT_FOUND';
            return null;
        }

        $position['accuracy'] = ORG_ROUTAMC_POSITIONING_ACCURACY_CITY;
        if (   isset($location['street'])
            && !empty($location['street']))
        {
            $position['accuracy'] = ORG_ROUTAMC_POSITIONING_ACCURACY_STREET;
        }
        $position['latitude'] = (float) $coordinate_matches[1];
        $position['longitude'] = (float) $coordinate_matches[2];
        
        return array($position);
    }

    /**
     * Empty default implementation, this calls won't do much.
     *
     * @param Array $coordinates Contains latitude and longitude values
     * @return Array containing geocoded information
     */
    function reverse_geocode($coordinates)
    {
        if (   !isset($coordinates['latitude'])
            && !isset($coordinates['longitude']))
        {
            $this->error = 'POSITIONING_MISSING_ATTRIBUTES';
            return null;
        }
        $lat_str = str_replace(',', '.', (string)$coordinates['latitude']);
        $lon_str = str_replace(',', '.', (string)$coordinates['longitude']);

        $url = "http://tinygeocoder.com/create-api.php?g={$lat_str},$lon_str";
        $http_request = new org_openpsa_httplib();
        $response = $http_request->get($url);
        if (empty($response))
        {
            $this->error = 'POSITIONING_SERVICE_NOT_AVAILABLE';
            return null;
        }
        // check for errors
        if (strpos($response, "couldn't get") !== false)
        {
            $this->error = 'POSITIONING_DETAILS_NOT_FOUND';
            return null;
        }
        $position = array();
        // Walk the reponse parts in reverse order putting to fields as specified by the response_fields below
        $response_parts = explode(',', $response);
        $response_parts = array_reverse($response_parts);
        $response_fields = array
        (
            'country',
            'postalcode',
            'city',
            'street',
        );
        while (count($response_parts) > 0)
        {
            if (count($response_fields) > 0)
            {
                $field = array_pop($response_fields);
            }
            if (!isset($position[$field]))
            {
                $position[$field] = array_pop($response_parts);
            }
            else
            {
                //special case got more fields in response than we know how to handle, add them to the last field
                $position[$field] .= ' ' . array_pop($response_parts);
            }
        }

        return array($position);
    }
}
?>