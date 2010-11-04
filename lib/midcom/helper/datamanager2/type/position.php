<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: position.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 position management type.
 *
 * This type allows you to position objects in the Midgard database geographically.
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_position extends midcom_helper_datamanager2_type
{
    var $location = null;
    var $object = null;
    var $relation = 30; // ORG_ROUTAMC_POSITIONING_RELATION_LOCATED

    function _on_initialize()
    {
        return $_MIDCOM->load_library('org.routamc.positioning');
    }

    /**
     * This function loads all known attachments from the storage object. It
     * will leave the field empty in case the storage object is null.
     */
    function convert_from_storage($source)
    {
        if ($this->storage->object === null)
        {
            // We don't have a storage object, skip the rest of the operations.
            $this->location = new org_routamc_positioning_location_dba();
            return;
        }

        //debug_push_class(__CLASS__,__FUNCTION__);
        //debug_print_r("this->storage->object",$this->storage->object);
        //debug_print_r("this->location",$this->location);
        //debug_pop();

        $this->object = new org_routamc_positioning_object($this->storage->object);

        $this->location = $this->object->seek_location_object();
        if (is_null($this->location))
        {
            $this->location = new org_routamc_positioning_location_dba();
        }
    }

    function convert_to_raw()
    {
        return $this->convert_to_csv();
    }

    function convert_to_storage()
    {
        if (   !$this->storage
            || !$this->storage->object)
        {
            return '';
        }

        $this->location->relation = $this->relation;
        if (   isset($this->location->guid)
            && $this->location->guid)
        {
            $this->location->update();
        }
        else
        {
            $this->location->parent = $this->storage->object->guid;
            $this->location->parentclass = $this->storage->object->__midcom_class_name__;

            $this->location->create();
        }

        return '';
    }

    function convert_from_csv ($source)
    {
        // TODO: Not yet supported
        return '';
    }

    function convert_to_csv()
    {
        return "{$this->location->latitude},{$this->location->longitude},{$this->location->altitude}";
    }

    function convert_to_html()
    {
        $result = '';

        $adr_properties = array();
        if ($this->location->description)
        {
            $adr_properties[] = "<span class=\"description\">{$this->location->description}</span>";
        }
        if ($this->location->text)
        {
            $adr_properties[] = "<span class=\"text\">{$this->location->text}</span>";
        }
        if ($this->location->room)
        {
            $adr_properties[] = "<span class=\"room\">{$this->location->room}</span>";
        }
        if ($this->location->street)
        {
            $adr_properties[] = "<span class=\"street-address\">{$this->location->street}</span>";
        }
        if ($this->location->postalcode)
        {
            $adr_properties[] = "<span class=\"postal-code\">{$this->location->postalcode}</span>";
        }
        if ($this->location->city)
        {
            $city = new org_routamc_positioning_city_dba($this->location->city);
            $adr_properties[] = "<span class=\"locality\">{$city->city}</span>";
        }
        if ($this->location->region)
        {
            $adr_properties[] = "<span class=\"region\">{$this->location->region}</span>";
        }
        if ($this->location->country)
        {
            $adr_properties[] = "<span class=\"country-name\">{$this->location->country}</span>";
        }

        if (count($adr_properties) > 0)
        {
            $result .= '<span class="adr">' . implode(', ', $adr_properties) . "</span>\n";
        }

        $latitude_string = org_routamc_positioning_utils::pretty_print_coordinate($this->location->latitude);
        $latitude_string .= ($this->location->latitude > 0) ? " N" : " S";
        $longitude_string = org_routamc_positioning_utils::pretty_print_coordinate($this->location->longitude);
        $longitude_string .= ($this->location->longitude > 0) ? " E" : " W";

        $style = '';
        if (!empty($result))
        {
            $style = ' style="display: none;"';
        }

        $result .= "<span class=\"geo\"{$style}>\n";
        $result .= "    <abbr class=\"latitude\" title=\"{$this->location->latitude}\">{$latitude_string}</abbr>\n";
        $result .= "    <abbr class=\"longitude\" title=\"{$this->location->longitude}\">{$longitude_string}</abbr>\n";
        $result .= "</span>\n";

        // TODO: Add Microformat for civic location

        return $result;
    }
}

?>