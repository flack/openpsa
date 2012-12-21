<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 */

/**
 * Importer for fetching position data for Yahoo! Fire Eagle users
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_importer_fireeagle extends org_routamc_positioning_importer
{
    /**
     * Seek users with Plazes account settings set
     *
     * @return Array
     */
    function seek_fireeagle_users()
    {
        // TODO: With 1.8 we can query parameters more efficiently
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'net.yahoo.fireeagle');
        $qb->add_constraint('name', '=', 'access_key');
        $accounts = $qb->execute();
        if (count($accounts) > 0)
        {
            foreach ($accounts as $account_param)
            {
                $user = new midcom_db_person($account_param->parentguid);
                $this->get_fireeagle_location($user, true);
            }
        }
    }

    private function _fetch_fireeagle_positions($fireeagle_access_key, $fireeagle_access_secret)
    {
        $position = array();

        require_once(MIDCOM_ROOT . '/external/fireeagle.php');

        $fireeagle = new FireEagle($this->_config->get('fireeagle_consumer_key'), $this->_config->get('fireeagle_consumer_secret'), $fireeagle_access_key, $fireeagle_access_secret);

        // Note: this must be C so we get floats correctly from JSON. See http://bugs.php.net/bug.php?id=41403
        setlocale(LC_NUMERIC, 'C');

        $user_data = $fireeagle->user();
        if (   !$user_data
            || !$user_data->user
            || empty($user_data->user->location_hierarchy))
        {
            return $position;
        }

        $best_position = $user_data->user->location_hierarchy[0];

        switch ($best_position->level_name)
        {
            case 'exact':
                $position['accuracy'] = 10;
                break;
            case 'postal':
                $position['accuracy'] = 20;
                break;
            case 'city':
                $position['accuracy'] = 30;
                break;
            default:
                $position['accuracy'] = 60;
                break;
        }

        $position['latitude'] = $best_position->latitude;
        $position['longitude'] = $best_position->longitude;

        $position['date'] = strtotime($best_position->located_at);

        return $position;
    }

    /**
     * Get fireeagle location for a user
     *
     * @param midcom_db_person $user Person to fetch Plazes data for
     * @param boolean $cache Whether to cache the position to a log object
     * @return Array
     */
    function get_fireeagle_location($user, $cache = true)
    {
        $fireeagle_access_key = $user->get_parameter('net.yahoo.fireeagle', 'access_key');
        $fireeagle_access_secret = $user->get_parameter('net.yahoo.fireeagle', 'access_secret');

        if (   $fireeagle_access_key
            && $fireeagle_access_secret)
        {
            $position = $this->_fetch_fireeagle_positions($fireeagle_access_key, $fireeagle_access_secret);

            if (   is_null($position)
                && !is_array($position))
            {
                return null;
            }

            $this->import($position, $user->id);
            return $position;
        }
        else
        {
            $this->error = 'POSITIONING_FIREEAGLE_NO_ACCOUNT';
        }

        return null;
    }

    /**
     * Import fireeagle log entry. The entries are associative arrays containing
     * all of the following keys:
     *
     * - latitude
     * - longitude
     *
     * @param array $log Log entry in Array format specific to importer
     * @param integer $person_id ID of the person to import logs for
     * @return boolean Indicating success.
     */
    function import($position, $person_id)
    {
        $this->log = new org_routamc_positioning_log_dba();
        $this->log->importer = 'fireeagle';
        $this->log->person = $person_id;

        $this->log->date = (int) $position['date'];
        $this->log->latitude = (float) $position['latitude'];
        $this->log->longitude = (float) $position['longitude'];
        $this->log->altitude = 0;
        $this->log->accuracy = $position['accuracy'];

        // Try to create the entry
        $stat = $this->log->create();

        $this->error = midcom_connection::get_error_string();
        return $stat;
    }
}
