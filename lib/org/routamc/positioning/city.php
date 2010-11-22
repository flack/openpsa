<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: city.php 26123 2010-05-16 19:03:49Z bergie $
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
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_routamc_positioning_city';
   
    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }
    
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
     * @return org_routamc_positioning_country_dba Country the city is in
     */
    function get_parent_guid_uncached()
    {
        if ($this->country)
        {
            $qb = org_routamc_positioning_country_dba::new_query_builder();
            $qb->add_constraint('code', '=', $this->country);
            $countries = $qb->execute();
            if (count($countries) == 0)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Could not load Country ID {$this->country} from the database, aborting.",
                    MIDCOM_LOG_INFO);
                debug_pop();
                return null;
            }
            return $countries[0]->guid;
        }

        return null;
    }

    /**
     * Don't save city if another city is in same place
     */
    function _on_creating()
    {
        $qb = org_routamc_positioning_city_dba::new_query_builder();
        $qb->add_constraint('longitude', '=', (double)$this->longitude);
        $qb->add_constraint('latitude', '=', (double)$this->latitude);
        $qb->set_limit(1);
        $matches = $qb->execute_unchecked();
        if (   !empty($matches)
               /* doublecheck */
            && $matches[0]->longitude === $this->longitude
            && $matches[0]->latitude === $this->latitude
            )
        {
            // We don't need to save duplicate entries
            midcom_connection::set_error(MGD_ERR_DUPLICATE);
            return false;
        }
        return parent::_on_creating();
    }

    static function get_by_name($name)
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
?>
