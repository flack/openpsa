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
class org_openpsa_relatedto_suspect extends midcom_baseclasses_components_purecode
{
    /**
     * Query all installed components for objects related to given object
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
    public static function find_links_object($object, $defaults = false)
    {
        $ret = array();
        //Copied on purpose TODO: when upgrading to PHP5 make sure this is passed as copy
        $manifests = midcom::get('componentloader')->manifests;
        //Check all installed components
        foreach ($manifests as $component => $manifest)
        {
            if ($component == 'midcom')
            {
                //Skip midcom core
                continue;
            }
            $component_ret = org_openpsa_relatedto_suspect::find_links_object_component($object, $component, $defaults);
            foreach ($component_ret as $linkdata)
            {
                $ret[] = $linkdata;
            }
            unset($component_ret, $linkdata);
        }

        //TODO: Filter out duplicates (not likely but theoretically possible)

        return $ret;
    }


    /**
     * Query all specific component for objects related to given object
     *
     * See org_openpsa_relatedto_suspect::find_links_object() for details
     */
    public static function find_links_object_component($object, $component, $defaults = false)
    {
        $ret = array();

        //Make sure we can load and access the component
        if (midcom::get('componentloader')->load_graceful($component))
        {
            //We could not load the component/interface
            debug_add("could not load component {$component}", MIDCOM_LOG_ERROR);
            return $ret;
        }

        $interface = midcom::get('componentloader')->get_interface_class($component);

        if (!method_exists($interface, 'org_openpsa_relatedto_find_suspects'))
        {
            //Component does not wish to tell us anything
            debug_add("component {$component} does not support querying for suspects", MIDCOM_LOG_INFO);
            return $ret;
        }
        //Get components suspected links
        $interface->org_openpsa_relatedto_find_suspects($object, $defaults, $ret);

        //Filter out existing links
        foreach ($ret as $k => $linkdata)
        {
            if ($guid = $linkdata['link']->check_db(false))
            {
                //Essentially same link already exists in db, remove from returned values
                debug_print_r("found matching link with {$guid} (skipping), our data:", $linkdata['link']);
                unset($ret[$k]);
            }
        }
        reset($ret);

        return $ret;
    }

    /**
     * Helper to fill properties of given $link object from given link object with defaults
     *
     * Tries to be smart about the direction (inbound vs outbound) properties
     */
    function defaults_helper(&$link, $defaults, $component = false, $obj = false)
    {
        $properties = array('fromClass', 'toClass', 'fromGuid', 'toGuid', 'fromComponent', 'toComponent', 'status', 'toExtra', 'toExtra');
        foreach ($properties as $property)
        {
            if (   !empty($defaults->$property)
                && empty($link->$property))
            {
                debug_add("Copying property '{$property}' ('{$defaults->$property}') from defaults");
                $link->$property = $defaults->$property;
            }
        }
        if ($component)
        {
            debug_add('$component given, guessing direction');
            if (   empty($link->toComponent)
                && !empty($link->fromComponent))
            {
                debug_add("Setting property 'toComponent' to '{$component}'");
                $link->toComponent = $component;
            }
            else
            {
                debug_add("Setting property 'fromComponent' to '{$component}'");
                $link->fromComponent = $component;
            }
        }
        if (is_object($obj))
        {
            debug_add('$obj given, guessing direction');
            if (   empty($link->toGuid)
                && !empty($link->fromGuid))
            {
                $link->toClass = get_class($obj);
                $link->toGuid = $obj->guid;
                debug_add("Setting property 'toGuid' to '{$link->toGuid}'");
                debug_add("Setting property 'toClass' to '{$link->toClass}'");
            }
            else
            {
                $link->fromClass = get_class($obj);
                $link->fromGuid = $obj->guid;
                debug_add("Setting property 'fromGuid' to '{$link->fromGuid}'");
                debug_add("Setting property 'fromClass' to '{$link->fromClass}'");
            }
        }
    }
}
?>
