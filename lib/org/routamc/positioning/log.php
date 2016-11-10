<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapper class for user position log entries
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_log_dba extends midcom_core_dbaobject
{
    const ACCURACY_GPS = 10;
    const ACCURACY_HTML = 15;
    const ACCURACY_ADDRESS = 15;
    const ACCURACY_MANUAL = 20;
    const ACCURACY_STREET = 20;
    const ACCURACY_CITY = 30;
    const ACCURACY_IP = 40;

    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_routamc_positioning_log';

    /**
     * Human-readable label for cases like Asgard navigation
     */
    public function get_label()
    {
        return strftime('%x', $this->date) . ' ' . org_routamc_positioning_utils::pretty_print_coordinates($this->latitude, $this->longitude);
    }

    /**
     * Don't save log if previous log is in same place
     */
    public function _on_creating()
    {
        $previous = $this->get_previous();
        if (   $previous
            && round($previous->longitude, 4) == round($this->longitude, 4)
            && round($previous->latitude, 4) == round($this->latitude, 4)
            && $previous->altitude == $this->altitude
            && date('Y-m-d', $previous->date) == date('Y-m-d', $this->date)) {
            // We don't need to save duplicate entries on same day
            debug_add("Not saving log, previous log \"{$previous->guid}\" on same day is in same place.",
                    MIDCOM_LOG_WARN);
            midcom_connection::set_error(MGD_ERR_DUPLICATE);
            return false;
        }
        return true;
    }

    /**
     * Returns the previous log entry by the person
     *
     * @return org_routamc_positioning_log_dba Previous log entry
     */
    function get_previous()
    {
        if (!$this->person) {
            return null;
        }
        $qb = org_routamc_positioning_log_dba::new_query_builder();
        $qb->add_constraint('person', '=', (int)$this->person);
        $qb->add_constraint('date', '<=', (int)$this->date);
        $qb->add_order('date', 'DESC');
        $qb->set_limit(1);
        $matches = $qb->execute_unchecked();
        if (count($matches) > 0) {
            return $matches[0];
        }
        return null;
    }

    /**
     * Returns the next log entry by the person
     *
     * @return org_routamc_positioning_log_dba Next log entry
     */
    function get_next()
    {
        if (!$this->person) {
            return null;
        }
        $qb = org_routamc_positioning_log_dba::new_query_builder();
        $qb->add_constraint('person', '=', (int)$this->person);
        $qb->add_constraint('date', '>', (int)$this->date);
        $qb->add_order('date', 'ASC');
        $qb->set_limit(1);
        $matches = $qb->execute_unchecked();
        if (count($matches) > 0) {
            return $matches[0];
        }
        return null;
    }

    private function _claim_location_entries()
    {
        $previous = $this->get_previous();

        if (is_object($previous)) {
            $qb = org_routamc_positioning_location_dba::new_query_builder();

            // Find locations reported to previous log but after
            // this log's date
            $qb->add_constraint('log', '=', $previous->id);
            $qb->add_constraint('date', '>=', $this->date);

            $matches = $qb->execute();
            foreach ($matches as $location) {
                // Switch the location to point to this log
                $location->log = $this->id;
                $location->latitude = $this->latitude;
                $location->longitude = $this->longitude;
                $location->altitude = $this->altitude;
                $location->update();
            }
        }
    }

    function get_city_string()
    {
        return org_routamc_positioning_utils::pretty_print_location($this->latitude, $this->longitude);
    }

    /**
     * Checks after log creation, switch cached location entries of objects
     * made after the previous log entry by the person and before this one.
     */
    public function _on_created()
    {
        $this->_claim_location_entries();
    }
}
