<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

use midgard_blob;
use midcom_db_attachment;

/**
 * Experimental storage class
 */
class images extends blobs
{
    public function save()
    {
        if (!parent::save())
        {
            return false;
        }
        foreach ($this->value as $data)
        {
            $attachment = $this->map[$data['identifier']];
            $this->set_imagedata($attachment);
            if (!empty($this->config['widget_config']['show_description']))
            {
                $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'description', $data['description']);
            }
        }
        return true;
    }

    protected function set_imagedata(midcom_db_attachment $attachment)
    {
        $blob = new midgard_blob($attachment->__object);
        $path = $blob->get_path();

        if ($data = @getimagesize($path))
        {
            $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'size_x', $data[0]);
            $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'size_y', $data[1]);
            $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'size_line', $data[3]);
        }
    }
}