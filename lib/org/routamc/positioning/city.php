<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapper class for city objects
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_city_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_routamc_positioning_city';

    /**
     * Human-readable label for cases like Asgard navigation
     */
    public function get_label()
    {
        return $this->city;
    }

    public function get_label_property()
    {
        return 'city';
    }

    /**
     * Don't save city if another city is in same place
     */
    public function _on_creating()
    {
        $qb = org_routamc_positioning_city_dba::new_query_builder();
        $qb->add_constraint('longitude', '=', (double)$this->longitude);
        $qb->add_constraint('latitude', '=', (double)$this->latitude);
        $qb->set_limit(1);
        $matches = $qb->execute_unchecked();
        if (   !empty($matches)
               /* doublecheck */
            && $matches[0]->longitude === $this->longitude
            && $matches[0]->latitude === $this->latitude)
        {
            // We don't need to save duplicate entries
            midcom_connection::set_error(MGD_ERR_DUPLICATE);
            return false;
        }
        return true;
    }

    public static function get_by_name($name)
    {
        if (empty($name))
        {
            return false;
        }
        // Seek by strict city name first
        $qb = org_routamc_positioning_city_dba::new_query_builder();
        $qb->add_constraint('city', 'LIKE', $name);
        $qb->set_limit(1);
        $matches = $qb->execute_unchecked();
        if (count($matches) > 0)
        {
            return $matches[0];
        }

        // Strict name didn't match, seek by alternate names
        $qb = org_routamc_positioning_city_dba::new_query_builder();
        $qb->add_constraint('alternatenames', 'LIKE', "%{$name}%");
        // Most likely we're interested in the biggest city that matches
        $qb->add_order('population', 'DESC');
        $qb->set_limit(1);
        $matches = $qb->execute_unchecked();
        if (count($matches) > 0)
        {
            return $matches[0];
        }

        return false;
    }
}
