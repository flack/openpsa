<?php
/**
 * @package openpsa.createphp
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace openpsa\createphp;

use Midgard\CreatePHP\RdfMapper;
use Midgard\CreatePHP\Entity\Controller;
use Midgard\CreatePHP\Entity\Property;

/**
 * Default RdfMapper implementation for MidCOM DBA
 *
 * @package openpsa.createphp
 */
class dba2rdfMapper implements RdfMapper
{
    public function getByIdentifier($identifier)
    {
        $identifier = str_replace("{$GLOBALS['midcom_config']['midcom_site_url']}midcom-permalink-", '', $identifier);
        $identifier = trim($identifier, '<>');

        try
        {
            return \midcom::get('dbfactory')->get_object_by_guid($identifier);
        }
        catch (\midcom_error $e)
        {
            $e->log();
            return false;
        }
    }

    public function createIdentifier($object)
    {
        return \midcom_services_permalinks::create_permalink($object->guid);
    }

    public function prepareObject(Controller $controller, $parent = null)
    {
        $config = $controller->getConfig();
        $class = $config['storage'];
        $object = new $class;

        if (null !== $parent)
        {
            $baseclass = \midcom_helper_reflector::resolve_baseclass($class);
            $reflector = new \midgard_reflection_property($baseclass);

            $up_property = \midgard_object_class::get_property_up($baseclass);
            if (!empty($up_property))
            {
                $target_property = $reflector->get_link_target($up_property);
                $target_class = $reflector->get_link_name($up_property);
                if (\midcom_helper_reflector::resolve_baseclass($parent) === $target_class)
                {
                    $object->{$up_property} = $parent->{$target_property};
                }
            }

            $parent_property = \midgard_object_class::get_property_parent($baseclass);
            if (!empty($parent_property))
            {
                $target_property = $reflector->get_link_target($parent_property);
                $target_class = $reflector->get_link_name($parent_property);
                if (\midcom_helper_reflector::resolve_baseclass($parent) === $target_class)
                {
                    $object->{$parent_property} = $parent->{$target_property};
                }
                else
                {
                    $object->{$parent_property} = $parent->{$parent_property};
                }
            }
        }
        return $object;
    }

    /**
     *
     * @param mixed $object
     * @param array $config
     * @return array
     */
    public function getChildren($object, array $config)
    {
        $class = $config['storage'];
        $qb = call_user_func(array($class, 'new_query_builder'));

        // match children through parent's up field
        $qb->add_constraint($config['parentfield'], '=', $object->id);
        // order the children by their score values
        $qb->add_order('score', 'ASC');
        return $qb->execute();
    }

    public function setPropertyValue($object, Property $node, $value)
    {
        $config = $node->getConfig();

        if (!array_key_exists('dba_name', $config))
        {
            throw new midcom_error('Could not find property mapping for ' . $node->get_identifier());
        }

        $object->{$config['dba_name']} = $value;
        return $object;
    }

    public function getPropertyValue($object, Property $node)
    {
        $config = $node->getConfig();

        if (!array_key_exists('dba_name', $config))
        {
            throw new midcom_error('Could not find property mapping for ' . $node->get_identifier());
        }

        return $object->{$config['dba_name']};
    }

    public function isEditable($object)
    {
        return $object->can_do('midgard:update');
    }

    public function store($object)
    {
        if (empty($object->id))
        {
            return $object->create();
        }
        return $object->update();
    }
}
?>