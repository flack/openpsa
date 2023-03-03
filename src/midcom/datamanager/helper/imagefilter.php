<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\helper;

use midcom_helper_imagefilter;
use midcom_db_attachment;
use midcom_error;
use Symfony\Component\Mime\FileBinaryMimeTypeGuesser;

/**
 * Image filter
 */
class imagefilter
{
    use attachment;

    private array $config;

    private array $images = [];

    private bool $save_archival = false;

    public function __construct(array $config, bool $save_archival = false)
    {
        $this->config = $config;
        $this->save_archival = $save_archival;
    }

    public function process(midcom_db_attachment $source, array $existing) : array
    {
        if ($this->save_archival) {
            $path = $source->location;
            $attachment = $this->get_attachment($source, $existing, 'archival');
            if (!$attachment->copy_from_file($path)) {
                throw new midcom_error('Failed to copy attachment');
            }
            $this->images['archival'] = $attachment;
        }

        $this->images['main'] = $this->create_main_image($source, $existing);

        $this->create_derived_images($existing);

        foreach ($this->images as $attachment) {
            $this->set_imagedata($attachment);
        }

        return $this->images;
    }

    private function get_attachment(midcom_db_attachment $input, array $existing, string $identifier) : midcom_db_attachment
    {
        // upload case
        if ($input->id == 0) {
            $guesser = new FileBinaryMimeTypeGuesser;
            $input->mimetype = $guesser->guessMimeType($input->location);
        }

        $filename = midcom_db_attachment::safe_filename($identifier . '_' . $input->name);
        if (!empty($existing[$identifier])) {
            $attachment = $existing[$identifier];
            if ($attachment->name != $filename) {
                $attachment->name = $this->generate_unique_name($filename, $attachment->parentguid);
            }
            $attachment->title = $input->name;
            $attachment->mimetype = $input->mimetype;
            return $attachment;
        }

        $attachment = new midcom_db_attachment;
        $attachment->parentguid = $input->parentguid;
        $this->create_attachment($attachment, $filename, $input->name, $input->mimetype);

        return $attachment;
    }

    private function set_imagedata(midcom_db_attachment $attachment)
    {
        $path = $attachment->get_path();

        if ($data = @getimagesize($path)) {
            $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'size_x', $data[0]);
            $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'size_y', $data[1]);
            $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'size_line', $data[3]);
        }
    }

    private function create_main_image(midcom_db_attachment $input, array $existing) : midcom_db_attachment
    {
        $source = $input->location;
        $attachment = $this->get_attachment($input, $existing, 'main');
        if (!$attachment->copy_from_file($source)) {
            throw new midcom_error('Failed to copy attachment');
        }
        $this->convert_to_web_type($attachment);
        return $attachment;
    }

    private function create_derived_images(array $existing)
    {
        $derived = [];
        if (!empty($this->config['derived_images'])) {
            $derived = $this->config['derived_images'];
        }
        if (!empty($this->config['auto_thumbnail'])) {
            $derived['thumbnail'] = "resize({$this->config['auto_thumbnail'][0]},{$this->config['auto_thumbnail'][1]})";
        }

        foreach ($derived as $identifier => $filter_chain) {
            $target = $this->get_attachment($this->images['main'], $existing, $identifier);
            $filter = new midcom_helper_imagefilter($this->images['main']);
            if (!empty($filter_chain)) {
                $filter->process_chain($filter_chain);
            }
            if (!$filter->write($target)) {
                throw new midcom_error("Failed to update image '{$target->guid}'");
            }
            $this->images[$identifier] = $target;
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
    private function convert_to_web_type(midcom_db_attachment $upload)
    {
        $original_mimetype = $upload->mimetype;
        switch (preg_replace('/;.+$/', '', $original_mimetype)) {
            case 'image/png':
            case 'image/gif':
            case 'image/jpeg':
                debug_add('No conversion necessary, we already have a web mime type');
                return;

            case 'application/postscript':
            case 'application/pdf':
                $upload->mimetype = 'image/png';
                $conversion = 'png';
                break;

            default:
                $upload->mimetype = 'image/jpeg';
                $conversion = 'jpg';
                break;
        }
        debug_add('convert ' . $original_mimetype . ' to ' . $conversion);

        $filter = new midcom_helper_imagefilter($upload);
        $filter->convert($conversion);

        // Prevent double .jpg.jpg
        if (!str_ends_with($upload->name, ".{$conversion}")) {
            // Make sure there is only one extension on the file ??
            $upload->name = midcom_db_attachment::safe_filename($upload->name . ".{$conversion}");
        }
        $filter->write($upload);
    }
}