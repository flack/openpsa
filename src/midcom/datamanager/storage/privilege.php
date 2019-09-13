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
        $source = $this->get_privilege();
        return $source->value;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $privilege = $this->get_privilege();
        $privilege->value = $this->value;
        $privilege->store();
    }

    /**
     * @return \midcom_core_privilege
     */
    private function get_privilege()
    {
        $privilege = $this->get_privilege_object()->get_privilege(
            $this->config['type_config']['privilege_name'],
            $this->config['type_config']['assignee'],
            $this->config['type_config']['classname']);

        if ($privilege === false) {
            throw new \midcom_error_forbidden('Failed to load privilege');
        }
        return $privilege;
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