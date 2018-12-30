<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

use midcom\datamanager\helper\imagefilter;

/**
 * Image storage
 *
 * Controls a list of images derived from just one (via filter chains)
 */
class image extends blobs implements recreateable
{
    protected $save_archival = false;

    public function recreate()
    {
        $this->map = [];

        $existing = $this->load();
        if (array_key_exists('archival', $existing)) {
            $this->map['archival'] = $existing['archival'];
            $this->value['file'] = $this->map['archival'];
            $attachment = $this->create_main_image($this->value['file'], $existing);
        } elseif (array_key_exists('main', $existing)) {
            $this->map['main'] = $existing['main'];
            $this->value['file'] = $existing['main'];
            $attachment = $existing['main'];
        }
        if (!empty($attachment)) {
            return $this->save_derived_images($attachment, $existing);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        if ($this->value === null) {
            //delete?
        }

        if (!empty($this->value['file'])) {
            $this->value['file']->parentguid = $this->object->guid;
            $existing = $this->load();
            $filter = new imagefilter($this->config['type_config'], $this->save_archival);
            $this->map = $filter->process($this->value['file'], $existing);

            return $this->save_attachment_list();
        }
        if (!empty($this->value['delete'])) {
            $this->map = [];
            return $this->save_attachment_list();
        }
        return true;
    }
}
