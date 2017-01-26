<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use midcom_db_attachment;
use midcom_helper_misc;

/**
 * Experimental blobs transformer
 */
class blobs implements DataTransformerInterface
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function transform($input)
    {
        if ($input === null) {
            return;
        }

        if (is_array($input)) {
            //This happens when there is a form validation failure
            if (!empty($input['filename'])) {
                //This is already transformed viewData from the parent object
                return $input;
            }
            return $this->transform_nonpersistent($input);
        }

        return $this->transform_persistent($input);
    }

    protected function transform_persistent(midcom_db_attachment $attachment)
    {
        $stats = $attachment->stat();
        $description = $attachment->title;
        if (!empty($this->config['widget_config']['show_description'])) {
            $description = $attachment->get_parameter('midcom.helper.datamanager2.type.blobs', 'description');
        }
        return array(
            'filename' => $attachment->name,
            'description' => $description,
            'title' => $attachment->title,
            'mimetype' => $attachment->mimetype,
            'url' => midcom_db_attachment::get_url($attachment),
            'id' => $attachment->id,
            'guid' => $attachment->guid,
            'filesize' => $stats[7],
            'formattedsize' => midcom_helper_misc::filesize_to_string($stats[7]),
            'lastmod' => $stats[9],
            'isoformattedlastmod' => strftime('%Y-%m-%d %T', $stats[9]),
            'size_x' => $attachment->get_parameter('midcom.helper.datamanager2.type.blobs', 'size_x'),
            'size_y' => $attachment->get_parameter('midcom.helper.datamanager2.type.blobs', 'size_y'),
            'size_line' => $attachment->get_parameter('midcom.helper.datamanager2.type.blobs', 'size_line'),
            'object' => $attachment,
            'score' => $attachment->metadata->score
            //'identifier' => $identifier
        );
    }

    protected function transform_nonpersistent(array $data)
    {
        if (empty($data['file'])) {
            return null;
        }
        $title = (!empty($data['title'])) ? $data['title'] : $data['file']['name'];
        $description = (array_key_exists('description', $data)) ? $data['description'] : $title;
        $stat = stat($data['file']['tmp_name']);
        return array(
            'filename' => $data['file']['name'],
            'description' => $description,
            'title' => $title,
            'mimetype' => $data['file']['type'],
            'url' => '',
            'id' => 0,
            'guid' => '',
            'filesize' => $stat[7],
            'formattedsize' => midcom_helper_misc::filesize_to_string($stat[7]),
            'lastmod' => $stat[9],
            'isoformattedlastmod' => strftime('%Y-%m-%d %T', $stat[9]),
            'size_x' => '',
            'size_y' => '',
            'size_line' => '',
            'object' => null,
            'identifier' => $data['identifier']
        );
    }

    public function reverseTransform($array)
    {
        if (!is_array($array) ) {
            throw new TransformationFailedException('Expected an array.');
        }

        if (!empty($array)) {
            return $array;
        }
        //@todo return $array; ?
    }
}
