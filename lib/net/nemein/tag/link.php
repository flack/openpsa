<?php
/**
 * @package net.nemein.tag
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @package net.nemein.tag
 */
class net_nemein_tag_link_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'net_nemein_tag_link';

    public $_use_activitystream = false;
    public $_use_rcs = false;

    public function get_parent_guid_uncached()
    {
        if (empty($this->fromGuid)) {
            return null;
        }
        $class = $this->fromClass;
        if (!class_exists($class)) {
            debug_add("Class '{$class}' is missing", MIDCOM_LOG_ERROR);
            return null;
        }
        $parent = new $class($this->fromGuid);
        if (empty($parent->guid)) {
            return null;
        }
        return $parent->guid;
    }

    public function get_label()
    {
        $mc = net_nemein_tag_tag_dba::new_collector('id', $this->tag);
        $tag_guids = $mc->get_values('tag');

        foreach ($tag_guids as $guid) {
            return net_nemein_tag_handler::tag_link2tagname($guid, $this->value, $this->context);
        }
        return $this->guid;
    }

    private function _sanity_check()
    {
        if (empty($this->fromGuid) || empty($this->fromClass) || empty($this->tag)) {
            debug_add("Sanity check failed with tag #{$this->tag}", MIDCOM_LOG_WARN);
            return false;
        }
        $qb = net_nemein_tag_link_dba::new_query_builder();
        if ($this->id) {
            $qb->add_constraint('id', '<>', $this->id);
        }
        $qb->add_constraint('fromGuid', '=', $this->fromGuid);
        $qb->add_constraint('tag', '=', $this->tag);
        $qb->add_constraint('context', '=', $this->context);

        if ($qb->count_unchecked() > 0) {
            debug_add("Duplicate check failed with tag #{$this->tag}", MIDCOM_LOG_WARN);
            return false;
        }
        return true;
    }

    public function _on_creating()
    {
        return $this->_sanity_check();
    }

    public function _on_created()
    {
        if ($this->context == 'geo') {
            $this->_geotag();
        }
    }

    public function _on_updating()
    {
        return $this->_sanity_check();
    }

    public function _on_updated()
    {
        if ($this->context == 'geo') {
            $this->_geotag();
        }
    }

    /**
     * Handle storing Flickr-style geo tags to org.routamc.positioning
     * storage should be to org_routamc_positioning_location_dba object
     * with relation org_routamc_positioning_location_dba::RELATION_IN
     *
     * @return boolean
     */
    private function _geotag()
    {
        if (!midcom::get()->componentloader->is_installed('org.routamc.positioning')) {
            return false;
        }

        // Get all "geo" tags of the object
        $object = midcom::get()->dbfactory->get_object_by_guid($this->fromGuid);
        $geotags = net_nemein_tag_handler::get_object_machine_tags_in_context($object, 'geo');

        $position = array(
            'longitude' => null,
            'latitude'  => null,
            'altitude'  => null,
        );

        foreach ($geotags as $key => $value) {
            switch ($key) {
                case 'lon':
                case 'lng':
                case 'long':
                    $position['longitude'] = $value;
                    break;

                case 'lat':
                    $position['latitude'] = $value;
                    break;

                case 'alt':
                    $position['altitude'] = $value;
                    break;
            }
        }

        if (   is_null($position['longitude'])
            || is_null($position['latitude'])) {
            // Not enough information for positioning, we need both lon and lat
            return false;
        }

        $object_location = new org_routamc_positioning_location_dba();
        $object_location->relation = org_routamc_positioning_location_dba::RELATION_IN;
        $object_location->parent = $this->fromGuid;
        $object_location->parentclass = $this->fromClass;
        $object_location->parentcomponent = $this->fromComponent;
        $object_location->date = $this->metadata->published;
        $object_location->longitude = $position['longitude'];
        $object_location->latitude = $position['latitude'];
        $object_location->altitude = $position['altitude'];

        return $object_location->create();
    }

    /**
     * By default all authenticated users should be able to do
     * whatever they wish with tag objects, later we can add
     * restrictions on object level as necessary.
     */
    public function get_class_magic_default_privileges()
    {
        $privileges = parent::get_class_magic_default_privileges();
        $privileges['USERS']['midgard:create']  = MIDCOM_PRIVILEGE_ALLOW;
        $privileges['USERS']['midgard:update']  = MIDCOM_PRIVILEGE_ALLOW;
        $privileges['USERS']['midgard:read']    = MIDCOM_PRIVILEGE_ALLOW;
        return $privileges;
    }
}
