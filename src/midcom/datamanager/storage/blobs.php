<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

use Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser;
use midcom_db_attachment;
use midcom_helper_misc;
use midcom_helper_reflector_nameresolver;
use midcom_error;
use midcom_connection;
use midcom;

/**
 * Experimental storage class
 */
class blobs extends delayed
{
    /**
     *
     * @var midcom_db_attachment[]
     */
    protected $map = array();

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
        $this->map = array();
        $existing = $this->load();

        if (!empty($this->value))
        {
            $guesser = new FileBinaryMimeTypeGuesser;
            foreach ($this->value as $identifier => $data)
            {
                $attachment = (array_key_exists($identifier, $existing)) ? $existing[$identifier]['object'] : null;
                $title = (array_key_exists('title', $data)) ? $data['title'] : null;
                if (!empty($data['file']))
                {
                    $filename = midcom_db_attachment::safe_filename($data['file']['name'], true);
                    $title = $title ?: $data['file']['name'];
                    $mimetype = $guesser->guess($data['file']['tmp_name']);
                    if (!$attachment)
                    {
                        $attachment = $this->create_attachment($filename, $title, $mimetype);
                        if (is_integer($identifier))
                        {
                            $identifier = md5(time() . $data['file']['name'] . $data['file']['tmp_name']);
                        }
                    }
                    else
                    {
                        if ($attachment->name != $filename)
                        {
                            $filename = $this->generate_unique_name($filename);
                        }
                        $attachment->name = $filename;
                        $attachment->title = $title;
                        $attachment->mimetype = $mimetype;
                    }
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
                $this->map[$identifier] = $attachment;
            }
        }
        //delete attachments which are no longer in map
        foreach (array_diff_key($existing, $this->map) as $attachment)
        {
            $attachment['object']->delete();
        }

        return $this->save_attachment_list();
    }

    /**
     * Make sure we have unique filename
     */
    protected function generate_unique_name($filename)
    {
        $filename = midcom_db_attachment::safe_filename($filename, true);
        $attachment = new midcom_db_attachment;
        $attachment->name = $filename;
        $attachment->parentguid = $this->object->guid;

        $resolver = new midcom_helper_reflector_nameresolver($attachment);
        if (!$resolver->name_is_unique())
        {
            debug_add("Name '{$attachment->name}' is not unique, trying to generate", MIDCOM_LOG_INFO);
            $ext = '';
            if (preg_match('/^(.*)(\..*?)$/', $filename, $ext_matches))
            {
                $ext = $ext_matches[2];
            }
            $filename = $resolver->generate_unique_name('name', $ext);
        }
        return $filename;
    }

    /**
     *
     * @return boolean
     */
    protected function save_attachment_list()
    {
        $list = array();
        foreach ($this->map as $identifier => $attachment)
        {
            $list[] = $identifier . ':' . $attachment->guid;
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

    protected function create_attachment($filename, $title, $mimetype)
    {
        $filename = $this->generate_unique_name($filename);
        $attachment = $this->object->create_attachment($filename, $title, $mimetype);
        if ($attachment === false)
        {
            throw new midcom_error('Failed to create attachment: ' . \midcom_connection::get_error_string());
        }
        return $attachment;
    }
}