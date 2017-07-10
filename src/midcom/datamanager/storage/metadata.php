<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

/**
 * Experimental storage class
 */
class metadata extends property
{
    /**
     * {@inheritdoc}
     */
    public function get_value()
    {
        return $this->object->metadata->{$this->config['name']};
    }

    /**
     * {@inheritdoc}
     */
    public function set_value($value)
    {
        if (empty($this->config['hidden'])) {
            $this->object->metadata->{$this->config['name']} = $value;
        }
    }
}
