<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
*/

namespace midcom\datamanager\storage;

use midcom\datamanager\helper\imagefilter;

/**
 * Images storage
 *
 * Controls a list of images
 */
class images extends blobs implements recreateable
{
    public function recreate()
    {
        $this->map = [];
        $map = $this->load();

        foreach ($map as $identifier => &$images) {
            $filter = new imagefilter($this->config['type_config']);
            $images = $filter->process($images['main'], $images);

            foreach ($images as $name => $image) {
                $this->map[$identifier . $name . ':' . $image->guid] = $image;
            }
        }
        return $this->save_image_map($map) && $this->save_attachment_list();
    }

    public function load()
    {
        $results = parent::load();
        $grouped = [];

        $identifiers = [];
        if ($raw_list = $this->object->get_parameter('midcom.helper.datamanager2.type.images', "attachment_map_{$this->config['name']}")) {
            $identifiers = explode(',', $raw_list);
        } else {
            // Reconstruct from blobs data
            foreach (array_keys($results) as $identifier) {
                $identifiers[] = $identifier . ':' . substr($identifier, 0, 32) . ':main';
            }
        }

        foreach ($identifiers as $item) {
            list($identifier, $images_identifier, $images_name) = explode(':', $item);

            if (array_key_exists($identifier, $results)) {
                if (!array_key_exists($images_identifier, $grouped)) {
                    $grouped[$images_identifier] = [];
                }
                $grouped[$images_identifier][$images_name] = $results[$identifier];
            }
        }

        return $grouped;
    }

    public function save()
    {
        $this->map = [];
        $map = $this->load();

        foreach ($this->value as $identifier => $images) {
            if (!empty($images['file'])) {
                if (is_numeric($identifier)) {
                    $identifier = md5(time() . $images['file']->name . $images['file']->location);
                }
                $images['file']->parentguid = $this->object->guid;
                $existing = array_key_exists($identifier, $map) ? $map[$identifier] : [];
                $filter = new imagefilter($this->config['type_config']);
                $map[$identifier] = $filter->process($images['file'], $existing);
            }
            if (!empty($map[$identifier])) {
                foreach ($map[$identifier] as $name => $image) {
                    $this->map[$identifier . $name . ':' . $image->guid] = $image;
                }
            }
        }
        return $this->save_image_map($map) && $this->save_attachment_list();
    }

    private function save_image_map(array $map)
    {
        $list = [];

        foreach ($map as $identifier => $derived) {
            foreach (array_keys($derived) as $name) {
                $list[] = $identifier . $name . ':' . $identifier . ':' . $name;
            }
        }

        return $this->object->set_parameter('midcom.helper.datamanager2.type.images', "attachment_map_{$this->config['name']}", implode(',', $list));
    }
}
