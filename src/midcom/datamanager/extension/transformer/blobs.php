<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

use Symfony\Component\Form\DataTransformerInterface;
use midcom_db_attachment;
use midcom_helper_misc;

/**
 * Experimental blobs transformer
 */
class blobs implements DataTransformerInterface
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function transform($input)
    {
        if ($input === null)
        {
            return;
        }

        $result = array();
        foreach ($input as $identifier => $attachment)
        {
            if ($attachment === null)
            {
                //@todo Delete?
                continue;
            }
            if (!empty($attachment['object']))
            {
                $result[$identifier] = $attachment;
                continue;
            }
        	$title = null;
        	if (array_key_exists('title', $attachment))
        	{
        		//@todo Is this really the best we can do?
        		$title = $attachment['title'];
        		$attachment = $attachment['file'];
        	}
        	if ($attachment)
        	{
        	    $result[$identifier] = $this->transform_nonpersistent($attachment, $identifier, $title);
        	}
        }
        return $result;
    }

    protected function transform_nonpersistent(array $data, $identifier, $title = null)
    {
    	$title = ($title !== null) ? $title : $data['name'];
        $stat = stat($data['tmp_name']);
        return array
        (
            'filename' => $data['name'],
            'description' => $title,
            'title' => $title,
            'mimetype' => $data['type'],
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
            'identifier' => $identifier
        );
    }

    public function reverseTransform($array)
    {
        if (!is_array($array) ) {
            throw new TransformationFailedException('Expected an array.');
        }

        if (!empty($array))
        {
            return $array;
        }
        //@todo return $array; ?
    }
}
