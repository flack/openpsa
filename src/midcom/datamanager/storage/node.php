<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

/**
 * Experimental storage interface
 */
interface node
{
    /**
     * @return mixed
     */
    public function get_value();

    /**
     * @param mixed $value
     */
    public function set_value($value);

    public function save();
}
