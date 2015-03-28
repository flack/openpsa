<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

use midcom_db_attachment;
use midcom_helper_imagefilter;
use midcom_error;
use midcom;
use midgard_blob;

/**
 * Experimental storage class
 */
class photo extends blobs
{
    /**
     * {@inheritdoc}
     */
    public function save()
    {
        if ($this->value === null)
        {
            //delete?
        }

        $existing = $this->load();
        if (!empty($this->value['photo']))
        {
            $this->convert_to_web_type($this->value['photo']);
            $attachment = $this->get_attachment($this->value['photo'], $existing, 'archival');
            $filename = midcom_db_attachment::safe_filename($this->value['photo']['name'], true);
            $attachment->name = 'archival_' . $filename;
            $attachment->mimetype = $this->value['photo']['type'];
            if (!$attachment->copy_from_file($this->value['photo']['tmp_name']))
            {
                throw new midcom_error('Failed to copy attachment');
            }

            if (!empty($this->config['type_config']['filter_chain']))
            {
                $this->apply_filter($attachment, $this->config['type_config']['filter_chain']);
            }

            $this->set_imagedata($attachment);
            $map = array('archival' => $attachment->guid);
            if (!empty($this->config['type_config']['derived_images']))
            {
                foreach ($this->config['type_config']['derived_images'] as $identifier => $filter_chain)
                {
                    $derived = $this->get_attachment($this->value['photo'], $existing, $identifier);
                    $derived->name = $identifier . '_' . $filename;
                    $this->apply_filter($attachment, $filter_chain, $derived);
                    $this->set_imagedata($derived);
                    $map[$identifier] = $derived->guid;
                }
            }
            return $this->save_attachment_list($map);
        }
        return true;
    }

    protected function get_attachment(array $data, $existing, $identifier)
    {
        if (!empty($existing[$identifier]))
        {
            return $existing[$identifier]['object'];
        }
        return $this->create_attachment($data);
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

    /**
     * Applies a filter chain
     *
     * @param midcom_db_attachment $source The image to apply to
     * @param string $filterchain The midcom_helper_imagefilter filter chain to apply
     * @param midcom_db_attachment $target The attachment where the changes should be saved
     */
    protected function apply_filter(midcom_db_attachment $source, $filterchain, $target = null)
    {
        if ($target === null)
        {
            $target = $source;
        }
        $filter = new midcom_helper_imagefilter($source);
        $filter->process_chain($filterchain);

        if (!$filter->write($target))
        {
            throw new midcom_error("Failed to update image '{$target->guid}'");
        }
    }

    /**
     * Automatically convert the uploaded file to a web-compatible type. Uses
     * only the first image of multi-page uploads (like PDFs). The original_tmpname
     * file is manipulated directly.
     *
     * Uploaded GIF, PNG and JPEG files are left untouched.
     *
     * In case of any conversions being done, the new extension will be appended
     * to the uploaded file.
     */
    protected function convert_to_web_type(array &$data)
    {
        $original_mimetype = $data['type'];
        switch (preg_replace('/;.+$/', '', $original_mimetype))
        {
            case 'image/png':
            case 'image/gif':
            case 'image/jpeg':
                debug_add('No conversion necessary, we already have a web mime type');
                return;

            case 'application/postscript':
            case 'application/pdf':
                $data['type'] = 'image/png';
                $conversion = 'png';
                break;

            default:
                $data['type'] = 'image/jpeg';
                $conversion = 'jpg';
                break;
        }
        debug_add('convert ' . $original_mimetype . ' to ' . $conversion);

        $filter = new midcom_helper_imagefilter;
        $filter->set_file($data['tmp_name']);
        $filter->convert($conversion);

        // Prevent double .jpg.jpg
        if (!preg_match("/\.{$conversion}$/", $data['tmp_name']))
        {
            // Make sure there is only one extension on the file ??
            $data['name'] = midcom_db_attachment::safe_filename($data['tmp_name'] . ".{$conversion}", true);
        }
    }
}