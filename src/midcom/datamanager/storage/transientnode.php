<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

/**
 * Experimental storage class
 */
class transientnode implements node
{
    private $value;

    /**
     * {@inheritdoc}
     */
    public function get_value()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function set_value($value)
    {
        $this->value = $value;
    }

    public function save()
    {
    }
}
