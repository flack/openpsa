<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

use midgard_reflection_property;
use midcom_helper_reflector_nameresolver;
use midcom_helper_misc;

/**
 * Experimental storage class
 */
class property extends dbanode
{
    private $set = false;

    /**
     * {@inheritdoc}
     */
    public function get_value()
    {
        if (!$this->object->id && !$this->set && $this->config['type'] == 'number') {
            return;
        }
        $value = $this->object->{$this->config['storage']['location']};
        if ($value === 0) {
            $reflector = new midgard_reflection_property($this->object->__mgdschema_class_name__);
            if ($reflector->is_link($this->config['storage']['location'])) {
                return;
            }
        }

        return $this->cast($value);
    }

    /**
     * {@inheritdoc}
     */
    public function set_value($value)
    {
        if ($this->config['type'] === 'urlname') {

            if (empty($value)) {
                $title_field = (!empty($this->config['type_config']['title_field'])) ? $this->config['type_config']['title_field'] : 'title';
                $value = midcom_helper_misc::urlize($this->object->{$title_field});
            } elseif (!empty($this->config['type_config']['allow_catenate'])) {
                $copy = clone $this->object;
                $copy->{$this->config['storage']['location']} = $value;
                $resolver = new midcom_helper_reflector_nameresolver($copy);
                if (!$resolver->name_is_unique()) {
                    $value = $resolver->generate_unique_name();
                }
            }
        }

        $this->set = true;
        $this->object->{$this->config['storage']['location']} = $value;
    }

    public function save()
    {
        return true;
    }
}
