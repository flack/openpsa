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
class multiple implements DataTransformerInterface
{
    private $config;

    private $multiple_separator = '|';

    private $multiple_storagemode = 'serialized';

    /**
     * This member contains the other key, in case it is set. In case of multiselects,
     * the full list of unknown keys is collected here, in case of single select, this value
     * takes precedence from the standard selection.
     *
     * This is only valid if the allow_other flag is set.
     * TODO: still to be implentend
     * @var string
     */
    private $others = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        if (!empty($this->config['type_config']['multiple_storagemode'])) {
            $this->multiple_storagemode = $this->config['type_config']['multiple_storagemode'];
        }
    }

    public function transform($input)
    {
        if (   $input === false
            || $input === null) {
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
                if (!is_string($source)) {
                    return [];
                }
                return explode($this->multiple_separator, $input);

            case 'imploded_wrapped':
                if (!is_string($input)) {
                    return [];
                }
                return explode($this->multiple_separator, substr($input, 1, -1));

            default:
                throw new midcom_error("The multiple_storagemode '{$this->multiple_storagemode}' is invalid, cannot continue.");
        }
    }

    public function reverseTransform($array)
    {
        if (!is_array($array) ) {
            throw new TransformationFailedException('Expected an array.');
        }

        if (empty($array)) {
            return;
        }

        switch ($this->multiple_storagemode) {
            case 'array':
                return $array;

            case 'serialized':
                if ($this->others) {
                    return array_merge($array, $this->others);
                }
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
     *
     * @param array $array
     * @return string The imploded data string.
     */
    private function get_imploded_options(array $array)
    {
        $glue = $this->multiple_separator;

        if ($this->others) {
            if (is_string($this->others)) {
                $this->others = [
                    $this->others => $this->others,
                ];
            }
            $options = array_merge($array, $this->others);
        } else {
            $options = $array;
        }

        $result = [];
        foreach ($options as $key) {
            if (strpos($key, $glue) !== false) {
                debug_add("The option key '{$key}' contained the multiple separator ($this->multiple_storagemode) char, which is not allowed for imploded storage targets. ignoring silently.",
                MIDCOM_LOG_WARN);
                continue;
            }

            $result[] = $key;
        }
        return implode($glue, $result);
    }
}
