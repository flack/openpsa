<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapper class for aerodrome objects
 *
 * <code>
 * $airport = new org_routamc_positioning_aerodrome_dba();
 * $airport->icao = 'FYMG';
 * $airport->iata = 'MQG';
 * $airport->name = 'Midgard Airport';
 * $airport->latitude = -22.083332;
 * $airport->longitude = 17.366667;
 * $airport->create();
 * </code>
 *
 * That's actually a real airport, located in Namibia.
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_aerodrome_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_routamc_positioning_aerodrome';

    /**
     * Human-readable label for cases like Asgard navigation
     */
    public function get_label()
    {
        if (!empty($this->name)) {
            return "{$this->icao} ({$this->name})";
        }
        return $this->icao;
    }

    /**
     * Don't save aerodrome if another aerodrome is in same place or exists with same ICAO
     */
    public function _on_creating()
    {
        if (   $this->longitude
            && $this->latitude) {
            $qb = org_routamc_positioning_aerodrome_dba::new_query_builder();
            $qb->add_constraint('longitude', '=', $this->longitude);
            $qb->add_constraint('latitude', '=', $this->latitude);
            $qb->set_limit(1);
            $matches = $qb->execute_unchecked();
            if (count($matches) > 0) {
                // We don't need to save duplicate entries
                return false;
            }
        }

        if (!empty($this->icao)) {
            $qb = org_routamc_positioning_aerodrome_dba::new_query_builder();
            $qb->add_constraint('icao', '=', $this->icao);
            $qb->set_limit(1);
            $matches = $qb->execute_unchecked();
            if (count($matches) > 0) {
                // We don't need to save duplicate entries
                midcom_connection::set_error(MGD_ERR_DUPLICATE);
                return false;
            }
        }
        return true;
    }
}
