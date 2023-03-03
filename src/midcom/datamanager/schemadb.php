<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

use midcom_error;
use midcom_helper_misc;

/**
 * Experimental schemadb class
 */
class schemadb
{
    private array $schemas = [];

    public static function from_path(string $path) : self
    {
        return new static(static::load_from_path($path));
    }

    private static function load_from_path(string $path) : array
    {
        $data = midcom_helper_misc::get_snippet_content($path);
        return midcom_helper_misc::parse_config($data, $path);
    }

    public function __construct(array $data = [])
    {
        $this->check_inheritance($data);
        foreach ($data as $name => $config) {
            $this->add($name, new schema($config));
        }
    }

    private function check_inheritance(array &$data)
    {
        foreach ($data as $schema_name => $schema) {
            if (!isset($schema['extends'])) {
                continue;
            }

            // Default extended schema is with the same name
            $extended_schema_name = $schema_name;
            $path = '';

            if (is_array($schema['extends'])) {
                if (isset($schema['extends']['path'])) {
                    $path = $schema['extends']['path'];
                }

                // Override schema name
                if (isset($schema['extends']['name'])) {
                    $extended_schema_name = $schema['extends']['name'];
                }
            } elseif (isset($data[$schema['extends']])) {
                $schema['extends'] = [
                    'name' => $schema['extends'],
                ];
            } else {
                $path = $schema['extends'];
            }

            if ($path === '') {
                // Infinite loop, set an UI message and stop executing
                if (   !isset($schema['extends']['name'])
                    || $schema['extends']['name'] === $schema_name) {
                    throw new midcom_error('schema ' . $schema_name . ' extends itself');
                }
                $extended_schema_name = $schema['extends']['name'];
                $extended_schemadb = [$extended_schema_name => $data[$extended_schema_name]];
            } else {
                $extended_schemadb = static::load_from_path($path);
                if (!isset($extended_schemadb[$extended_schema_name])) {
                    throw new midcom_error('extended schema ' . $path . ':' . $schema_name . ' was not found');
                }
            }

            // Override the extended schema with fields from the new schema
            foreach ($schema as $key => $value) {
                if ($key === 'extends') {
                    continue;
                }

                // This is probably either fields or operations
                if (is_array($value)) {
                    if (!isset($extended_schemadb[$extended_schema_name][$key])) {
                        $extended_schemadb[$extended_schema_name][$key] = [];
                    }

                    foreach ($value as $name => $field) {
                        if (!$field) {
                            unset($extended_schemadb[$extended_schema_name][$key][$name]);
                            continue;
                        }

                        $extended_schemadb[$extended_schema_name][$key][$name] = $field;
                    }
                } else {
                    $extended_schemadb[$extended_schema_name][$key] = $value;
                }
            }

            // Replace the new schema with extended schema
            $data[$schema_name] = $extended_schemadb[$extended_schema_name];
        }
    }

    public function add(string $name, schema $schema)
    {
        $this->schemas[$name] = $schema;
        $schema->set_name($name);
    }

    /**
     * @return schema[]
     */
    public function all() : array
    {
        return $this->schemas;
    }

    public function get_first() : schema
    {
        if (empty($this->schemas)) {
            throw new midcom_error('Schema DB is empty');
        }
        return reset($this->schemas);
    }

    public function has(string $name) : bool
    {
        return array_key_exists($name, $this->schemas);
    }

    public function get(string $name) : schema
    {
        if (!array_key_exists($name, $this->schemas)) {
            throw new midcom_error('Schema ' . $name . ' not found in schemadb');
        }
        return $this->schemas[$name];
    }
}
