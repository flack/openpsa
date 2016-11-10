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
    /**
     * {@inheritdoc}
     */
    public function get_value()
    {
        return $this->object->{$this->config['storage']['location']};
    }

    /**
     * {@inheritdoc}
     */
    public function set_value($value)
    {
        $this->object->{$this->config['storage']['location']} = $value;
    }

    public function save()
    {
        return true;
    }
}
