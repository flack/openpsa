<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use midcom_error;

/**
 * Experimental jsdate transformer
 */
class multipleTransformer implements DataTransformerInterface
{
    private array $config;

    private string $multiple_separator = '|';

    private string $multiple_storagemode = 'serialized';

    public function __construct(array $config)
    {
        $this->config = $config;
        if (!empty($this->config['type_config']['multiple_storagemode'])) {
            $this->multiple_storagemode = $this->config['type_config']['multiple_storagemode'];
        }
    }

    public function transform(mixed $input) : mixed
    {
        if (in_array($input, [false, null], true)) {
            return [];
        }

        switch ($this->multiple_storagemode) {
            case 'serialized':
            case 'array':
                if (empty($input)) {
                    return [];
                }

                return unserialize($input);

            case 'imploded':
                if (!is_string($input)) {
                    return [];
                }
                return explode($this->multiple_separator, $input);

            case 'imploded_wrapped':
                if (!is_string($input) || substr($input, 1, -1) == '') {
                    return [];
                }
                $results = explode($this->multiple_separator, substr($input, 1, -1));
                if (   !empty($this->config['widget_config']['id_field'])
                    && $this->config['widget_config']['id_field'] == 'id') {
                    $results = array_map(intval(...), $results);
                }
                return $results;

            default:
                throw new midcom_error("The multiple_storagemode '{$this->multiple_storagemode}' is invalid, cannot continue.");
        }
    }

    public function reverseTransform(mixed $array) : mixed
    {
        if (!is_array($array) ) {
            throw new TransformationFailedException('Expected an array.');
        }

        if (empty($array)) {
            return null;
        }

        switch ($this->multiple_storagemode) {
            case 'array':
                return $array;

            case 'serialized':
                return serialize($array);

            case 'imploded':
                return $this->get_imploded_options($array);

            case 'imploded_wrapped':
                $glue = $this->multiple_separator;
                $options = $this->get_imploded_options($array);
                return "{$glue}{$options}{$glue}";

            default:
                throw new TransformationFailedException('Invalid storage mode ' . $this->multiple_storagemode);
        }
    }

    /**
     * Prepares the imploded storage string. All entries containing the pipe char (used as glue)
     * will be logged and skipped silently.
     */
    private function get_imploded_options(array $array) : string
    {
        $glue = $this->multiple_separator;

        $result = [];
        foreach ($array as $key) {
            if (str_contains($key, $glue)) {
                debug_add("The option key '{$key}' contained the multiple separator ($this->multiple_storagemode) char, which is not allowed for imploded storage targets. ignoring silently.",
                MIDCOM_LOG_WARN);
                continue;
            }

            $result[] = $key;
        }
        return implode($glue, $result);
    }
}
