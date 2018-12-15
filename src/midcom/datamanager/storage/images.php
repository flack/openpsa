<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

use midcom_error;
use midcom_db_attachment;
use midcom_helper_imagefilter;
use midgard\portable\api\blob;

/**
 * Images storage
 *
 * Controls a list of images
 */
class images extends blobs implements recreateable
{
    public function recreate()
    {
        $attachments = $this->load();
        foreach ($attachments as $attachment) {
            if (!empty($this->config['type_config']['filter_chain'])) {
                $this->apply_filter($attachment, $this->config['type_config']['filter_chain']);
            }
            $this->set_imagedata($attachment);
        }
        return true;
    }

    public function save()
    {
        if (!parent::save()) {
            return false;
        }

        foreach ($this->value as $key => $attachment) {
            if ($key === 'description') {
                continue;
            }
            if (!empty($this->config['type_config']['filter_chain'])) {
                $this->apply_filter($attachment, $this->config['type_config']['filter_chain']);
            }
            $this->set_imagedata($attachment);
        }
        return true;
    }

    protected function set_imagedata(midcom_db_attachment $attachment)
    {
        $blob = new blob($attachment->__object);
        $path = $blob->get_path();

        if ($data = @getimagesize($path)) {
            $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'size_x', $data[0]);
            $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'size_y', $data[1]);
            $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'size_line', $data[3]);
        }
    }

    /**
     * Applies a filter chain
     *
     * @param midcom_db_attachment $source The image to apply to
     * @param string $filterchain The midcom_helper_imagefilter filter chain to apply
     * @param midcom_db_attachment $target The attachment where the changes should be saved
     */
    protected function apply_filter(midcom_db_attachment $source, $filterchain, midcom_db_attachment $target = null)
    {
        if ($target === null) {
            $target = $source;
        }
        $filter = new midcom_helper_imagefilter($source);
        if (!empty($filterchain)) {
            $filter->process_chain($filterchain);
        }
        if (!$filter->write($target)) {
            throw new midcom_error("Failed to update image '{$target->guid}'");
        }
    }
}
