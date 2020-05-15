<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage\container;

use midcom_core_dbaobject;
use midgard_reflection_property;
use midcom\datamanager\schema;
use midcom\datamanager\storage\transientnode;
use midcom\datamanager\storage\node;
use midcom\datamanager\storage\blobs;
use midcom\datamanager\storage\property;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Experimental storage baseclass
 */
class dbacontainer extends container
{
    /**
     *
     * @var midcom_core_dbaobject
     */
    private $object;

    public function __construct(schema $schema, midcom_core_dbaobject $object, array $defaults)
    {
        $this->object = $object;
        $this->schema = $schema;

        foreach ($this->schema->get('fields') as $name => $config) {
            if (array_key_exists($name, $defaults)) {
                $config['default'] = $defaults[$name];
            }
            $config['name'] = $name;
            $field = $this->prepare_field($config);
            if (   isset($config['default'])
                && (!$this->object->id || $field instanceof transientnode)) {
                $field->set_value($config['default']);
            }

            $this->fields[$name] = $field;
        }
    }

    public function lock() : bool
    {
        if (!$this->object->id) {
            return true;
        }
        return $this->object->metadata->lock();
    }

    public function unlock() : bool
    {
        if (!$this->object->id) {
            return true;
        }
        return $this->object->metadata->unlock();
    }

    public function is_locked() : bool
    {
        return $this->object->metadata->is_locked();
    }

    /**
     * {@inheritdoc}
     */
    public function get_value()
    {
        return $this->object;
    }

    /**
     * {@inheritdoc}
     */
    public function set_value($object)
    {
        $this->object = $object;
    }

    private function prepare_field(array $config) : node
    {
        $location = empty($config['storage']['location']) ? null : strtolower($config['storage']['location']);
        // We need to check for parameter, because it is set by default in the schema parser and then ignored
        // by the type. The things we do for backwards compatibility...
        if (in_array($location, [null, 'parameter'], true)) {
            if (class_exists('midcom\datamanager\storage\\' . $config['type'])) {
                $classname = 'midcom\datamanager\storage\\' . $config['type'];
            } elseif ($location === 'parameter') {
                $classname = 'midcom\datamanager\storage\parameter';
            } else {
                return new transientnode($config);
            }
        } elseif ($location === 'metadata') {
            $classname = 'midcom\datamanager\storage\metadata';
        } elseif ($location === 'privilege') {
            $classname = 'midcom\datamanager\storage\privilege';
        } else {
            $classname = property::class;

            $rfp = new midgard_reflection_property($this->object->__mgdschema_class_name__);
            $type = $rfp->get_midgard_type($location);
            if ($type == MGD_TYPE_STRING) {
                $config['validation'][] = new Length(['max' => 255]);
            }
        }
        return new $classname($this->object, $config);
    }

    public function save()
    {
        if ($this->object->id) {
            $stat = $this->object->update();
        } elseif ($stat = $this->object->create()) {
            $this->object->set_parameter('midcom.helper.datamanager2', 'schema_name', $this->schema->get_name());
        }
        if (!$stat) {
            if (\midcom_connection::get_error() === MGD_ERR_ACCESS_DENIED) {
                throw new \midcom_error_forbidden('Failed to save: ' . \midcom_connection::get_error_string());
            }
            throw new \midcom_error('Failed to save: ' . \midcom_connection::get_error_string());
        }

        foreach ($this->fields as $node) {
            $node->save();
        }
    }

    public function move_uploaded_files() : int
    {
        $total_moved = 0;
        foreach ($this->fields as $node) {
            if ($node instanceof blobs) {
                $total_moved += $node->move_uploaded_files();
            }
        }
        return $total_moved;
    }
}
