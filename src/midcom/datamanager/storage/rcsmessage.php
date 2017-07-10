<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

/**
 * Experimental storage class
 */
class rcsmessage extends dbanode
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
        if (empty($this->config['hidden'])) {
            $this->object->set_rcs_message($value);
        }
    }

    public function save()
    {
        return true;
    }
}
