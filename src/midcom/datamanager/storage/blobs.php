<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

use midcom_db_attachment;
use midcom_error;
use midcom_connection;
use midcom;
use midcom\datamanager\helper\attachment;
use Symfony\Component\Mime\FileBinaryMimeTypeGuesser;

/**
 * Experimental storage class
 */
class blobs extends delayed
{
    use attachment;

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
                    $mimetype = $guesser->guessMimeType($source);

                    if (empty($db_att)) {
                        $db_att = $att;
                        $db_att->parentguid = $this->object->guid;
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
                if (!empty($this->config['widget_config']['show_description'])) {
                    $db_att->set_parameter('midcom.helper.datamanager2.type.blobs', 'description', $this->value['description']);
                }
            }
        }
        //delete attachments which are no longer in map
        foreach (array_diff_key($atts_in_db, $this->map) as $attachment) {
            $attachment->delete();
        }

        $this->save_attachment_list();
    }

    public function move_uploaded_files() : int
    {
        $total_moved = 0;

        foreach ($this->value as $att) {
            if (!is_a($att, midcom_db_attachment::class)) {
                continue;
            }
            if ($att->id !== 0) {
                continue;
            }
            $prefix = midcom::get()->config->get('midcom_tempdir') . '/tmpfile-';
            if (str_starts_with($att->location, $prefix)) {
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

    protected function save_attachment_list() : bool
    {
        if (!empty($this->config['widget_config']['sortable'])) {
            uasort($this->map, function ($a, $b) {
                if ($a->metadata->score == $b->metadata->score) {
                    return strnatcasecmp($a->name, $b->name);
                }
                return $b->metadata->score <=> $a->metadata->score;
            });
        }

        $list = [];

        foreach ($this->map as $identifier => $attachment) {
            $list[] = $identifier . ':' . $attachment->guid;
        }
        return $this->object->set_parameter('midcom.helper.datamanager2.type.blobs', "guids_{$this->config['name']}", implode(',', $list));
    }

    protected function load_attachment_list() : array
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
}
