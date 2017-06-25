<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

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
        return $this->object->{$this->config['storage']['location']};
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
