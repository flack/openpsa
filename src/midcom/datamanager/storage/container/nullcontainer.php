<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage\container;

use midcom\datamanager\storage\transientnode;
use midcom\datamanager\schema;

/**
 * Experimental storage class
 */
class nullcontainer extends container
{
    /**
     *
     * @var mixed
     */
    protected $value;

    public function __construct(schema $schema, array $defaults)
    {
        $this->schema = $schema;

        foreach ($this->schema->get_fields() as $name => $config) {
            if (array_key_exists($name, $defaults)) {
                $config['default'] = $defaults[$name];
            }
            $config['name'] = $name;
            $this->fields[$name] = new transientnode($config);
            if (isset($config['default'])) {
                $this->fields[$name]->set_value($config['default']);
            }
        }
    }

    public function set_value($value)
    {
        $this->value = $value;
    }

    public function get_value()
    {
        return $this->value;
    }

    public function save()
    {
        return true;
    }
}
