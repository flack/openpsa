<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\storage;

use midcom_error;

/**
 * Experimental storage class
 */
class parameter extends delayed
{
    private $multiple_separator = '|';

    /**
     * This member contains the other key, in case it is set. In case of multiselects,
     * the full list of unknown keys is collected here, in case of single select, this value
     * takes precedence from the standard selection.
     *
     * This is only valid if the allow_other flag is set.
     * TODO: still to be implentend
     * @var string
     */
    private $other = [];

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        $source = $this->object->get_parameter($this->config['storage']['domain'], $this->config['storage']['name']);

        if (!empty($this->config['type_config']['allow_multiple'])) {
            $source = $this->convert_multiple_from_storage($source);
        }

        return $source;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        //workaround for weird mgd API behavior where setting falsy (i.e. deleting) a nonexistent parameter
        //returns an error
        if (   !$this->value
            && $this->load() === null) {
            return true;
        }

        $value = $this->value;
        if (!empty($this->config['type_config']['allow_multiple'])) {
            $value = $this->convert_multiple_to_storage();
        }

        return $this->object->set_parameter($this->config['storage']['domain'], $this->config['storage']['name'], $value);
    }

    private function get_mode()
    {
        return (empty($this->config['type_config']['multiple_storagemode'])) ? 'serialized' : $this->config['type_config']['multiple_storagemode'];
    }

    private function convert_multiple_from_storage($source)
    {
        $glue = $this->multiple_separator;

        switch ($this->get_mode()) {
            case 'serialized':
            case 'array':
                if (   !is_array($source)
                    && empty($source)) {
                    $source = [];
                }
                return $source;

            case 'imploded':
                if (!is_string($source)) {
                    return [];
                }
                return explode($glue, $source);

            case 'imploded_wrapped':
                if (!is_string($source)) {
                    return [];
                }
                return explode($glue, substr($source, 1, -1));

            default:
                throw new midcom_error("The multiple_storagemode '{$this->get_mode()}' is invalid, cannot continue.");
        }
    }

    /**
     * Converts the selected options according to the multiple_storagemode setting.
     *
     * @return mixed The data converted to the final data storage.
     */
    private function convert_multiple_to_storage()
    {
        switch ($this->get_mode()) {
            case 'array':
                return $this->value;

            case 'serialized':
                if ($this->others) {
                    return array_merge($this->value, $this->others);
                }
                return $this->value;

            case 'imploded':
                $options = $this->get_imploded_options();
                return $options;

            case 'imploded_wrapped':
                $glue = $this->multiple_separator;
                $options = $this->get_imploded_options();
                return "{$glue}{$options}{$glue}";

            default:
                throw new midcom_error("The multiple_storagemode '{$this->get_mode()}' is invalid, cannot continue.");
        }
    }

    /**
     * Prepares the imploded storage string. All entries containing the pipe char (used as glue)
     * will be logged and skipped silently.
     *
     * @return string The imploded data string.
     */
    private function get_imploded_options()
    {
        $glue = $this->multiple_separator;

        if ($this->others) {
            if (is_string($this->others)) {
                $this->others = [
                    $this->others => $this->others,
                ];
            }
            $options = array_merge($this->value, $this->others);
        } else {
            $options = $this->value;
        }

        $result = [];
        foreach ($options as $key) {
            if (strpos($key, $glue) !== false) {
                debug_add("The option key '{$key}' contained the multiple separator ($this->config['type_config']['multiple_storagemode']) char, which is not allowed for imploded storage targets. ignoring silently.",
                MIDCOM_LOG_WARN);
                continue;
            }

            $result[] = $key;
        }
        return implode($glue, $result);
    }
}