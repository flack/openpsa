<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

/**
 * Image transformer
 *
 * This handles multiple attachments because of filter chains and such
 */
class imageTransformer extends attachmentTransformer
{
    public function transform($input)
    {
        if ($input === null) {
            return [];
        }

        $result = ['objects' => []];

        foreach ($input as $key => $value) {
            if ($key === 'delete' || $key === 'description' || $key == 'title') {
                $result[$key] = $value;
            } else {
                $result['objects'][$key] = parent::transform($value);
                if ($key === 'file') {
                    $result['identifier'] = $result['objects'][$key]['identifier'];
                }
                if ($key === 'main') {
                    if (!empty($this->config['widget_config']['show_title'])) {
                        $result['title'] = $result['objects'][$key]['title'];
                    }
                    if (!empty($this->config['widget_config']['show_description'])) {
                        $result['description'] = $result['objects'][$key]['description'];
                    }
                }
            }
        }
        return $result;
    }

    public function reverseTransform($array)
    {
        if (empty($array)) {
            return null;
        }
        $result = [];
        if (   !empty($array['file'])
            || !empty($array['identifier']) && substr($array['identifier'], 0, 8) === 'tmpfile-') {
            $result['file'] = parent::reverseTransform($array);
        } elseif (!empty($array['objects'])) {
            foreach ($array['objects'] as $key => $value) {
                $result[$key] = parent::reverseTransform($value);
            }
        }
        if (!empty($array['delete'])) {
            $result['delete'] = $array['delete'];
        }
        if (!empty($this->config['widget_config']['show_description'])) {
            $result['description'] = $array['description'];
        }
        if (!empty($this->config['widget_config']['show_title'])) {
            $result['title'] = $array['title'];
        }
        return $result;
    }
}
