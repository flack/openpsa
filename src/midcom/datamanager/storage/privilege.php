<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

/**
 * Experimental storage class
 */
class privilege extends delayed
{
    /**
     * {@inheritdoc}
     */
    public function load()
    {
        $source = $this->object->get_privilege(
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
        return $this->object->set_privilege(
            $this->config['type_config']['privilege_name'],
            $this->config['type_config']['assignee'],
            $this->value,
            $this->config['type_config']['classname']
        );
    }
}