<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 */

/**
 * Importer for fetching position data from the instamapper service
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_importer_instamapper extends org_routamc_positioning_importer
{
    /**
     * Initializes the class. The real startup is done by the initialize() call.
     */
    public function __construct()
    {
        parent::__construct();
        $_MIDCOM->load_library('org.openpsa.httplib');
    }

    /**
     * Seek users with instamapper API key set
     *
     * @return Array
     */
    function seek_instamapper_users()
    {
        // TODO: With 1.8 we can query parameters more efficiently
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.routamc.positioning:instamapper');
        $qb->add_constraint('name', '=', 'api_key');
        $accounts = $qb->execute();
        if (count($accounts) > 0)
        {
            foreach ($accounts as $account_param)
            {
                $user = new midcom_db_person($account_param->parentguid);
                if (!$user->guid)
                {
                    continue;
                }
                $this->get_instamapper_location($user, true);
            }
        }
    }

    private function _fetch_instamapper_positions($api_key)
    {
        $url = "http://www.instamapper.com/api?action=getPositions&key={$api_key}&num=10";
        $client = new org_openpsa_httplib();
        $csv = $client->get($url);

        if (!$csv)
        {
            $this->error = 'POSITIONING_INSTAMAPPER_CONNECTION_NORESULTS';
            return null;
        }

        $lines = explode("\n", $csv);
        $positions = array();
        foreach ($lines as $line)
        {
            if (strpos($line, ',') === false)
            {
                continue;
            }

            $position_data = explode(',', $line);
            $positions[] = array
            (
                'device' => $position_data[0],
                'device_label' => $position_data[1],
                'time' => date('c', $position_data[2]),
                'latitude' => $position_data[3],
                'longitude' => $position_data[4],
                'altitude' => (int) $position_data[5],
                'speed' => $position_data[6],
                'bearing' => $position_data[7],
            );
        }

        // Return latest first
        return array_reverse($positions);
    }

    /**
     * Get instamapper location for a user
     *
     * @param midcom_db_person $user Person to fetch Plazes data for
     * @param boolean $cache Whether to cache the position to a log object
     * @return Array
     */
    function get_instamapper_location($user, $cache = true)
    {
        $instamapper_url = trim($user->parameter('org.routamc.positioning:instamapper', 'api_key'));

        if ($instamapper_url)
        {
            $positions = $this->_fetch_instamapper_positions($instamapper_url);

            if (   is_null($positions)
                || empty($positions))
            {
                return null;
            }

            if ($cache)
            {
                foreach ($positions as $position)
                {
                    $this->import($position, $user->id);
                }
            }

            return $positions[0];
        }
        else
        {
            $this->error = 'POSITIONING_INSTAMAPPER_NO_APIKEY';
        }

        return null;
    }

    /**
     * Import instamapper log entry. The entries are associative arrays containing
     * all of the following keys:
     *
     * - latitude
     * - longitude
     * - device code
     * - time
     *
     * @param Array $log Log entry in Array format specific to importer
     * @param integer $person_id ID of the person to import logs for
     * @return boolean Indicating success.
     */
    function import($position, $person_id)
    {
        $this->log = new org_routamc_positioning_log_dba();
        $this->log->importer = 'instamapper';
        $this->log->person = $person_id;

        $this->log->date = strtotime($position['time']);

        $this->log->latitude = (float) $position['latitude'];
        $this->log->longitude = (float) $position['longitude'];
        $this->log->altitude = $position['altitude'];
        $this->log->bearing = $position['bearing'];
        $this->log->accuracy = ORG_ROUTAMC_POSITIONING_ACCURACY_GPS;

        // Try to create the entry
        $stat = $this->log->create();

        if ($stat)
        {
            $this->log->parameter('com.instamapper', 'device', $position['device']);
            $this->log->parameter('com.instamapper', 'device_label', $position['device_label']);
            $this->log->parameter('com.instamapper', 'speed', $position['speed']);
        }

        $this->error = midcom_connection::get_error_string();
        return $stat;
    }
}
