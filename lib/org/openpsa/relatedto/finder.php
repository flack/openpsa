<?php
/**
 * @package org.openpsa.relatedto
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Find relatedto suspects
 *
 * @package org.openpsa.relatedto
 */
abstract class org_openpsa_relatedto_finder
{
    abstract public function process();

    protected function count_links(string $guid, $classes, string $direction) : int
    {
        // Do no seek if we already have confirmed links
        $mc = new org_openpsa_relatedto_collector($this->event->guid, $classes, $direction);
        $mc->add_constraint('status', '=', org_openpsa_relatedto_dba::CONFIRMED);
        return count($mc->get_related_guids());
    }

    protected function prepare_links(midcom_core_querybuilder $qb, string $component, org_openpsa_relatedto_dba $defaults) : array
    {
        $links_array = [];
        foreach ($qb->execute() as $object) {
            $links_array[] = [
                'other_obj' => $object,
                'link' => self::defaults_helper($defaults, $component, $object)
            ];
        }
        return $links_array;
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
            if (!empty($defaults->$property)) {
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

    protected function save(array $suspect_links)
    {
        foreach ($suspect_links as $linkdata) {
            if ($linkdata['link']->create()) {
                debug_add("saved link to {$linkdata['other_obj']->guid} (link id #{$linkdata['link']->id})", MIDCOM_LOG_INFO);
            } else {
                debug_add("could not save link to {$linkdata['other_obj']->guid}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        }
    }

    /**
     * Returns a defaults template for relatedto objects
     */
    protected function suspect_defaults(midcom_core_dbaobject $object, string $component, string $direction = 'incoming') : org_openpsa_relatedto_dba
    {
        $prefix = $direction == 'incoming' ? 'to' : 'from';

        $link_def = new org_openpsa_relatedto_dba();
        $link_def->{$prefix . 'Component'} = $component;
        $link_def->{$prefix . 'Guid'} = $object->guid;
        $link_def->{$prefix . 'Class'} = get_class($object);
        $link_def->status = org_openpsa_relatedto_dba::SUSPECTED;
        return $link_def;
    }
}