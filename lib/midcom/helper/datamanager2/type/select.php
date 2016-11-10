<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple select type. This allows the selection of one or more values
 * from a given list. It is possible to enable adding "unreferenced" items in a "others"
 * listing, but those are outside the normal processing.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>array options:</i> The allowed option listing, a key/value map. Only the keys
 *   are stored in the storage location, using serialized storage. If you set this to
 *   null, <i>option_callback</i> has to be defined instead. You may not define both
 *   options.
 * - <i>string option_callback:</i> This must be the name of an available class which
 *   handles the actual option listing. See below how such a class has to look like.
 *   If you set this to null, <i>options</i> has to be defined instead. You may not
 *   define both options.
 * - <i>mixed option_callback_arg:</i> An additional argument passed to the constructor
 *   of the option callback, defaulting to null.
 * - <i>boolean allow_other:</i> If this flag is set, the system allows the addition of
 *   values not in the option list. All unknown values will be merged into a single
 *   comma separated listing of unknown options during loading, which will be kept in
 *   that simple string representation. Otherwise, unknown keys will be forbidden, on
 *   validations they cause a validation error, on loading they are dropped silently.
 *   This option is set to false by default.
 * - <i>boolean allow_multiple:</i> If this flag is set, you may select more than one
 *   option. This is disabled by default. If this feature is disabled, the loader
 *   code will drop all matches beyond the first match.
 * - <i>boolean csv_export_key:</i> If this flag is set, the CSV export will store the
 *   field key instead of its value. This is only useful if the foreign tables referenced
 *   are available at the site of import. This flag is not set by default. Note, that
 *   this does not affect import, which is only available with keys, not values.
 * - <i>string multiple_storagemode:</i> Controls how multiple options are stored in
 *   a single field. See below "multiselect storagemodes". Defaults to "serialized".
 * - <i>boolean sortable:</i> Switch for determining if the order selected by the widget
 *   should be stored to the metadata object
 *
 * Keys should be alphanumeric only.
 *
 * <b>Multiselect storage modes</b>
 *
 * This type knows three ways of storing multiselect data:
 *
 * - 'serialized' will just store a serialized array
 * - 'imploded' will implode the keys using '|' as a separator
 * - 'imploded_wrapped' behaves like 'imploded' except that it will wrap the saved
 *   string again in '|'s thus yielding something like |1|2|3|...|. This is useful
 *   if you want to use like queries to look up values in such fields.
 *
 * Naturally, both 'imploded' storage modes don't allow a '|' being part of a key.
 * This is only checked during storage (due to performance reasons); if an invalid
 * element is found there, it will be skipped and logged. No error will be shown
 * on-site.
 *
 * <b>Option Callback class</b>
 *
 * These classes must implement midcom_helper_datamanager2_interface
 *
 * The class is loaded using require_once by translating it to a path relative to midcom_root
 * prior to instantiation. If the class cannot be loaded from the filesystem but from a
 * snippet, you need to include that snippet previously, an auto-load from there is not
 * yet possible.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_select extends midcom_helper_datamanager2_type
{
    /**
     * A list of the currently selected keys. This is an array even for single select
     * types, in which case the validation limits it to one item. The values array
     * consists only of the object keys, use the resolver function to get the corresponding
     * values.
     *
     * @var array
     */
    public $selection = array();

    /**
     * This member contains the other key, in case it is set. In case of multiselects,
     * the full list of unknown keys is collected here, in case of single select, this value
     * takes precedence from the standard selection.
     *
     * This is only valid if the allow_other flag is set.
     *
     * @var string
     */
    public $others = array();

    /**
     * The options available to the client. You should not access this variable directly,
     * as this information may be loaded on demand, depending on the types configuration.
     *
     * @see get_all_options()
     * @var array
     */
    public $options = array();

    /**
     * In case the options are returned by a callback, this member holds the name of the
     * class.
     *
     * @var string
     */
    public $option_callback = null;

    /**
     * The argument to pass to the option callback constructor.
     *
     * @var mixed
     */
    public $option_callback_arg = null;

    /**
     * Set this to true if you want to allow selection of values not part of the regular
     * selection list. In this case you'll find the other options collected in the $others
     * member.
     *
     * @var boolean
     */
    public $allow_other = false;

    /**
     * This flag controls whether multiple selections are allowed, or not.
     *
     * @var boolean
     */
    public $allow_multiple = false;

    /**
     * Set this to false to use with chooser, this skips making sure the key exists in option list
     * Mainly used to avoid unnecessary seeks to load all a ton of objects to the options list.
     *
     * @var boolean
     */
    public $require_corresponding_option = true;

    /**
     * Set this to true if you want the keys to be exported to the csv dump instead of the
     * values. Note, that this does not affect import, which is only available with keys, not
     * values.
     *
     * @var boolean
     */
    public $csv_export_key = false;

    /**
     * In case the options are returned by a callback, this member holds the callback
     * instance.
     *
     * @var string
     */
    public $_callback = null;

    /**
     * The storage mode used when multiselect is enabled, see the class' introduction for
     * details.
     *
     * @var string
     */
    public $multiple_storagemode = 'serialized';

    /**
     * Glue that will be used for separating the keys
     *
     * @var string
     */
    public $multiple_separator = '|';

    /**
     * Should the sorting feature be enabled. This will affect the way chooser widget will act
     * and how the results will be presented. If the sorting feature is enabled,
     *
     * @var boolean
     */
    public $sortable = false;

    /**
     * Initialize the class, if necessary, create a callback instance, otherwise
     * validate that an option array is present.
     */
    public function _on_initialize()
    {
        if (   !is_array($this->options)
            && $this->option_callback === null)
        {
            throw new midcom_error("Either 'options' or 'option_callback' must be defined for the field {$this->name}");
        }
        if (   !empty($this->options)
            && $this->option_callback !== null)
        {
            throw new midcom_error("Both 'options' and 'option_callback' was defined for the field {$this->name}");
        }

        if ($this->option_callback !== null)
        {
            $this->_callback = $this->initialize_option_callback();
        }

        // Activate serialized storage format if we are in multiselect-mode.
        $this->serialized_storage = ($this->allow_multiple && $this->multiple_storagemode == 'serialized');
    }

    /**
     * Returns the full name for a given key. This value is not localized in any way.
     *
     * @param string $key The key index to look up.
     * @return string The name of the key in clear-text, or null, if the key was not found.
     */
    function get_name_for_key($key)
    {
        $key = (string) $key;

        if (!$this->key_exists($key))
        {
            if ($this->require_corresponding_option)
            {
                return null;
            }
            // This is probably chooser or autocomplete
            // FIXME: This is not exactly an elegant way to do this
            return $this->_get_name_from_object($key);
        }

        if ($this->option_callback === null)
        {
            return $this->options[$key];
        }
        return $this->_callback->get_name_for_key($key);
    }

    private function _get_name_from_object($key)
    {
        $widget_config = $this->storage->_schema->fields[$this->name]['widget_config'];
        if (   empty($widget_config['class'])
            || empty($widget_config['titlefield'])
            || !$key)
        {
            return null;
        }

        if (   !empty($widget_config['component'])
            && !midcom::get()->componentloader->is_loaded($widget_config['component']))
        {
            // Ensure the corresponding component is loaded
            midcom::get()->componentloader->load($widget_config['component']);
        }

        $widget_config['id_field'] = isset($widget_config['id_field']) ? $widget_config['id_field'] : 'guid';
        $qb = new midcom_core_querybuilder($widget_config['class']);
        $qb->add_constraint($widget_config['id_field'], '=', $key);
        $results = $qb->execute();
        if (count($results) != 1)
        {
            debug_add('Failed to load ' . $widget_config['class'] . ' ' . $key . ': ' . count($results) . ' results found, 1 expected');
            return null;
        }
        $field_options = (array) $widget_config['titlefield'];

        foreach ($field_options as $field)
        {
            if (!empty($object->$field))
            {
                return $object->$field;
            }
        }
        return null;
    }

    /**
     * Checks whether the given key is known.
     *
     * @param string $key The key index to look up.
     * @return boolean True if the key is known, false otherwise.
     */
    function key_exists($key)
    {
        $key = (string) $key;

        if ($this->option_callback === null)
        {
            return array_key_exists($key, $this->options);
        }

        if (   isset($this->_callback)
            && method_exists($this->_callback, 'key_exists'))
        {
            return $this->_callback->key_exists($key);
        }

        return false;
    }

    /**
     * Returns the full listing of all available key/value pairs.
     *
     * @return array Listing of all keys, as an associative array.
     */
    function list_all()
    {
        if ($this->option_callback === null)
        {
            return $this->options;
        }
        return $this->_callback->list_all();
    }

    /**
     * Converts storage format to live format, all invalid keys are dropped, and basic validation
     * is done to ensure constraints like allow_multiple are met.
     */
    public function convert_from_storage($source)
    {
        $this->selection = array();
        $this->others = array();

        if (   $source === false
            || $source === null)
        {
            // We are fine at this point.
            return;
        }
        if ($this->allow_multiple)
        {
            // In multiselect mode, we need to convert as per type setting.
            $source = $this->_convert_multiple_from_storage($source);
        }
        else
        {
            // If we aren't in multiselect mode, we don't get an array by default (to have
            // plain storage), therefore we typecast here. This is easier to do than having
            // the same code below twice thus unifying allow_other handling mainly.

            $source = array($source);
        }

        foreach ($source as $key)
        {
            $key = (string) $key;
            if ($this->key_exists($key))
            {
                $this->selection[] = $key;
                if (!$this->allow_multiple)
                {
                    // Whatever happens, in this mode we only have one key.
                    return;
                }
            }
            // Done as separate check instead of || because I'm not 100% sure this is the correct place for it (Rambo)
            elseif (!$this->require_corresponding_option)
            {
                $this->selection[] = $key;
                if (!$this->allow_multiple)
                {
                    // Whatever happens, in this mode we only have one key.
                    return;
                }
            }
            elseif ($this->allow_other)
            {
                $this->others[] = $key;

                if (!$this->allow_multiple)
                {
                    // Whatever happens, in this mode we only have one key.
                    return;
                }
            }
            else
            {
                debug_add("Encountered unknown key {$key} for field {$this->name}, skipping it.", MIDCOM_LOG_INFO);
            }
        }
    }

    /**
     * Merges selection and others arrays, the validation cycle ensures that they are
     * right.
     *
     * @return array The storage information.
     */
    public function convert_to_storage()
    {
        if ($this->allow_multiple)
        {
            return $this->_convert_multiple_to_storage();
        }
        if (   $this->allow_other
            && !empty($this->others))
        {
            return $this->others[0];
        }
        if (count($this->selection) == 0)
        {
            return '';
        }
        return current($this->selection);
    }

    /**
     * Converts the selected options according to the multiple_storagemode setting.
     *
     * @param mixed The stored data.
     * @return array The stored data converted back to an array.
     */
    function _convert_multiple_from_storage($source)
    {
        $glue = $this->multiple_separator;

        switch ($this->multiple_storagemode)
        {
            case 'serialized':
            case 'array':
                if (   !is_array($source)
                    && empty($source))
                {
                    $source = array();
                }
                return $source;

            case 'imploded':
                if (!is_string($source))
                {
                    return array();
                }
                return explode($glue, $source);

            case 'imploded_wrapped':
                if (!is_string($source))
                {
                    return array();
                }
                return explode($glue, substr($source, 1, -1));

            default:
                throw new midcom_error("The multiple_storagemode '{$this->multiple_storagemode}' is invalid, cannot continue.");
        }
    }

    /**
     * Converts the selected options according to the multiple_storagemode setting.
     *
     * @return mixed The data converted to the final data storage.
     */
    function _convert_multiple_to_storage()
    {
        switch ($this->multiple_storagemode)
        {
            case 'array':
                return $this->selection;

            case 'serialized':
                if ($this->others)
                {
                    return array_merge($this->selection, $this->others);
                }
                return $this->selection;

            case 'imploded':
                $options = $this->_get_imploded_options();
                return $options;

            case 'imploded_wrapped':
                $glue = $this->multiple_separator;
                $options = $this->_get_imploded_options();
                return "{$glue}{$options}{$glue}";

            default:
                throw new midcom_error("The multiple_storagemode '{$this->multiple_storagemode}' is invalid, cannot continue.");
        }
    }

    /**
     * Prepares the imploded storage string. All entries containing the pipe char (used as glue)
     * will be logged and skipped silently.
     *
     * @return string The imploded data string.
     */
    private function _get_imploded_options()
    {
        $glue = $this->multiple_separator;

        if ($this->others)
        {
            if (is_string($this->others))
            {
                $this->others = array
                (
                    $this->others => $this->others,
                );
            }
            $options = array_merge($this->selection, $this->others);
        }
        else
        {
            $options = $this->selection;
        }

        $result = array();
        foreach ($options as $key)
        {
            if (strpos($key, $glue) !== false)
            {
                debug_add("The option key '{$key}' contained the multiple separator ({$this->multiple_separator}) char, which is not allowed for imploded storage targets. ignoring silently.",
                    MIDCOM_LOG_WARN);
                continue;
            }

            $result[] = $key;
        }
        return implode($glue, $result);
    }

    /**
     * CSV conversion works from the storage representation, converting the arrays
     * into simple text lists.
     */
    public function convert_from_csv($source)
    {
        $source = explode(',', $source);
        $this->convert_from_storage($source);
    }

    /**
     * CSV conversion works from the storage representation, converting the arrays
     * into simple text lists.
     */
    public function convert_to_csv()
    {
        if ($this->csv_export_key)
        {
            $data = $this->convert_to_storage();
            if (is_array($data))
            {
                return implode(',', $data);
            }
            return $data;
        }
        $values = $this->combine_values();
        return implode($values, ', ');
    }

    /**
     * The validation callback ensures that we don't have an array or an object
     * as a value, which would be wrong.
     *
     * @return boolean Indicating validity.
     */
    public function _on_validate()
    {
        if (   !$this->allow_other
            && $this->others)
        {
            $this->validation_error = $this->_l10n->get('type select: other selection not allowed');
            return false;
        }

        if (   !$this->allow_multiple
            && count($this->selection) > 1)
        {
            $this->validation_error = $this->_l10n->get('type select: multiselect not allowed');
            return false;
        }

        $field = $this->_datamanager->schema->fields[$this->name];
        if (   $field['required']
            && count($this->selection) == 0)
        {
            $this->validation_error = sprintf($this->_l10n->get('field %s is required'), $field['title']);
            return false;
        }

        return true;
    }

    function combine_values()
    {
        $selection = array_map(array($this, 'get_name_for_key'), $this->selection);
        if ($this->others)
        {
            $selection = array_merge($selection, (array) $this->others);
        }
        return $selection;
    }

    public function convert_to_html()
    {
        $values_localized = array_map(array($this, 'translate'), $this->combine_values());
        return implode($values_localized, ', ');
    }
}
