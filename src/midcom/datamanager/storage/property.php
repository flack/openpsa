<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

use midgard_reflection_property;

/**
 * Experimental storage class
 */
class property extends dbanode
{
    private $set = false;

    /**
     * {@inheritdoc}
     */
    public function get_value()
    {
        if (!$this->object->id && !$this->set && $this->config['type'] == 'number') {
            return;
        }
        $value = $this->object->{$this->config['storage']['location']};
        if ($value === 0) {
            $reflector = new midgard_reflection_property($this->object->__mgdschema_class_name__);
            if ($reflector->is_link($this->config['storage']['location'])) {
                return;
            }
        }
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set_value($value)
    {
        $this->set = true;
        $this->object->{$this->config['storage']['location']} = $value;
    }

    public function save()
    {
        return true;
    }
}
