<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

use Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser;
use midcom_db_attachment;
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
    protected $map = [];

    /**
     * @return midcom_db_attachment[]
     */
    public function load()
    {
        $results = [];
        if (!$this->object->id) {
            return $results;
        }

        $items = $this->load_attachment_list();
        foreach ($items as $identifier => $guid) {
            try {
                $results[$identifier] = new midcom_db_attachment($guid);
            } catch (midcom_error $e) {
                $e->log();
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $this->map = [];
        $atts_in_db = $this->load();

        if (!empty($this->value)) {
            $guesser = new FileBinaryMimeTypeGuesser;
            foreach ($this->value as $identifier => $att) {
                if (!is_a($att, midcom_db_attachment::class)) {
                    continue;
                }

                $db_att = (array_key_exists($identifier, $atts_in_db)) ? $atts_in_db[$identifier] : null;

                // new upload case
                if ($att->id === 0) {
                    $filename = midcom_db_attachment::safe_filename($att->name, true);
                    $title = $att->title ?: $att->name;
                    $source = $att->location;
                    $mimetype = $guesser->guess($source);

                    if (empty($db_att)) {
                        $db_att = $att;
                        $this->create_attachment($db_att, $filename, $title, $mimetype);
                        $identifier = md5(time() . $db_att->name . $source);
                    } else {
                        $db_att->name = $filename;
                        $db_att->title = $title;
                        $db_att->mimetype = $mimetype;
                    }
                    if (!$db_att->copy_from_file($source)) {
                        throw new midcom_error('Failed to copy attachment: ' . midcom_connection::get_error_string());
                    }
                    unlink($source);
                }
                // No file upload, only title change
                elseif ($db_att->title != $att->title) {
                    $db_att->title = $att->title;
                    $db_att->update();
                }
                $this->map[$identifier] = $db_att;
                if (!empty($this->config['widget_config']['sortable'])) {
                    $db_att->metadata->score = (int) $att->metadata->score;
                    $db_att->update();
                }
            }
        }
        //delete attachments which are no longer in map
        foreach (array_diff_key($atts_in_db, $this->map) as $attachment) {
            $attachment->delete();
        }

        if (!empty($this->config['widget_config']['sortable'])) {
            uasort($this->map, function ($a, $b) {
                if ($a->metadata->score == $b->metadata->score) {
                    return strnatcasecmp($a->name, $b->name);
                }
                return $b->metadata->score - $a->metadata->score;
            });
        }

        return $this->save_attachment_list();
    }

    public function move_uploaded_files()
    {
        $total_moved = 0;

        foreach ($this->value as $identifier => $att) {
            if (!is_a($att, midcom_db_attachment::class)) {
                continue;
            }
            if ($att->id !== 0) {
                continue;
            }
            $prefix = midcom::get()->config->get('midcom_tempdir') . '/tmpfile-';
            if (substr($att->location, 0, strlen($prefix)) === $prefix) {
                continue;
            }
            $total_moved++;

            $source = $att->location;
            $att->location = $prefix . md5(time() . $att->name . $source);
            $att->title = $att->name;

            move_uploaded_file($source, $att->location);
        }

        return $total_moved;
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
        if (!$resolver->name_is_unique()) {
            debug_add("Name '{$attachment->name}' is not unique, trying to generate", MIDCOM_LOG_INFO);
            $ext = '';
            if (preg_match('/^(.*)(\..*?)$/', $filename, $ext_matches)) {
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
        $list = [];

        foreach ($this->map as $identifier => $attachment) {
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
        $map = [];
        $raw_list = $this->object->get_parameter('midcom.helper.datamanager2.type.blobs', "guids_{$this->config['name']}");
        if (!$raw_list) {
            return $map;
        }

        foreach (explode(',', $raw_list) as $item) {
            $info = explode(':', $item);
            if (count($info) < 2) {
                debug_add("item '{$item}' is broken!", MIDCOM_LOG_ERROR);
                continue;
            }
            $identifier = $info[0];
            $guid = $info[1];
            if (mgd_is_guid($guid)) {
                $map[$identifier] = $guid;
            }
        }
        return $map;
    }

    /**
     *
     * @param midcom_db_attachment $attachment
     * @param string $filename
     * @param string $title
     * @param string $mimetype
     * @throws midcom_error
     */
    protected function create_attachment($attachment, $filename, $title, $mimetype)
    {
        $attachment->name = $this->generate_unique_name($filename);
        $attachment->title = $title;
        $attachment->mimetype = $mimetype;
        $attachment->parentguid = $this->object->guid;

        if (!$attachment->create()) {
            throw new midcom_error('Failed to create attachment: ' . \midcom_connection::get_error_string());
        }
    }
}
