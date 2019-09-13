<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

/**
 * Experimental storage class
 */
class parameter extends delayed
{
    /**
     * {@inheritdoc}
     */
    public function load()
    {
        $value = $this->object->get_parameter($this->config['storage']['domain'], $this->config['storage']['name']);

        if ($value === null && isset($this->config['default'])) {
            $value = $this->config['default'];
        }

        return $this->cast($value);
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $this->object->set_parameter($this->config['storage']['domain'], $this->config['storage']['name'], $this->value);
    }
}