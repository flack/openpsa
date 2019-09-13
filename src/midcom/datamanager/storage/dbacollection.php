<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

use midcom;
use midcom_error;
use midcom_connection;
use midcom\datamanager\storage\container\dbacontainer;
use midcom\datamanager\schema;
use midgard\portable\storage\connection;

/**
 * Experimental storage class
 */
class dbacollection extends delayed
{
    /**
     * @var schema
     */
    private $schema;

    public function __construct($object, $config)
    {
        parent::__construct($object, $config);
        $defaults = [
            'mapping_class_name' => null,
            'master_fieldname' => null,
            'master_is_id' => false,
            'schema' => null
        ];
        $this->config['type_config'] = array_merge($defaults, $this->config['type_config']);
        $this->schema = new schema($this->config['type_config']['schema']);
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $existing = $this->load();
        $to_delete = array_diff_key($existing, $this->value);

        foreach ($this->value as $key => $container) {
            $needs_save = false;
            if (is_array($container)) {
                $container = $this->create_container($container);
                $needs_save = true;
            } else {
                foreach (array_keys($this->schema->get('fields')) as $name) {
                    if ($container->$name != $existing[$key]->$name) {
                        $needs_save = true;
                        break;
                    }
                }
            }
            if ($needs_save) {
                $container->save();
            }
        }

        foreach ($to_delete as $container) {
            if (!$container->get_value()->delete()) {
                throw new midcom_error("Failed to delete subobject {$container->get_value()->guid}: " . midcom_connection::get_error_string());
            }
        }
    }

    private function create_container(array $data) : dbacontainer
    {
        $object = new $this->config['type_config']['mapping_class_name'];
        $object->{$this->config['type_config']['master_fieldname']} = $this->get_master_foreign_key();
        return new dbacontainer($this->schema, $object, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        if (!$this->get_master_foreign_key()) {
            return [];
        }
        // @todo: Without this, we get a reference to the dbacontainer-wrapped object on save. Figure out why
        connection::get_em()->clear();

        $result = [];
        $qb = midcom::get()->dbfactory->new_query_builder($this->config['type_config']['mapping_class_name']);
        $qb->add_constraint($this->config['type_config']['master_fieldname'], '=', $this->get_master_foreign_key());

        foreach ($qb->execute() as $object) {
            $result[$object->guid] = new dbacontainer($this->schema, $object, []);
        }

        return $result;
    }

    /**
     * Returns the foreign key of the master object. This is either the ID or the GUID of
     * the master object, depending on the $master_is_id member.
     *
     * @var string Foreign key for the master field in the mapping table.
     */
    private function get_master_foreign_key()
    {
        if ($this->config['type_config']['master_is_id']) {
            return $this->object->id;
        }
        return $this->object->guid;
    }
}
