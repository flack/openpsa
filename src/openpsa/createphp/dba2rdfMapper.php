<?php
/**
 * @package openpsa.createphp
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace openpsa\createphp;

use Midgard\CreatePHP\RdfMapperInterface;
use Midgard\CreatePHP\Entity\Controller;
use Midgard\CreatePHP\Entity\PropertyInterface;
use Midgard\CreatePHP\Type\TypeInterface;

/**
 * Default RdfMapper implementation for MidCOM DBA
 *
 * @package openpsa.createphp
 */
class dba2rdfMapper implements RdfMapperInterface
{
    public function getBySubject($identifier)
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

    public function createSubject($object)
    {
        return \midcom::get('permalinks')->create_permalink($object->guid);
    }

    public function prepareObject(TypeInterface $controller, $parent = null)
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
        if (empty($config['parentfield']))
        {
            throw new \midcom_error('parentfield was not defined in config');
        }
        $parentfield = $config['parentfield'];
        // if storage is not defined, we assume it's the same as object
        if (empty($config['storage']))
        {
            $storage = get_class($object);
        }
        else
        {
            $storage = $config['storage'];
        }

        $reflector = new \midgard_reflection_property(\midcom_helper_reflector::resolve_baseclass($storage));

        if (!$reflector->is_link($parentfield))
        {
            throw new \midcom_error('could not determine storage class');
        }

        $qb = call_user_func(array($storage, 'new_query_builder'));

        $qb->add_constraint($parentfield, '=', $object->id);
        // order the children by their score values
        $qb->add_order('score', 'ASC');
        return $qb->execute();
    }

    public function setPropertyValue($object, PropertyInterface $node, $value)
    {
        $config = $node->getConfig();

        if (!array_key_exists('dba_name', $config))
        {
            throw new \midcom_error('Could not find property mapping for ' . $node->get_identifier());
        }

        $object->{$config['dba_name']} = $value;
        return $object;
    }

    public function getPropertyValue($object, PropertyInterface $node)
    {
        $config = $node->getConfig();

        if (!array_key_exists('dba_name', $config))
        {
            $fieldname = $node->getIdentifier();
        }
        else
        {
            $fieldname = $config['dba_name'];
        }
        if (!\midcom::get('dbfactory')->property_exists($object, $fieldname))
        {
            throw new \midcom_error('Could not find property mapping for ' . $fieldname);
        }

        return $object->$fieldname;
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