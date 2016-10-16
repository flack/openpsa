<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapper class for access to cached object locations
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_location_dba extends midcom_core_dbaobject
{
    const RELATION_IN = 10;
    const RELATION_ABOUT = 20;
    const RELATION_LOCATED = 30;

    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_routamc_positioning_location';

    /**
     * Human-readable label for cases like Asgard navigation
     */
    public function get_label()
    {
        if ($this->parent)
        {
            $parent = $this->get_parent();
            if ($parent)
            {
                $label = $parent->guid;
                if (isset($parent->title))
                {
                    $label = $parent->title;
                }
                elseif (method_exists($parent, 'get_label'))
                {
                    $label = $parent->get_label();
                }
                return "{$this->parentclass} {$label}";
            }
        }
        return "{$this->parentclass} #{$this->id}";
    }

    /**
     * Returns the object GUID the location is stored for
     *
     * @return string Parent GUID or null if there is none
     */
    public function get_parent_guid_uncached()
    {
        return $this->parent;
    }

    /**
     * Returns the object GUID the location is stored for
     *
     * @return string Parent GUID or null if there is none
     */
    public static function get_parent_guid_uncached_static($guid, $classname = __CLASS__)
    {
        $mc = new midgard_collector('org_routamc_positioning_location', 'guid', $guid);
        $mc->set_key_property('parent');
        $mc->execute();
        $link_values = $mc->list_keys();
        if (empty($link_values))
        {
            return null;
        }

        return key($link_values);
    }

    /**
     * Checks after location cache creation
     */
    public function _on_created()
    {
        if (   !$this->log
            && $this->relation == self::RELATION_IN)
        {
            // This location entry is defined as being made in a location,
            // but is stored to object directly without corresponding log,
            // create one.
            // This situation can happen for example when importing images
            // that have EXIF geo tags set
            $object = $this->get_parent();
            $log = new org_routamc_positioning_log();
            $log->date = $this->date;
            // TODO: Use 1.8 metadata authors instead?
            $log->person = $object->metadata->creator;
            $log->latitude = $this->latitude;
            $log->longitude = $this->longitude;
            $log->altitude = $this->altitude;
            $log->importer = 'objectlocation';
            // Usually the positions based on objects are manual, except in
            // case of GPS-equipped cameras etc. We still need to figure
            // out how to handle those.
            $log->accuracy = org_routamc_positioning_log_dba::ACCURACY_MANUAL;
            $log->create();
        }
    }
}
