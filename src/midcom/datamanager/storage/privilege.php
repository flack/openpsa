<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

use midcom_core_dbaobject;

/**
 * Experimental storage class
 */
class privilege extends delayed
{
    public function __construct(midcom_core_dbaobject $object, array $config)
    {
        if (!array_key_exists('classname', $config['type_config'])) {
            $config['type_config']['classname'] = '';
        }
        parent::__construct($object, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        $source = $this->get_privilege_object()->get_privilege(
            $this->config['type_config']['privilege_name'],
            $this->config['type_config']['assignee'],
            $this->config['type_config']['classname']
        );
        return $source->value;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        return $this->get_privilege_object()->set_privilege(
            $this->config['type_config']['privilege_name'],
            $this->config['type_config']['assignee'],
            $this->value,
            $this->config['type_config']['classname']
        );
    }

    /**
     * @return midcom_core_dbaobject
     */
    private function get_privilege_object()
    {
        if (!empty($this->config['type_config']['privilege_object'])) {
            return $this->config['type_config']['privilege_object'];
        }
        return $this->object;
    }
}