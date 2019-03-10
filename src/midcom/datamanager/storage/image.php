<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

use midcom\datamanager\helper\imagefilter;

/**
 * Image storage
 *
 * Controls a list of images derived from just one (via filter chains)
 */
class image extends blobs implements recreateable
{
    protected $save_archival = false;

    public function recreate()
    {
        $existing = $this->load();
        if (array_key_exists('archival', $existing)) {
            $attachment = $existing['archival'];
        } elseif (array_key_exists('main', $existing)) {
            $attachment = $existing['main'];
        } else {
            return true;
        }

        $filter = new imagefilter($this->config['type_config'], $this->save_archival);
        $this->map = $filter->process($attachment, $existing);
        return $this->save_attachment_list();
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        if ($this->value === null) {
            //delete?
        }

        if (!empty($this->value['delete'])) {
            $this->map = [];
            return $this->save_attachment_list();
        }

        $stat = true;
        if (!empty($this->value['file'])) {
            $this->value['file']->parentguid = $this->object->guid;
            $existing = $this->load();
            $filter = new imagefilter($this->config['type_config'], $this->save_archival);
            $this->map = $filter->process($this->value['file'], $existing);

            $stat = $this->save_attachment_list();
        }

        $check_fields = ['description', 'title', 'score'];

        if (array_intersect_key(array_keys($check_fields), $this->value)) {
            $main = $this->get_main();
            $needs_update = false;
            foreach ($check_fields as $field) {
                if (array_key_exists($field, $this->value)) {
                    if ($field === 'description') {
                        $main->set_parameter('midcom.helper.datamanager2.type.blobs', 'description', $this->value['description']);
                    } elseif ($field === 'title') {
                        $needs_update = $needs_update || $main->title != $this->value['title'];
                        $main->title = $this->value['title'];
                    } elseif ($field === 'score') {
                        $needs_update = $needs_update || $main->metadata->score != $this->value['score'];
                        $main->metadata->score = (int) $this->value['score'];
                    }
                }
            }
            if ($needs_update) {
                $main->update();
            }
        }

        return $stat;
    }

    /**
     * @return \midcom_db_attachment
     */
    private function get_main()
    {
        if (!empty($this->map['main'])) {
            return $this->map['main'];
        }
        $items = $this->load();
        if (!empty($items['main'])) {
            return $items['main'];
        }
        return false;
    }
}
