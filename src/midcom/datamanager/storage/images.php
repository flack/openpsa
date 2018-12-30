<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

use midcom_error;
use midcom_db_attachment;
use midcom_helper_imagefilter;

/**
 * Images storage
 *
 * Controls a list of images
 */
class images extends image
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

    /**
     * Applies a filter chain
     *
     * @param midcom_db_attachment $source The image to apply to
     * @param string $filterchain The midcom_helper_imagefilter filter chain to apply
     * @param midcom_db_attachment $target The attachment where the changes should be saved
     */
    private function apply_filter(midcom_db_attachment $source, $filterchain)
    {
        $filter = new midcom_helper_imagefilter($source);
        if (!empty($filterchain)) {
            $filter->process_chain($filterchain);
        }
        if (!$filter->write($source)) {
            throw new midcom_error("Failed to update image '{$source->guid}'");
        }
    }
}
