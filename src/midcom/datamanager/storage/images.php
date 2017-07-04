<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

use midgard_blob;
use midcom_error;
use midcom_db_attachment;
use midcom_helper_imagefilter;

/**
 * Experimental storage class
 */
class images extends blobs
{
    public function save()
    {
        if (!parent::save()) {
            return false;
        }
        foreach ($this->value as $data) {
            $attachment = $this->map[$data['identifier']];
            if (!empty($this->config['type_config']['filter_chain'])) {
                $this->apply_filter($attachment, $this->config['type_config']['filter_chain']);
            }
            $this->set_imagedata($attachment);
            if (!empty($this->config['widget_config']['show_description'])) {
                $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'description', $data['description']);
            }
        }
        return true;
    }

    protected function set_imagedata(midcom_db_attachment $attachment)
    {
        $blob = new midgard_blob($attachment->__object);
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
    protected function apply_filter(midcom_db_attachment $source, $filterchain, $target = null)
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
