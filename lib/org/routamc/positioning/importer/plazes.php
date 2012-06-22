<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 */

/**
 * Importer for fetching position and activity data for Plazes users
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_importer_plazes extends org_routamc_positioning_importer
{
    /**
     * Initializes the class. The real startup is done by the initialize() call.
     */
    public function __construct()
    {
         parent::__construct();
    }

    /**
     * Seek users with Plazes account settings set
     *
     * @return Array
     */
    function seek_plazes_users()
    {
        // TODO: With 1.8 we can query parameters more efficiently
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.routamc.positioning:plazes');
        $qb->add_constraint('name', '=', 'username');
        $accounts = $qb->execute();
        if (count($accounts) > 0)
        {
            foreach ($accounts as $account_param)
            {
                $user = new midcom_db_person($account_param->parentguid);
                $this->get_plazes_location($user, true);
            }
        }
    }

    private function _get_plazes_userid($user, $plazes_username, $plazes_password, $cache)
    {
        $client = new org_openpsa_httplib();
        $xml = $client->get('http://plazes.com/me.xml', 'User-agent: device: midgard', $plazes_username, $plazes_password);

        $simplexml = simplexml_load_string($xml);

        if (!isset($simplexml->id))
        {
            return null;
        }

        $user_id = (int) $simplexml->id;

        if ($cache)
        {
            $user->set_parameter('org.routamc.positioning:plazes', 'user_id', $user_id);
        }

        return $user_id;
    }

    private function _fetch_plazes_positions($plazes_username, $plazes_password)
    {
        $positions = array();

        $client = new org_openpsa_httplib();
        $xml = $client->get("http://plazes.com/users/{$plazes_username}/past_activities.xml", 'User-agent: device: midgard', $plazes_username, $plazes_password);
        $simplexml = simplexml_load_string($xml);

        if (!isset($simplexml->activity))
        {
            return null;
        }

        foreach ($simplexml->activity as $activity)
        {
            if (   !isset($activity->plaze)
                || !isset($activity->plaze->latitude))
            {
                // No location, skip
                continue;
            }

            $positions[] = array
            (
                'plaze' => (int) $activity->plaze->id,
                'latitude' => (float) $activity->plaze->latitude,
                'longitude' => (float) $activity->plaze->longitude,
                'country' => (string) $activity->plaze->country_code,
                'city' => (string) $activity->plaze->city,
                'date' => strtotime((string) $activity->scheduled_at),
            );
        }

        return $positions;
    }

    /**
     * Get plazes location for a user
     *
     * @param midcom_db_person $user Person to fetch Plazes data for
     * @param boolean $cache Whether to cache the position to a log object
     * @return Array
     */
    function get_plazes_location($user, $cache = true)
    {
        $plazes_username = $user->get_parameter('org.routamc.positioning:plazes', 'username');
        $plazes_password = $user->get_parameter('org.routamc.positioning:plazes', 'password');

        if (   $plazes_username
            && $plazes_password)
        {
            $positions = $this->_fetch_plazes_positions($plazes_username, $plazes_password);

            if (   is_null($positions)
                && !is_array($positions))
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
            $this->error = 'POSITIONING_PLAZES_NO_ACCOUNT';
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
        $this->log->importer = 'plazes';
        $this->log->person = $person_id;

        $this->log->date = (int) $position['date'];
        $this->log->latitude = (float) $position['latitude'];
        $this->log->longitude = (float) $position['longitude'];
        $this->log->altitude = 0;
        $this->log->accuracy = ORG_ROUTAMC_POSITIONING_ACCURACY_PLAZES;

        // Try to create the entry
        $stat = $this->log->create();

        $this->log->parameter('org.routamc.positioning:plazes', 'plaze_key', $position['plaze']);

        $this->error = midcom_connection::get_error_string();
        return $stat;
    }
}
