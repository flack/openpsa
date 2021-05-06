<?php
/**
 * @package org.openpsa.relatedto
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Class for finding suspected "related to" links
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_suspect
{
    /**
     * Query all specific component for objects related to given object
     *
     * If optional $defaults (org_openpsa_relatedto_dba object) is given
     * it's used to fill default values for link objects returned.
     *
     * NOTE: Only returns new suspected relations, confirmed/notrelated links are filtered out
     *
     * returns array with the target object and prefilled link object
     *
     * The see org.openpsa.projects for an example of how the component interface method
     * org_openpsa_relatedto_find_suspects() should work.
     */
    public static function find_links_object_component(midcom_core_dbaobject $object, string $component, org_openpsa_relatedto_dba $defaults) : array
    {
        $ret = [];

        //Make sure we can access the component
        if (!midcom::get()->componentloader->is_installed($component)) {
            debug_add("Component {$component} is not installed", MIDCOM_LOG_ERROR);
            return $ret;
        }

        $interface = midcom::get()->componentloader->get_interface_class($component);

        if (!method_exists($interface, 'org_openpsa_relatedto_find_suspects')) {
            //Component does not wish to tell us anything
            debug_add("component {$component} does not support querying for suspects", MIDCOM_LOG_INFO);
            return $ret;
        }
        //Get components suspected links
        $interface->org_openpsa_relatedto_find_suspects($object, $defaults, $ret);

        //Filter out existing links
        foreach ($ret as $k => $linkdata) {
            if ($guid = $linkdata['link']->check_db(false)) {
                //Essentially same link already exists in db, remove from returned values
                debug_print_r("found matching link with {$guid} (skipping), our data:", $linkdata['link']);
                unset($ret[$k]);
            }
        }

        return $ret;
    }

    public static function add_links(midcom_core_querybuilder $qb, string $component, org_openpsa_relatedto_dba $defaults, array &$links_array)
    {
        foreach ($qb->execute() as $object) {
            $links_array[] = [
                'other_obj' => $object,
                'link' => self::defaults_helper($defaults, $component, $object)
            ];
        }
    }

    /**
     * Fill properties of given $link object from given link object with defaults
     *
     * Tries to be smart about the direction (inbound vs outbound) properties
     */
    private static function defaults_helper(org_openpsa_relatedto_dba $defaults, string $component, midcom_core_dbaobject $obj) : org_openpsa_relatedto_dba
    {
        $link = new org_openpsa_relatedto_dba;

        $properties = ['fromClass', 'toClass', 'fromGuid', 'toGuid', 'fromComponent', 'toComponent', 'status', 'toExtra', 'toExtra'];
        foreach ($properties as $property) {
            if (   !empty($defaults->$property)
                && empty($link->$property)) {
                debug_add("Copying property '{$property}' ('{$defaults->$property}') from defaults");
                $link->$property = $defaults->$property;
            }
        }

        if (   empty($link->toComponent)
            && !empty($link->fromComponent)) {
            debug_add("Setting property 'toComponent' to '{$component}'");
            $link->toComponent = $component;
        } else {
            debug_add("Setting property 'fromComponent' to '{$component}'");
            $link->fromComponent = $component;
        }

        if (   empty($link->toGuid)
            && !empty($link->fromGuid)) {
            $link->toClass = get_class($obj);
            $link->toGuid = $obj->guid;
            debug_add("Setting property 'toGuid' to '{$link->toGuid}'");
            debug_add("Setting property 'toClass' to '{$link->toClass}'");
        } else {
            $link->fromClass = get_class($obj);
            $link->fromGuid = $obj->guid;
            debug_add("Setting property 'fromGuid' to '{$link->fromGuid}'");
            debug_add("Setting property 'fromClass' to '{$link->fromClass}'");
        }
        return $link;
    }
}
