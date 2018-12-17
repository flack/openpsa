<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use midcom_db_attachment;
use midcom_helper_misc;
use midcom;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Experimental blobs transformer
 */
class attachmentTransformer implements DataTransformerInterface
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function transform($input)
    {
        if (empty($input)) {
            return null;
        }

        if (! $input instanceof midcom_db_attachment) {
            throw new UnexpectedTypeException($input, midcom_db_attachment::class);
        }

        $description = $input->title;
        if (!empty($this->config['widget_config']['show_description'])) {
            $description = $input->get_parameter('midcom.helper.datamanager2.type.blobs', 'description');
        }

        // Check for nonpersistent entries (which we mainly get in when there's a validation error)
        if (empty($input->guid)) {
            $identifier = substr($input->location, strlen(midcom::get()->config->get('midcom_tempdir')) + 1);
            $stats = stat($input->location);
            $file = new UploadedFile($input->location, $input->name);
        } else {
            $identifier = $input->guid;
            $stats = $input->stat();
            $file = null;
        }

        return [
            'filename' => $input->name,
            'description' => $description,
            'title' => $input->title,
            'mimetype' => $input->mimetype,
            'url' => midcom_db_attachment::get_url($input),
            'id' => $input->id,
            'guid' => $input->guid,
            'filesize' => $stats[7],
            'formattedsize' => midcom_helper_misc::filesize_to_string($stats[7]),
            'lastmod' => $stats[9],
            'isoformattedlastmod' => strftime('%Y-%m-%d %T', $stats[9]),
            'size_x' => $input->get_parameter('midcom.helper.datamanager2.type.blobs', 'size_x'),
            'size_y' => $input->get_parameter('midcom.helper.datamanager2.type.blobs', 'size_y'),
            'size_line' => $input->get_parameter('midcom.helper.datamanager2.type.blobs', 'size_line'),
            'object' => $input,
            'score' => $input->metadata->score,
            'identifier' => $identifier,
            'file' => $file
        ];
    }

    public function reverseTransform($array)
    {
        if (empty($array)) {
            return null;
        }

        $title = $array['title'];

        if (!empty($array['file']) && $array['file'] instanceof UploadedFile) {
            $attachment = new midcom_db_attachment;
            $attachment->name = $array['file']->getClientOriginalName();
            $attachment->mimetype = $array['file']->getMimeType();
            $attachment->location = $array['file']->getPathname();

            if (empty($title)) {
                $title = $attachment->name;
            }
        } elseif (substr($array['identifier'], 0, 8) === 'tmpfile-') {
            $tmpfile = midcom::get()->config->get('midcom_tempdir') . '/' . $array['identifier'];
            if (file_exists($tmpfile)) {
                $attachment = new midcom_db_attachment;
                $attachment->name = $title ?: $array['identifier'];
                $attachment->location = $tmpfile;
            }
        } elseif (!empty($array['object'])) {
            $attachment = $array['object'];
        }

        if (empty($attachment)) {
            throw new TransformationFailedException('None of the required keys were found.');
        }
        $attachment->title = $title;
        if (!empty($this->config['widget_config']['sortable'])) {
            $attachment->metadata->score = (int) $array['score'];
        }

        return $attachment;
    }
}
