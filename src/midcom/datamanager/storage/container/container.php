<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage\container;

use midcom\datamanager\storage\node;
use midcom\datamanager\schema;

/**
 * Experimental storage class
 */
abstract class container implements node, \ArrayAccess, \Iterator
{
    /**
     *
     * @var node[]
     */
    protected $fields = [];

    /**
     *
     * @var schema
     */
    protected $schema;

    /**
     * @var string
     */
    private $last_operation;

    /**
     * @return boolean Indicating success
     */
    abstract public function lock() : bool;

    /**
     * @return boolean Indicating success
     */
    abstract public function unlock() : bool;

    abstract public function is_locked() : bool;

    public function get_last_operation() : string
    {
        return $this->last_operation;
    }

    public function set_last_operation(string $operation)
    {
        $this->last_operation = $operation;
    }

    public function __get($name)
    {
        return $this->fields[$name]->get_value();
    }

    public function __set($name, $value)
    {
        $this->fields[$name]->set_value($value);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->fields);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->fields[$offset]);
    }

    /**
     *
     * @return node
     */
    public function current()
    {
        return current($this->fields);
    }

    public function key()
    {
        return key($this->fields);
    }

    public function next() : void
    {
        next($this->fields);
    }

    public function rewind() : void
    {
        reset($this->fields);
    }

    public function valid() : bool
    {
        return key($this->fields) !== null;
    }
}
