<?php
/**
 * @package openpsa.createphp
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace openpsa\createphp;

use Midgard\CreatePHP\Mapper\AbstractRdfMapper;
use Midgard\CreatePHP\Entity\PropertyInterface;
use Midgard\CreatePHP\Entity\CollectionInterface;
use Midgard\CreatePHP\Entity\EntityInterface;
use Midgard\CreatePHP\Type\TypeInterface;
use midcom;
use midcom_connection;
use midcom_error;
use midcom_helper_reflector;

/**
 * Default RdfMapper implementation for MidCOM DBA
 *
 * @package openpsa.createphp
 */
class dba2rdfMapper extends AbstractRdfMapper
{
    /**
     * {@inheritDoc}
     */
    public function orderChildren(EntityInterface $entity, CollectionInterface $node, $expectedOrder)
    {
        $children = $this->getChildren($entity->getObject(), $node);
        $child_subjects = [];
        $child_map = [];

        foreach ($children as $child) {
            $child_subjects[] = $this->createSubject($child);
            $child_map[$this->createSubject($child)] = $child;
        }

        $child_subjects = $this->sort($child_subjects, $expectedOrder);

        foreach ($child_subjects as $index => $subject) {
            if ($index !== $child_map[$subject]->metadata->score) {
                $child_map[$subject]->metadata->score = $index;
                if (!$child_map[$subject]->update()) {
                    throw new midcom_error(midcom_connection::get_error_string());
                }
            }
        }
    }

    public function getBySubject($identifier)
    {
        $identifier = str_replace(midcom::get()->config->get('midcom_site_url') . 'midcom-permalink-', '', $identifier);
        $identifier = trim($identifier, '<>');

        try {
            return midcom::get()->dbfactory->get_object_by_guid($identifier);
        } catch (midcom_error $e) {
            $e->log();
            return false;
        }
    }

    public function createSubject($object)
    {
        return midcom::get()->permalinks->create_permalink($object->guid);
    }

    public function prepareObject(TypeInterface $controller, $parent = null)
    {
        $config = $controller->getConfig();
        $class = $config['storage'];
        $object = new $class;

        if (null !== $parent) {
            $baseclass = midcom_helper_reflector::resolve_baseclass($class);
            $reflector = new \midgard_reflection_property($baseclass);

            $up_property = \midgard_object_class::get_property_up($baseclass);
            if (!empty($up_property)) {
                $target_property = $reflector->get_link_target($up_property);
                $target_class = $reflector->get_link_name($up_property);
                if (midcom_helper_reflector::resolve_baseclass($parent) === $target_class) {
                    $object->{$up_property} = $parent->{$target_property};
                }
            }

            $parent_property = \midgard_object_class::get_property_parent($baseclass);
            if (!empty($parent_property)) {
                $target_property = $reflector->get_link_target($parent_property);
                $target_class = $reflector->get_link_name($parent_property);
                if (midcom_helper_reflector::resolve_baseclass($parent) === $target_class) {
                    $object->{$parent_property} = $parent->{$target_property};
                } else {
                    $object->{$parent_property} = $parent->{$parent_property};
                }
            }
        }
        return $object;
    }

    /**
     *
     * @param mixed $object
     * @param CollectionInterface $config
     * @return array
     */
    public function getChildren($object, CollectionInterface $collection)
    {
        $config = $collection->getConfig();
        if (empty($config['parentfield'])) {
            throw new midcom_error('parentfield was not defined in config');
        }
        $parentfield = $config['parentfield'];
        // if storage is not defined, we assume it's the same as object
        if (empty($config['storage'])) {
            $storage = get_class($object);
        } else {
            $storage = $config['storage'];
        }

        $reflector = new \midgard_reflection_property(midcom_helper_reflector::resolve_baseclass($storage));

        if (!$reflector->is_link($parentfield)) {
            throw new midcom_error('could not determine storage class');
        }

        $qb = call_user_func([$storage, 'new_query_builder']);

        $qb->add_constraint($parentfield, '=', $object->id);
        // order the children by their score values
        $qb->add_order('score', 'ASC');
        return $qb->execute();
    }

    public function setPropertyValue($object, PropertyInterface $node, $value)
    {
        $fieldname = $this->_get_fieldname($object, $node);
        $object->$fieldname = $value;
        return $object;
    }

    private function _get_fieldname($object, PropertyInterface $node)
    {
        $config = $node->getConfig();

        if (!array_key_exists('dba_name', $config)) {
            $fieldname = $node->getIdentifier();
        } else {
            $fieldname = $config['dba_name'];
        }
        if (!midcom_helper_reflector::get($object)->property_exists($fieldname)) {
            throw new midcom_error('Could not find property mapping for ' . $fieldname);
        }
        return $fieldname;
    }

    public function getPropertyValue($object, PropertyInterface $node)
    {
        $fieldname = $this->_get_fieldname($object, $node);
        return $object->$fieldname;
    }

    public function isEditable($object)
    {
        return $object->can_do('midgard:update');
    }

    public function store(EntityInterface $entity)
    {
        $object = $entity->getObject();
        if (empty($object->id)) {
            $stat = $object->create();
        } else {
            $stat = $object->update();
        }
        if (false === $stat) {
            debug_add('Could not save entity: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
        }
        return $stat;
    }
}
