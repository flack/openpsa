<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * GeoPlugin geocoding service, geocodes IP addresses
 *
 * GeoPlugin (http://www.geoplugin.com) is a free-to-use IP-to-location geocoding service.
 * However, they request users of their service to provide a link to their site using
 * something like:
 *
 * <code>
 * <a href="http://www.geoplugin.com/" target="_new" title="geoPlugin for IP geolocation">Geolocation by geoPlugin</a>
 * </code>
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_geocoder_geoplugin extends org_routamc_positioning_geocoder
{
    /**
     * Try geocoding an IP address.
     *
     * @param array $location Parameters to geocode with, conforms to XEP-0080
     * @param array $options Implementation-specific configuration
     * @return array containing geocoded information
     */
    public function geocode(array $location, array $options = array())
    {
        if (!isset($location['ip'])) {
            throw new InvalidArgumentException("No IP address provided");
        }

        // Check that we have a valid IP
        if (!filter_var($location['ip'], FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException("Invalid IP address provided");
        }

        $http_request = new org_openpsa_httplib();
        $json = $http_request->get("http://www.geoplugin.net/json.gp?ip={$location['ip']}");
        if (!$json) {
            throw new RuntimeException("GeoPlugin did not return data");
        }
        // Remove the geoPlugin() callback
        $json = substr($json, 10, -1);
        $geocoded = json_decode($json);

        if (   !$geocoded->geoplugin_latitude
            || !$geocoded->geoplugin_longitude) {
            throw new RuntimeException("GeoPlugin did not return coordinates for IP");
        }

        $location = array(
            'latitude' => (float) $geocoded->geoplugin_latitude,
            'longitude' => (float) $geocoded->geoplugin_longitude,
        );
        $location['accuracy'] = 80;
        $location['source'] = 'geoplugin';

        if (isset($geocoded->geoplugin_countryCode)) {
            $location['country'] = $geocoded->geoplugin_countryCode;
            $location['accuracy'] = 60;
        }

        if (isset($geocoded->geoplugin_city)) {
            $location['city'] = $geocoded->geoplugin_city;
            $location['accuracy'] = 30;
        }

        return $location;
    }
}
