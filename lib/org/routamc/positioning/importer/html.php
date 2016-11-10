<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 */

/**
 * Importer for fetching position data from HTML ICBM meta tags on remote sites
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_importer_html extends org_routamc_positioning_importer
{
    /**
     * Seek users with ICBM account settings set
     *
     * @return Array
     */
    function seek_icbm_users()
    {
        // TODO: With 1.8 we can query parameters more efficiently
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.routamc.positioning:html');
        $qb->add_constraint('name', '=', 'icbm_url');
        $accounts = $qb->execute();
        if (count($accounts) > 0) {
            foreach ($accounts as $account_param) {
                $user = new midcom_db_person($account_param->parentguid);
                $this->get_icbm_location($user, true);
            }
        }
    }

    private function _fetch_icbm_position($url)
    {
        $client = new org_openpsa_httplib();
        $html = $client->get($url);
        $icbm = org_openpsa_httplib_helpers::get_meta_value($html, 'icbm');
        if (strstr($icbm, ',')) {
            $icbm_parts = explode(',', $icbm);
            if (count($icbm_parts) == 2) {
                $latitude = (float) $icbm_parts[0];
                if (   $latitude > 90
                    || $latitude < -90) {
                    // This is no earth coordinate, my friend
                    $this->error = 'POSITIONING_HTML_INCORRECT_LATITUDE';
                    return null;
                }

                $longitude = (float) $icbm_parts[1];
                if (   $longitude > 180
                    || $longitude < -180) {
                    // This is no earth coordinate, my friend
                    $this->error = 'POSITIONING_HTML_INCORRECT_LONGITUDE';
                    return null;
                }

                $position = array
                (
                    'latitude'    => $latitude,
                    'longitude'    => $longitude,
                );
                return $position;
            }
        }
        $this->error = 'POSITIONING_HTML_CONNECTION_NORESULTS';
        return null;
    }

    /**
     * Get ICBM location for a user
     *
     * @param midcom_db_person $user Person to fetch ICBM data for
     * @param boolean $cache Whether to cache the position to a log object
     * @return Array
     */
    function get_icbm_location($user, $cache = true)
    {
        $icbm_url = $user->get_parameter('org.routamc.positioning:html', 'icbm_url');

        if ($icbm_url) {
            $position = $this->_fetch_icbm_position($icbm_url);

            if (is_null($position)) {
                return null;
            }

            if ($cache) {
                $this->import($position, $user->id);
            }

            return $position;
        }
        $this->error = 'POSITIONING_ICBM_NO_URL';

        return null;
    }

    /**
     * Import ICBM log entry. The entries are associative arrays containing
     * all of the following keys:
     *
     * - latitude
     * - longitude
     *
     * @param array $position Log entry in Array format specific to importer
     * @param integer $person_id ID of the person to import logs for
     * @return boolean Indicating success.
     */
    function import(array $position, $person_id)
    {
        $this->log = new org_routamc_positioning_log_dba();
        $this->log->importer = 'html';
        $this->log->person = $person_id;

        $this->log->date = time();
        $this->log->latitude = (float) $position['latitude'];
        $this->log->longitude = (float) $position['longitude'];
        $this->log->altitude = 0;
        $this->log->accuracy = org_routamc_positioning_log_dba::ACCURACY_HTML;

        // Try to create the entry
        $stat = $this->log->create();
        $this->error = midcom_connection::get_error_string();
        return $stat;
    }
}
