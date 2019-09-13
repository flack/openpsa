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
    private $config;

    private $value;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

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
