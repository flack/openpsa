<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 */

/**
 * Importer for fetching position data from GeoRSS feeds on remote sites
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_importer_georss extends org_routamc_positioning_importer
{
    /**
     * Initializes the class. The real startup is done by the initialize() call.
     */
    public function __construct()
    {
         parent::__construct();

         midcom::get('componentloader')->load_library('net.nemein.rss');
    }

    /**
     * Seek users with Plazes account settings set
     *
     * @return Array
     */
    function seek_georss_users()
    {
        // TODO: With 1.8 we can query parameters more efficiently
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.routamc.positioning:georss');
        $qb->add_constraint('name', '=', 'georss_url');
        $accounts = $qb->execute();
        if (count($accounts) > 0)
        {
            foreach ($accounts as $account_param)
            {
                $user = new midcom_db_person($account_param->parentguid);
                $this->get_georss_location($user, true);
            }
        }
    }

    private function _fetch_georss_position($url)
    {
        $rss_content = net_nemein_rss_fetch::raw_fetch($url);
        if (isset($rss_content->items))
        {
            foreach ($rss_content->items as $item)
            {
                $latitude = null;
                $longitude = null;

                if (   array_key_exists('georss', $item)
                    && array_key_exists('point', $item['georss']))
                {
                    // GeoRSS Simple format
                    $geo_point = explode(' ', $item['georss']['point']);
                    if (count($geo_point) != 2)
                    {
                        // Somehow broken point
                        continue;
                    }

                    $latitude = (float) $geo_point[0];
                    $longitude = (float) $geo_point[1];
                }
                if (   array_key_exists('geo', $item)
                    && array_key_exists('lat', $item['geo'])
                    && array_key_exists('lon', $item['geo']))
                {
                    // W3C geo format
                    $latitude = (float) $item['geo']['lat'];
                    $longitude = (float) $item['geo']['lon'];
                }

                if (   !is_null($latitude)
                    && !is_null($longitude))
                {
                    if (   $latitude > 90
                        || $latitude < -90)
                    {
                        // This is no earth coordinate, my friend
                        $this->error = 'POSITIONING_GEORSS_INCORRECT_LATITUDE';
                        // Skip to next
                        continue;
                    }

                    if (   $longitude > 180
                        || $longitude < -180)
                    {
                        // This is no earth coordinate, my friend
                        $this->error = 'POSITIONING_GEORSS_INCORRECT_LONGITUDE';
                        // Skip to next
                        continue;
                    }

                    if (!isset($item['date_timestamp']))
                    {
                        $item['date_timestamp'] = time();
                    }

                    $position = array
                    (
                        'latitude'    => $latitude,
                        'longitude'   => $longitude,
                        'time'        => $item['date_timestamp'],
                    );

                    // We're happy with the first proper match
                    return $position;
                }
            }
        }
        $this->error = 'POSITIONING_GEORSS_CONNECTION_NORESULTS';
        return null;
    }

    /**
     * Get plazes location for a user
     *
     * @param midcom_db_person $user Person to fetch Plazes data for
     * @param boolean $cache Whether to cache the position to a log object
     * @return Array
     */
    function get_georss_location($user, $cache = true)
    {
        $georss_url = $user->parameter('org.routamc.positioning:georss', 'georss_url');

        if ($georss_url)
        {
            $position = $this->_fetch_georss_position($georss_url);

            if (is_null($position))
            {
                return null;
            }

            if ($cache)
            {
                $this->import($position, $user->id);
            }

            return $position;
        }
        else
        {
            $this->error = 'POSITIONING_ICBM_NO_URL';
        }

        return null;
    }

    /**
     * Import plazes log entry. The entries are associative arrays containing
     * all of the following keys:
     *
     * - latitude
     * - longitude
     *
     * @param Array $log Log entry in Array format specific to importer
     * @param integer $person_id ID of the person to import logs for
     * @return boolean Indicating success.
     */
    function import($position, $person_id)
    {
        $this->log = new org_routamc_positioning_log_dba();
        $this->log->importer = 'georss';
        $this->log->person = $person_id;

        if (is_null($position['time']))
        {
            $position['time'] = time();
        }
        $this->log->date = $position['time'];

        $this->log->latitude = (float) $position['latitude'];
        $this->log->longitude = (float) $position['longitude'];
        $this->log->altitude = 0;
        $this->log->accuracy = ORG_ROUTAMC_POSITIONING_ACCURACY_HTML;

        // Try to create the entry
        $stat = $this->log->create();
        $this->error = midcom_connection::get_error_string();
        return $stat;
    }
}
