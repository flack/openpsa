<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * User geolocation
 *
 * The methods of this class can be used for storing and retrieving location of both authenticated
 * and anonymous users.
 *
 * <b>Simple usage with GeoPlugin IP address geocoding works like the following:</b>
 *
 * <code>
 * <?php
 * // Read location from session or user's location log
 * $user_location = org_routamc_positioning_user::get_location();
 * if (is_null($user_location))
 * {
 *     // No location found, try to geocode based on user IP
 *     $geocoder = org_routamc_positioning_geocoder::create('geoplugin');
 *     $location_parameters = array('ip' => $_SERVER['REMOTE_ADDR']);
 *     $user_location = $geocoder->geocode($location_parameters);
 *     if (!is_null($user_location))
 *     {
 *         // Store geocoded location to session or user's location log
 *         org_routamc_positioning_user::set_location($user_location);
 *     }
 * }
 *
 * if (!is_null($user_location))
 * {
 *     printf('You\'re in %s, %s', $user_location['latitude'], $user_location['longitude']);
 *     // Will print "You're in 60.2345, 25.00456"
 * }
 * </code>
 *
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_user extends midcom_baseclasses_components_purecode
{
    public static function set_location(array $location)
    {
        if (   !isset($location['latitude'])
            || !isset($location['longitude']))
        {
            throw new InvalidArgumentException('No coordinates provided');
        }

        if (midcom::get()->auth->user)
        {
            // Set to user's location log
            return org_routamc_positioning_user::set_location_for_person($location, midcom::get()->auth->user->get_storage());
        }

        // Set to session
        $session = new midcom_services_session();
        return $session->set('org_routamc_positioning_user_location', $location);
    }

    public static function set_location_for_person(array $location, midcom_db_person $person)
    {
        if (   !isset($location['latitude'])
            || !isset($location['longitude']))
        {
            throw new InvalidArgumentException('No coordinates provided');
        }

        $log = new org_routamc_positioning_log_dba();
        $log->person = $person->id;
        $log->latitude = $location['latitude'];
        $log->longitude = $location['longitude'];

        if (isset($location['source']))
        {
            $log->importer = $location['source'];
        }
        if (isset($location['accuracy']))
        {
            $log->accuracy = $location['accuracy'];
        }

        return $log->create();
    }

    public static function get_location($when = null)
    {
        if (midcom::get()->auth->user)
        {
            // Get from user's location log
            return org_routamc_positioning_user::get_location_for_person(midcom::get()->auth->user->get_storage(), $when);
        }

        // Get from session
        $session = new midcom_services_session();
        if (!$session->exists('org_routamc_positioning_user_location'))
        {
            return null;
        }
        return $session->get('org_routamc_positioning_user_location');
    }

    public static function get_location_for_person(midcom_db_person $person, $when = null)
    {
        $person_position = new org_routamc_positioning_person($person);
        return $person_position->get_coordinates($when);
    }
}
