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
class imageTransformer extends blobTransformer
{
    public function transform($input)
    {
        if ($input === null) {
            return [];
        }

        $result = ['objects' => []];
        foreach ($input as $key => $value) {
            if ($key === 'delete' || $key === 'description') {
                $result[$key] = $value;
            } else {
                $result['objects'][$key] = parent::transform($value);
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

        if (!empty($array['objects'])) {
            foreach ($array['objects'] as $key => $value) {
                $result[$key] = parent::reverseTransform($value);
            }
        }
        if (!empty($array['file'])) {
            $result['file'] = parent::reverseTransform($array);
        }
        if (!empty($array['delete'])) {
            $result['delete'] = $array['delete'];
        }
        if (!empty($this->config['widget_config']['show_description'])) {
            $result['description'] = $array['description'];
        }
        return $result;
    }
}
