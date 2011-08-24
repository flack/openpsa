<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 date datatype
 *
 * <b>Available configuration options:</b>
 *
 * - <i>string storage_type:</i> Defines the storage format of the date. The default
 *   is 'ISO', see below for details.
 *
 * <b>Available storage formats:</b>
 *
 * - ISO: YYYY-MM-DD HH:MM:SS
 * - ISO_DATE: YYYY-MM-DD
 * - ISO_EXTENDED: YYYY-MM-DDTHH:MM:SS(Z|[+-]HH:MM)
 * - ISO_EXTENDED_MICROTIME: YYYY-MM-DDTHH:MM:SS.S(Z|[+-]HH:MM)
 * - UNIXTIME: Unix Timestamps (seconds since epoch)
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_date extends midcom_helper_datamanager2_type
{
    /**
     * The current date encapsulated by this type.
     *
     * @var DateTime
     */
    var $value = null;

    /**
     * The storage type to use, see the class introduction for details.
     *
     * @var string
     */
    var $storage_type = 'ISO';

    /**
     * Possible date field that must be earlier than this date field
     *
     * @var string
     */
    var $later_than = '';

    /**
     * Maximum date which the $value should not exceed, can be passed
     * in schema
     */
    var $max_date = null;

    /**
     * Minimum date which the $value should not exceed, can be passed
     * in schema
     */
    var $min_date = null;

    /**
     * Initialize the value with an empty DateTime object.
     */
    public function _on_configuring($config)
    {
        $this->value = new DateTime();
    }

    public function _on_validate()
    {
        if (!$this->_validate_date_range())
        {
            return false;
        }
        if (empty($this->later_than))
        {
            return true;
        }

        if (   !isset($this->_datamanager->types[$this->later_than])
            || !is_a($this->_datamanager->types[$this->later_than], 'midcom_helper_datamanager2_type_date')
            || !$this->_datamanager->types[$this->later_than]->value)
        {
            debug_add("Failed to validate date field {$this->name} with {$this->later_than}, as such date field wasn't found.",
                MIDCOM_LOG_INFO);
            $this->validation_error = sprintf($this->_l10n->get('type date: failed to compare date with field %s'), $this->later_than);
            return false;
        }

        if ($this->value < $this->_datamanager->types[$this->later_than]->value)
        {

            //And the award for most horrible API call goes to...
            $earlier_field_label = $this->_datamanager->schema->translate_schema_string($this->_datamanager->schema->fields[$this->later_than]['title']);

            $this->validation_error = sprintf($this->_l10n->get('type date: this date must be later than %s'), $earlier_field_label);
            return false;
        }

        return true;
    }

    /**
     * This function uses the DateTime constructor to handle the conversion.
     * It should be able to deal with all three storage variants transparently.
     *
     * @param mixed $source The storage data structure.
     */
    function convert_from_storage ($source)
    {
        if (null === $source)
        {
            $this->value = null;
        }
        else if (!$source)
        {
            $this->value = new DateTime('0000-00-00 00:00:00');
        }
        else
        {
            if (is_int($source))
            {
                $source = strftime('%Y-%m-%d %H:%M:%S', $source);
            }
            $this->value = new DateTime($source);
        }
    }

    /**
     * Converts DateTime object to storage representation.
     *
     * @return string The string representation of the date according to the
     *     storage_type.
     */
    function convert_to_storage()
    {
        switch ($this->storage_type)
        {
            case 'ISO':
                if ($this->is_empty())
                {
                    return '0000-00-00 00:00:00';
                }
                else
                {
                    return $this->value->format('Y-m-d H:i:s');
                }

            case 'ISO_DATE':
                if ($this->is_empty())
                {
                    return '0000-00-00';
                }
                else
                {
                    return $this->value->format('Y-m-d');
                }

            case 'ISO_EXTENDED':
            case 'ISO_EXTENDED_MICROTIME':
                if ($this->is_empty())
                {
                    return '0000-00-00T00:00:00.0';
                }
                else
                {
                    return str_replace(',', '.', $this->value->format('c'));
                }

            case 'UNIXTIME':
                if ($this->is_empty())
                {
                    return 0;
                }
                else
                {
                    return (int) $this->value->format('U');
                }

            default:
                throw new midcom_error("Invalid storage type for the Datamanager date type: {$this->storage_type}");
        }
    }

    /**
     * CSV conversion is mapped to regular type conversion.
     */
    function convert_from_csv ($source)
    {
        $this->convert_from_storage($source);
    }

    /**
     * CSV conversion is mapped to regular type conversion.
     */
    function convert_to_csv()
    {
        if ($this->is_empty())
        {
            return '';
        }
        $format = $this->_get_format('short date csv');
        return $this->value->format($format);
    }

    function convert_to_html()
    {
        if ($this->is_empty())
        {
            return '';
        }
        else
        {
            $format = $this->_get_format();
            return htmlspecialchars($this->value->format($format));
        }
    }

    /**
     * Helper function that returns the localized date format with or without time
     *
     * @param string $base The format we want to use
     */
    private function _get_format($base = 'short date')
    {
        $format = $this->_l10n_midcom->get($base);
        // FIXME: This is not exactly an elegant way to do this
        $widget_conf = $this->storage->_schema->fields[$this->name]['widget_config'];
        if (    $this->storage_type != 'ISO_DATE'
            && (   !array_key_exists('show_time', $widget_conf)
                || $widget_conf['show_time']))
        {
            $format .= ' H:i';
            if (   array_key_exists('hide_seconds', $widget_conf)
                && !$widget_conf['hide_seconds'])
            {
                $format .= ':s';
            }
        }
        return $format;
    }

    /**
     * Tries to detect whether the date value entered is empty in terms of the Midgard
     * core. For this, all values are compared to zero, if all tests succeed, the date
     * is considered empty.
     *
     * @return boolean Indicating Emptyness state.
     */
    function is_empty()
    {
        if (null === $this->value)
        {
            return true;
        }

        return ($this->value->format('Y') == '-0001');
    }

    private function _validate_date_range()
    {
        if ($this->is_empty())
        {
            return true;
        }

        //sometimes the date-compare function seems to corrupt the date, by changing timezone maybe
        if (   !empty($this->min_date)
            && $this->value < new DateTime($this->min_date))
        {
            $min_date = new DateTime($this->min_date);
            $this->validation_error = sprintf($this->_l10n->get('type date: this date must be at least %s or later'), htmlspecialchars($min_date->format($format)));
            return false;
        }
        if (   !empty($this->max_date)
            && $this->value > new DateTime($this->max_date))
        {
            $max_date = new DateTime($this->max_date);
            $this->validation_error = sprintf($this->_l10n->get('type date: this date must be earlier or be %s'), htmlspecialchars($max_date->format($format)));
            return false;
        }

        return true;
    }
}
?>
