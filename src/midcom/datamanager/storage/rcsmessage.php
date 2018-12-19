<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

/**
 * Experimental storage class
 */
class rcsmessage extends property
{
    /**
     * {@inheritdoc}
     */
    public function get_value()
    {
        return $this->object->get_rcs_message();
    }

    /**
     * {@inheritdoc}
     */
    public function set_value($value)
    {
        $this->object->set_rcs_message($value);
    }
}
