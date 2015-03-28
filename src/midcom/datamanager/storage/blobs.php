<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

use Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser;
use midcom_db_attachment;
use midcom_helper_misc;
use midcom_error;
use midcom_connection;
use midcom;

/**
 * Experimental storage class
 */
class blobs extends delayed
{
    /**
     * {@inheritdoc}
     */
    public function load()
    {
        $results = array();
        if (!$this->object->id)
        {
            return $results;
        }

        $items = $this->load_attachment_list();
        foreach ($items as $identifier => $guid)
        {
            try
            {
                $results[$identifier] = $this->convert_from_storage(new midcom_db_attachment($guid), $identifier);
            }
            catch (midcom_error $e)
            {
                $e->log();
            }
        }
        return $results;
    }

    protected function convert_from_storage(midcom_db_attachment $attachment, $identifier)
    {
        $stats = $attachment->stat();
        return array
        (
            'filename' => $attachment->name,
            'description' => $attachment->title, // for backward-compat (not sure if that's even needed at this juncture..)
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
            'identifier' => $identifier
        );
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
    	$map = array();
    	$existing = $this->load();

        if (!empty($this->value))
        {
            $guesser = new FileBinaryMimeTypeGuesser;
            foreach ($this->value as $identifier => $data)
            {
                if (array_key_exists($identifier, $existing))
                {
                    $attachment = $existing[$identifier]['object'];
                }
                else
                {
                    if (empty($data['file']))
                    {
                        continue;
                    }
                    if (is_integer($identifier))
                    {
                        $identifier = md5(time() . $data['file']['name'] . $data['file']['tmp_name']);
                    }
                    $attachment = $this->create_attachment($data['file']);
                }
            	$title = null;
            	if (array_key_exists('title', $data))
            	{
            		$title = $data['title'];
            	}
            	if (!empty($data['file']))
            	{
                	$filename = midcom_db_attachment::safe_filename($data['file']['name'], true);
                	$attachment->name = $filename;
                	$attachment->title = ($title !== null) ? $title : $data['file']['name'];
                	$attachment->mimetype = $guesser->guess($data['file']['tmp_name']);
                    if (!$attachment->copy_from_file($data['file']['tmp_name']))
                    {
                        throw new midcom_error('Failed to copy attachment: ' . midcom_connection::get_error_string());
                    }
            	}
            	// No file upload, only title change
            	else if ($attachment->title != $title)
            	{
            	    $attachment->title = $title;
            	    $attachment->update();
            	}
                $map[$identifier] = $attachment->guid;
            }
        }
        //delete attachments which are no longer in map
        foreach (array_diff_key($existing, $map) as $attachment)
        {
        	$attachment['object']->delete();
        }

        return $this->save_attachment_list($map);
    }

    /**
     *
     * @param array $map
     * @return boolean
     */
    protected function save_attachment_list(array $map)
    {
        $list = array();
        foreach ($map as $identifier => $guid)
        {
            $list[] = $identifier . ':' . $guid;
        }
        return $this->object->set_parameter('midcom.helper.datamanager2.type.blobs', "guids_{$this->config['name']}", implode(',', $list));
    }

    /**
     *
     * @return array
     */
    protected function load_attachment_list()
    {
        $map = array();
        $raw_list = $this->object->get_parameter('midcom.helper.datamanager2.type.blobs', "guids_{$this->config['name']}");
        if (!$raw_list)
        {
            return $map;
        }

        foreach (explode(',', $raw_list) as $item)
        {
            $info = explode(':', $item);
            if (count($info) < 2)
            {
                debug_add("item '{$item}' is broken!", MIDCOM_LOG_ERROR);
                continue;
            }
            $identifier = $info[0];
            $guid = $info[1];
            if (mgd_is_guid($guid))
            {
                $map[$identifier] = $guid;
            }
        }
        return $map;
    }

    protected function create_attachment(array $data)
    {
        $filename = midcom_db_attachment::safe_filename($data['name'], true);
        $attachment = $this->object->create_attachment($filename, $data['name'], $data['type']);
        if ($attachment === false)
        {
            throw new midcom_error('Failed to create attachment: ' . \midcom_connection::get_error_string());
        }
        return $attachment;
    }
}