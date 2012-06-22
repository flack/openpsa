<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 */

/**
 * Importer for fetching position data for Qaiku users
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_importer_qaiku extends org_routamc_positioning_importer
{
    /**
     * Initializes the class. The real startup is done by the initialize() call.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Seek users with Qaiku API key set
     *
     * @return Array
     */
    function seek_qaiku_users()
    {
        // TODO: With 1.8 we can query parameters more efficiently
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.routamc.statusmessage:qaiku');
        $qb->add_constraint('name', '=', 'apikey');
        $accounts = $qb->execute();
        if (count($accounts) > 0)
        {
            foreach ($accounts as $account_param)
            {
                $user = new midcom_db_person($account_param->parentguid);
                $this->get_qaiku_location($user, true);
            }
        }
    }

    private function _fetch_qaiku_positions($qaiku_apikey)
    {
        $positions = array();
        $client = new org_openpsa_httplib();
        $start_date = urlencode('2009-11-26 09:00:00');
        $json = $client->get("http://www.qaiku.com/api/statuses/user_timeline.json?apikey={$qaiku_apikey}&since={$start_date}");
        $statuses = json_decode($json);
        foreach ($statuses as $status)
        {
            if (   !isset($status->geo->coordinates)
                || empty($status->geo->coordinates))
            {
                // No location, skip
                continue;
            }

            $positions[] = array
            (
                'qaiku' => $status->id,
                'latitude' => (float) $status->geo->coordinates[1],
                'longitude' => (float) $status->geo->coordinates[0],
                'date' => strtotime($status->created_at),
            );
        }

        return $positions;
    }

    /**
     * Get qaiku location for a user
     *
     * @param midcom_db_person $user Person to fetch Qaiku data for
     * @param boolean $cache Whether to cache the position to a log object
     * @return Array
     */
    function get_qaiku_location($user, $cache = true)
    {
        $qaiku_apikey = $user->get_parameter('org.routamc.statusmessage:qaiku', 'apikey');
        if ($qaiku_apikey)
        {
            $positions = $this->_fetch_qaiku_positions(trim($qaiku_apikey));

            if (empty($positions))
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
            $this->error = 'POSITIONING_QAIKU_NO_APIKEY';
        }

        return null;
    }

    /**
     * Import qaiku log entry. The entries are associative arrays containing
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
        $this->log->importer = 'qaiku';
        $this->log->person = $person_id;
        $this->log->date = $position['date'];
        $this->log->latitude = $position['latitude'];
        $this->log->longitude = $position['longitude'];

        // Try to create the entry
        $stat = $this->log->create();

        $this->log->parameter('org.routamc.positioning:qaiku', 'id', $position['qaiku']);

        $this->error = midcom_connection::get_error_string();
        return $stat;
    }
}
