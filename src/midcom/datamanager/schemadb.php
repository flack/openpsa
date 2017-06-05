<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

use midcom_error;

/**
 * Experimental schemadb class
 */
class schemadb
{
    private $schemas = [];

    public function add($name, schema $schema)
    {
        $this->schemas[$name] = $schema;
        $schema->set_name($name);
    }

    /**
     *
     * @return schema
     */
    public function get_first()
    {
        if (empty($this->schemas)) {
            throw new midcom_error('Schema DB is empty');
        }
        return reset($this->schemas);
    }

    /**
     *
     * @param string $name
     * @return schema
     */
    public function get($name)
    {
        if (!array_key_exists($name, $this->schemas)) {
            throw new midcom_error('Schema ' . $name . ' not found in schemadb');
        }
        return $this->schemas[$name];
    }
}
