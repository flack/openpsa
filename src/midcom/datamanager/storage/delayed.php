<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

/**
 * Experimental storage baseclass
 */
abstract class delayed extends dbanode
{
    protected $value;

    protected $initialized;

    public function set_value($value)
    {
        $this->initialized = true;
        $this->value = $value;
    }

    public function get_value()
    {
        if (!$this->initialized) {
            $this->value = $this->load();
            $this->initialized = true;
        }
        return $this->value;
    }

    /**
     * @return mixed Current value
     */
    abstract public function load();
}
