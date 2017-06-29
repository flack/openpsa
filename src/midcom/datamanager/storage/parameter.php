<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

/**
 * Experimental storage class
 */
class parameter extends delayed
{
    /**
     * {@inheritdoc}
     */
    public function load()
    {
        return $this->object->get_parameter($this->config['storage']['domain'], $this->config['storage']['name']);
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        //workaround for weird mgd API behavior where setting falsy (i.e. deleting) a nonexistent parameter
        //returns an error
        if (   !$this->value
            && $this->load() === null) {
            return true;
        }

        return $this->object->set_parameter($this->config['storage']['domain'], $this->config['storage']['name'], $this->value);
    }
}