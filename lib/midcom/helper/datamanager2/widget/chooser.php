<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Chooser widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
 *
 * It can only be bound to a select type (or subclass thereof), and inherits the configuration
 * from there as far as possible.
 *
 * <b>Available configuration options:</b>
 *
 *
 * Example: (The simplest ones)
 * <code>
 * 'contacts' => Array
 * (
 *     'title' => 'contacts',
 *     'storage' => null,
 *     'type' => 'select',
 *     'type_config' => array
 *     (
 *          'multiple_storagemode' => 'array',
 *     ),
 *     'widget' => 'chooser',
 *     'widget_config' => array
 *     (
 *         'clever_class' => 'contact',
 *     ),
 * ),
 * </code>
 * OR
 * <code>
 *  'styles' => Array
 *  (
 *      'title' => 'styles',
 *      'storage' => null,
 *      'type' => 'select',
 *      'type_config' => array
 *      (
 *           'multiple_storagemode' => 'array',
 *      ),
 *      'widget' => 'chooser',
 *      'widget_config' => array
 *      (
 *          'clever_class' => 'style',
 *      ),
 *  ),
 * </code>
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_chooser extends midcom_helper_datamanager2_widget
{
    /**
     * id of the input element
     *
     * @var String
     */
    private $_element_id = "chooser_widget";

    /**
     * Array of options that are passed to javascript widget
     *
     * @var Array
     */
    private $_js_widget_options = array();

    private $_input_element = null;

    /**
     * Class to search for
     *
     * @var string
     */
    var $class = null;

    /**
     * Which component the searched class belongs to
     *
     * @var string
     */
    var $component = null;

    /**
     * Clever class
     *
     * @var string
     */
    var $clever_class = null;

    /**
     * Array of constraints (besides the search term), always AND
     *
     * Attention: If the type defines constraints, they always take precedence
     *
     * Example:
     * <code>
     *     'constraints' => array
     *     (
     *         array
     *         (
     *             'field' => 'username',
     *             'op' => '<>',
     *             'value' => '',
     *         ),
     *     ),
     * </code>
     *
     * @var array
     */
    var $constraints = array();

    /**
     * Fields/properties to show on results
     *
     * Example:
     * <code>
     *      'result_headers' => array
     *      (
     *          array
     *          (
     *              'name' => 'firstname',
     *              'title' => 'first name',
     *          ),
     *          array
     *          (
     *              'name' => 'lastname',
     *              'title' => 'last name',
     *          ),
     *      ),
     * </code>
     *
     * @var array
     */
    var $result_headers = array();

    /**
     * In search results replaces given field with full path to the object
     *
     * Example: (in topics)
     * <code>
     *      'generate_path_for' => 'extra',
     * </code>
     *
     * @var array
     */
    var $generate_path_for = null;

    /**
     * Fields/properties to search the keyword for, always OR and specified after the constraints above
     *
     * Example:
     * <code>
     *      'searchfields' => array
     *      (
     *          'firstname',
     *          'lastname',
     *          'email',
     *          'username'
     *      ),
     * </code>
     *
     * @var array
     */
    var $searchfields = array();

    /**
     * associative array of ordering info, always added last
     *
     * Example:
     * <code>
     *     'orders' => array
     *     (
     *         array
     *         (
     *             'lastname' => 'ASC',
     *         ),
     *         array
     *         (
     *             'firstname' => 'ASC',
     *         )
     *     ),
     * </code>
     *
     * @var array
     */
    var $orders = array();

    /**
     * Field/property to use as the key/id
     *
     * @var string
     */
    var $id_field = 'guid';

    /**
     * These options are always visible
     *
     * @var array
     */
    var $static_options = array();

    /**
     * Minimum characters necessary to trigger a search
     *
     * @var integer
     */
    var $min_chars = 2;

    /**
     * Whether to automatically append/prepend wildcards to the query
     *
     * Valid values: 'both', 'start', 'end' and <empty> (0, '', false & null)
     *
     * Example:
     * <code>
     *     'auto_wildcards' => 'both',
     * </code>
     *
     * @var string
     */
    var $auto_wildcards = 'end';

    /**
     * The javascript to append to the page
     *
     * @var string
     */
    private $_jscript = '';

    /**
     * In case the options are returned by a callback, this member holds the class.
     *
     * @var class
     */
    private $_callback = false;

    /**
     * In case the options are returned by a callback, this member holds the name of the
     * class.
     *
     * @var string
     */
    private $_callback_class = null;

    /**
     * The arguments to pass to the option callback constructor.
     *
     * @var mixed
     */
    private $_callback_args = null;

    /**
     * Renderer
     *
     * @var mixed
     */
    var $renderer = null;

    /**
     * Renderer callback
     *
     * @var class
     */
    private $_renderer_callback = false;

    /**
     * Renderer callback class name
     *
     * @var string
     */
    private $_renderer_callback_class = null;

    /**
     * Renderer callback arguments
     *
     * @var array
     */
    private $_renderer_callback_args = array();

    /**
     * The group of widgets items as QuickForm elements
     *
     * @var array
     */
    var $widget_elements = array();

    private $_static_items_html = "";
    private $_added_static_items = array();

    var $allow_multiple = true;
    var $require_corresponding_option = true;

    var $reflector_key = null;

    var $creation_mode_enabled = null;
    var $creation_handler = null;
    var $creation_default_key = null;

    var $js_format_items = array();

    /**
     * Should the sorting be enabled
     *
     * @var boolean    True if the sorting should be enabled
     */
    var $sortable = null;

    /**
     * Default search terms to provide with the chooser
     *
     * @var string Default search terms
     */
    var $default_search = null;

    /**
     * The initialization event handler post-processes the maxlength setting.
     *
     * @return boolean Indicating Success
     */
    public function _on_initialize()
    {
        if (!is_a($this->_type, 'midcom_helper_datamanager2_type_select'))
        {
            debug_add("Warning, the field {$this->name} is not a select type or subclass thereof, you cannot use the chooser widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        // Determine the sortability from the type configuration
        if (   is_null($this->sortable)
            && isset($this->_type->sortable))
        {
            $this->sortable = $this->_type->sortable;
        }

        $this->_callback_class = $this->_type->option_callback;
        $this->_callback_args = $this->_type->option_callback_arg;

        $this->allow_multiple = $this->_type->allow_multiple;
        $this->require_corresponding_option = $this->_type->require_corresponding_option;

        if (   empty($this->class)
            && empty($this->component)
            && empty($this->clever_class))
        {
            if (   !isset($this->_callback_class)
                || empty($this->_callback_class))
            {
                debug_add("Warning, the field {$this->name} does not have proper class definitions set.",
                    MIDCOM_LOG_WARN);

                return false;
            }
        }

        if (   !empty($this->renderer)
            && !$this->_check_renderer())
        {
            debug_add("Warning, the field {$this->name} renderer wasn't found or not set properly, thus widget can never show results.",
                MIDCOM_LOG_WARN);
            return false;
        }

        if (!empty($this->_type->constraints))
        {
            $this->constraints = $this->_type->constraints;
        }

        if (!$this->_check_class())
        {
            debug_add("Warning, cannot load class {$this->class} for field {$this->name}.",
                MIDCOM_LOG_WARN);
            return false;
        }

        if (!$this->_is_replication_safe())
        {
            return false;
        }

        if (   empty($this->searchfields)
            && !isset($this->_callback_class))
        {
            debug_add("Warning, the field {$this->name} does not have searchfields defined, it can never return results.",
                MIDCOM_LOG_WARN);
            return false;
        }

        midcom::get('head')->enable_jquery();

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/chooser/jquery.chooser_widget.css');

        midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.mouse.min.js');

        if ($this->sortable)
        {
            midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.draggable.min.js');
            midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.droppable.min.js');
            midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.sortable.min.js');
        }

        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/chooser/jquery.chooser_widget.js');

        $this->_element_id = "{$this->_namespace}{$this->name}_chooser_widget";

        if (!is_null($this->creation_handler))
        {
            $this->_enable_creation_mode();
        }

        $this->_init_widget_options();

        return true;
    }

    /**
     * Check that the id_field is replication-safe
     */
    private function _is_replication_safe()
    {
        if (   $this->id_field == 'guid'
            || is_a($this->_type, 'midcom_helper_datamanager2_type_mnrelation'))
        {
            return true;
        }
        switch ($this->_field['storage']['location'])
        {
            case 'parameter':
            case 'configuration':
                // Storing IDs to parameters is not replication safe
                debug_add("Field \"{$this->name}\" is set to store to a parameter but links via ID which is not replication-safe, aborting.", MIDCOM_LOG_WARN);

                midcom::get('uimessages')->add($this->_l10n->get('midcom.helper.datamanager2'), sprintf($this->_l10n->get('field %s is set to store to a parameter but links via ID which is not replication-safe, aborting'), $this->name), 'error');

                return false;
        }

        // Normal field, verify that it is a link
        if (   $this->_type->storage->object !== null
            && !is_a($this->_type->storage->object, 'midcom_core_temporary_object'))

        {
            // We have an object, check the link type
            // Note: in creation mode we do not have this so we have no way to check)
            $mgdschema_object = midcom::get('dbfactory')->convert_midcom_to_midgard($this->_type->storage->object);
            if (    $mgdschema_object !== null
                 && $this->_field['storage']['location'] !== null)
            {
                $mrp = new midgard_reflection_property(get_class($mgdschema_object));

                if (   !$mrp
                    || !$mrp->is_link($this->_field['storage']['location']))
                {
                    // Storing IDs to non-linked fields is not replication safe
                    debug_add("Field \"{$this->name}\" is set to store to property \"{$this->_field['storage']['location']}\" which is not link, making it replication-unsafe, aborting.", MIDCOM_LOG_WARN);

                    midcom::get('uimessages')->add($this->_l10n->get('midcom.helper.datamanager2'), sprintf($this->_l10n->get('field %s is set to store to property %s but links via ID which is not replication-safe, aborting'), $this->name, $this->_field['storage']['location']), 'error');

                    return false;
                }
            }
        }
        return true;
    }

    private function _enable_creation_mode()
    {
        if (!empty($this->creation_handler))
        {
            $this->creation_mode_enabled = true;
        }
    }

    private function _check_renderer()
    {
        if (!isset($this->renderer['class']))
        {
            return false;
        }

        $this->_renderer_callback_class = $this->renderer['class'];
        $this->_renderer_callback_args = array();
        if (   isset($this->renderer['args'])
            && !empty($this->renderer['args']))
        {
            $this->_renderer_callback_args = $this->renderer['args'];
        }

        if (!class_exists($this->_renderer_callback_class))
        {
            // Try auto-load.
            $path = MIDCOM_ROOT . '/' . str_replace('_', '/', $this->_renderer_callback_class) . '.php';
            if (! file_exists($path))
            {
                debug_add("Auto-loading of the renderer callback class {$this->_renderer_callback_class} from {$path} failed: File does not exist.", MIDCOM_LOG_ERROR);
                return false;
            }
            require_once($path);
        }

        if (!class_exists($this->_renderer_callback_class))
        {
            debug_add("The renderer callback class {$this->_renderer_callback_class} was defined as option for the field {$this->name} but did not exist.", MIDCOM_LOG_ERROR);
            return false;
        }
        $this->_renderer_callback = new $this->_renderer_callback_class($this->_renderer_callback_args);

        return $this->_renderer_callback->initialize();
    }

    private function _check_class()
    {
        if (!empty($this->clever_class))
        {
            return $this->_check_clever_class();
        }

        if (!empty($this->_callback_class))
        {
            $this->_callback = $this->_type->initialize_option_callback();
            return $this->_callback;
        }

        if (class_exists($this->class))
        {
            return true;
        }

        if (!empty($this->component))
        {
            midcom::get('componentloader')->load($this->component);
        }

        return class_exists($this->class);
    }

    private function _check_clever_class()
    {
        $clever_classes = $this->_config->get('clever_classes');

        if (array_key_exists($this->clever_class, $clever_classes))
        {
            $this->_initialize_from_clever_class($clever_classes[$this->clever_class]);
            return true;
        }
        else
        {
            return $this->_initialize_from_reflector();
        }
    }

    private function _initialize_from_clever_class($class)
    {
        $this->class = $class['class'];
        $this->component = $class['component'];

        if (!empty($this->component))
        {
            midcom::get('componentloader')->load($this->component);
        }

        if (empty($this->result_headers))
        {
            $this->result_headers = array();
            foreach ($class['result_headers'] as $header_data)
            {
                if ($this->component)
                {
                    $header_data['title'] = midcom::get('i18n')->get_string($header_data['title'], $this->component);
                }
                else
                {
                    $header_data['title'] = $this->_l10n_midcom->get($header_data['title']);
                }
                $this->result_headers[] = $header_data;
            }
        }

        $config_fields = array('generate_path_for', 'constraints', 'searchfields', 'orders', 'reflector_key', 'id_field');

        foreach ($config_fields as $field)
        {
            if (   isset($class[$field])
                && empty($this->$field))
            {
                $this->$field = $class[$field];
            }
        }
    }

    private function _initialize_from_reflector()
    {
        $matching_type = false;
        $matched_types = array();
        foreach (midcom_connection::get_schema_types() as $schema_type)
        {
            $pos = strpos($schema_type, $this->clever_class);
            if ($pos !== false)
            {
                $matched_types[] = $schema_type;
            }
        }

        if (count($matched_types) == 1)
        {
            $matching_type = $matched_types[0];
        }
        else
        {
            if ($this->clever_class == 'event')
            {
                $this->creation_default_key = 'title';
            }
            else if ($this->clever_class == 'person')
            {
                $matching_type = 'midgard_person';
            }
            else
            {
                if (count($matched_types) > 0)
                {
                    $matching_type = $matched_types[0];
                }
            }
        }

        if (!$matching_type)
        {
            debug_add("no matches found for {$this->clever_class}!");
            return false;
        }

        $midcom_reflector = new midcom_helper_reflector($matching_type);

        $labels = array();

        $dummy_object = new $matching_type();
        $type_fields = array_keys(get_object_vars($dummy_object));

        unset($type_fields['metadata']);
        foreach ($type_fields as $key)
        {
            if (in_array($key, array('title','firstname','lastname','name','email','start','end','location')))
            {
                if (! in_array($key, $labels))
                {
                    $labels[] = $key;
                }
            }
        }

        if (empty($labels))
        {
            $label_properties = $midcom_reflector->get_label_property();
            if (is_array($label_properties))
            {
                foreach ($label_properties as $key)
                {
                    if (! in_array($key, array('id', 'guid')))
                    {
                        if (! in_array($key, $labels))
                        {
                            $labels[] = $key;
                        }
                    }
                }
            }
        }

        $this->class = $_MIDCOM->dbclassloader->get_midcom_class_name_for_mgdschema_object($matching_type);
        $this->component = $_MIDCOM->dbclassloader->get_component_for_class($matching_type);

        if (empty($this->constraints))
        {
            $this->constraints = array();
        }
        if (empty($this->searchfields))
        {
            $this->searchfields = $midcom_reflector->get_search_properties();
            if (empty($this->searchfields))
            {
                //TODO: Special rules for objects that need them
            }
        }
        if (empty($this->orders))
        {
            $this->orders = array();
        }

        $reflection_l10n = $midcom_reflector->get_component_l10n();
        if (empty($this->result_headers))
        {
            $this->result_headers = array();
            foreach ($labels as $label)
            {
                $header = array();
                $header['title'] = $reflection_l10n->get($label);
                $header['name'] = $label;
                $this->result_headers[] = $header;
            }

            if (empty($this->result_headers))
            {
                //Special rules for objects that need them
            }
        }

        if (   $this->creation_mode_enabled
            && empty($this->creation_default_key))
        {
            $this->creation_default_key = $this->result_headers[0]['name'];
        }
    }

    function _init_widget_options()
    {
        $this->_js_widget_options['widget_id'] = $this->_element_id;
        $this->_js_widget_options['min_chars'] = $this->min_chars;
        $this->_js_widget_options['result_limit'] = 10;
        $this->_js_widget_options['renderer_callback'] = false;
        $this->_js_widget_options['result_headers'] = array();
        $this->_js_widget_options['allow_multiple'] = true;
        $this->_js_widget_options['id_field'] = $this->id_field;
        $this->_js_widget_options['format_items'] = null;

        if ($this->generate_path_for)
        {
            $this->_js_widget_options['generate_path_for'] = $this->generate_path_for;
        }

        if ($this->sortable)
        {
            $this->_js_widget_options['sortable'] = true;
        }

        if ($this->creation_mode_enabled)
        {
            $this->_js_widget_options['creation_mode'] = true;
            $this->_js_widget_options['creation_handler'] = $this->creation_handler;
            $this->_js_widget_options['creation_default_key'] = $this->creation_default_key;
        }

        if (isset($this->max_results))
        {
            $this->_js_widget_options['result_limit'] = $this->result_limit;
        }
        if (isset($this->renderer_callback))
        {
            $this->_js_widget_options['renderer_callback'] = $this->renderer_callback;
        }
        if (isset($this->allow_multiple))
        {
            $this->_js_widget_options['allow_multiple'] = false;
            if ($this->allow_multiple)
            {
                $this->_js_widget_options['allow_multiple'] = true;
            }
        }
        if (! empty($this->js_format_items))
        {
            $this->_js_widget_options['format_items'] = $this->js_format_items;
        }

        $headers = array();
        $header_count = count($this->result_headers);
        foreach ($this->result_headers as $k => $header_item)
        {
            $header_title = midcom::get('i18n')->get_string(midcom::get('i18n')->get_string($header_item['title'], $this->component), 'midcom');

            $headers[] = array
            (
                'title' => $header_title,
                'name' => $header_item['name']
            );
        }
        $this->_js_widget_options['result_headers'] = $headers;

        $this->_generate_extra_params();
    }

    function _generate_extra_params()
    {
        $map = array
        (
            'component', 'class',
            '_callback_class', '_callback_args',
            '_renderer_callback_class', '_renderer_callback_args',
            'constraints', 'searchfields', 'orders',
            'result_headers', 'generate_path_for',
            'auto_wildcards',
            'reflector_key'
        );

        $params = array();
        foreach ($map as $map_key)
        {
            $params[$map_key] = $this->$map_key;
        }

        $this->_js_widget_options['extra_params'] = base64_encode(serialize($params));
    }

    /**
     * Internal helper for parsing the $_REQUEST data for including the elements requested via GET or POST
     *
     * @return Array
     */
    private function _get_request_elements()
    {
        $results = array();

        // No results available
        if (!isset($_REQUEST["{$this->_element_id}_selections"]))
        {
            return $results;
        }

        foreach ($_REQUEST["{$this->_element_id}_selections"] as $guid => $value)
        {
            if (!$value)
            {
                continue;
            }

            $results[$guid] = $value;
        }

        return $results;
    }

    /**
     * Adds a simple search form and place holder for results.
     * Also adds static options to results.
     */
    function add_elements_to_form($attributes)
    {
        // Get url to search handler
        $nav = new midcom_helper_nav();
        $root_node = $nav->get_node($nav->get_root_node());
        if (empty($root_node))
        {
            return;
        }
        $this->_handler_url = $root_node[MIDCOM_NAV_FULLURL] . 'midcom-exec-midcom.helper.datamanager2/chooser_handler.php';

        $this->widget_elements[] = HTML_QuickForm::createElement
        (
            'hidden',
            "{$this->_element_id}_handler_url",
            $this->_handler_url,
            array
            (
                'id' => "{$this->_element_id}_handler_url",
            )
        );

        // Text input for the search box
        $search_input = HTML_QuickForm::createElement
        (
            'text',
            "{$this->_element_id}_search_input",
            $this->_translate($this->_field['title']),
            array_merge($attributes, array
            (
                'class'         => 'shorttext chooser_widget_search_input',
                'id'            => "{$this->_element_id}_search_input",
                'style'         => "display: none;",
            ))
        );

        if ($this->default_search)
        {
            $search_input->setValue($this->default_search);
            $this->_js_widget_options['default_search'] = true;
        }

        $this->widget_elements[] = $search_input;

        if ($this->creation_mode_enabled)
        {
            $dialog_id = $this->_element_id . '_creation_dialog';

            $dialog_html = "<div class=\"chooser_widget_creation_dialog\" id=\"{$dialog_id}\">\n";
            $dialog_html .= "    <div class=\"chooser_widget_creation_dialog_content_holder\">\n";
            $dialog_html .= "    </div>\n";
            $dialog_html .= "</div>\n";

            $button_html = "<div class=\"chooser_widget_create_button\" id=\"{$this->_element_id}_create_button\" style=\"display: none;\">\n";
            $button_html .= "</div>\n";

            $html = $button_html . $dialog_html;

            $this->widget_elements[] = HTML_QuickForm::createElement
            (
                'static',
                "{$this->_element_id}_creation_dialog_holder",
                '',
                $html
            );
        }

        $this->_jscript .= '<script type="text/javascript">';
        $this->_jscript .= 'jQuery().ready(function(){';

        $script = "jQuery('#{$this->_element_id}_search_input').midcom_helper_datamanager2_widget_chooser_widget('{$this->_handler_url}',\n";
        $script .= json_encode($this->_js_widget_options);
        $script .= ");";
        $this->_jscript .= $script;

        $this->_static_items_html .= "<table class=\"widget_chooser_static_items_table\">\n";
        $this->_static_items_html .= "    <thead>\n";
        $this->_static_items_html .= "        <tr>\n";

        if (   !empty($this->reflector_key)
            && !$this->result_headers)
        {
            $title = midcom::get('i18n')->get_string('Label', 'midcom');
            $this->_static_items_html .= "            <th class=\"label\">{$title}&nbsp;</th>\n";
        }
        else
        {
            foreach ($this->result_headers as $header_item)
            {
                $header_title = midcom::get('i18n')->get_string(midcom::get('i18n')->get_string($header_item['title'], $this->component), 'midcom');
                $this->_static_items_html .= "            <th class=\"{$header_item['name']}\">{$header_title}&nbsp;</th>\n";
            }
        }

        $title = midcom::get('i18n')->get_string('Selected', 'midcom.helper.datamanager2');
        $this->_static_items_html .= "            <th class=\"selected\">{$title}&nbsp;</th>\n";
        $this->_static_items_html .= "        </tr>\n";
        $this->_static_items_html .= "    </thead>\n";
        $this->_static_items_html .= "    <tbody>\n";

        $this->_add_items_to_form();

        $this->_static_items_html .= "    </tbody>\n";
        $this->_static_items_html .= "</table>\n";

        $this->widget_elements[] = HTML_QuickForm::createElement
        (
            'static',
            "{$this->_element_id}_initscripts",
            '',
            $this->_jscript
        );

        $this->widget_elements[] = HTML_QuickForm::createElement
        (
            'static',
            "{$this->_element_id}_noscript",
            '',
            $this->_static_items_html
        );
        $this->widget_elements[] = HTML_QuickForm::createElement
        (
            'hidden',
            "{$this->_element_id}_selections",
            ''
        );

        $this->_form->addGroup($this->widget_elements, $this->name, $this->_translate($this->_field['title']), '', array('class' => 'midcom_helper_datamanager2_widget_chooser'));
        if ($this->_field['required'])
        {
            $errmsg = sprintf($this->_l10n->get('field %s is required'), $this->_translate($this->_field['title']));
            $this->_form->addGroupRule($this->name, array
            (
                "{$this->_element_id}_widget_selections" => array
                (
                    array($errmsg, 'required'),
                ),
            ));
        }
    }

    private function _add_items_to_form()
    {
        // Add existing and static selections
        $existing_elements = $this->_type->selection;

        // Add to existing elements the ones requested (POST/GET) to this page
        $new_elements = $this->_get_request_elements();

        $elements = array_merge($this->static_options, $existing_elements, $new_elements);

        $ee_script = '';
        if ($this->_renderer_callback)
        {
            foreach ($elements as $key)
            {
                $data = $this->_get_key_data($key);
                if ($data)
                {
                    $item = $this->_renderer_callback->render_data($data);
                    $ee_script .= "jQuery('#{$this->_element_id}_search_input').midcom_helper_datamanager2_widget_chooser_add_result_item({$data},'{$item}');\n";

                    $this->_add_existing_item_as_static($key);
                }
            }
        }
        else
        {
            foreach ($elements as $key)
            {
                $data = $this->_get_key_data($key);
                if ($data)
                {
                    $ee_script .= "\n";
                    $ee_script .= "jQuery('#{$this->_element_id}_search_input')\n";
                    $ee_script .= ".midcom_helper_datamanager2_widget_chooser_add_result_item(\n";
                    $ee_script .= "    {$data},\n";
                    $ee_script .= "    this\n";
                    $ee_script .= ");\n";

                    $this->_add_existing_item_as_static($key);
                }
            }
        }

        $this->_jscript .= $ee_script;
        $this->_jscript .= "\njQuery('#" . $this->_element_id . "_search_input').midcom_helper_datamanager2_widget_chooser_adjust_height();";
        $this->_jscript .= '});';

        $this->_jscript .= "\nclose_dialog = function(widget_id){jQuery('#' + widget_id + '_creation_dialog').hide();};";
        $this->_jscript .= "\nadd_item = function(data, widget_id){jQuery('#' + widget_id + '_search_input').midcom_helper_datamanager2_widget_chooser_add_result_item(data);};";
        $this->_jscript .= '</script>';
    }

    private function _add_existing_item_as_static($key)
    {
        $object = $this->_get_key_data($key, false, true);
        $id_field = $this->id_field;
        $item_id = @$object->$id_field;

        if (   empty($item_id)
            || isset($this->_added_static_items[$item_id]))
        {
            return;
        }

        $this->_static_items_html .= "    <tr id=\"{$this->_element_id}_existing_item_{$item_id}_row\" class=\"chooser_widget_existing_item_static_row\">\n";

        if (   !empty($this->reflector_key)
            && !$this->result_headers)
        {
            $value = @$object->get_label();
            $title = midcom::get('i18n')->get_string('label', 'midcom');
            $this->_static_items_html .= "<td class=\"label\">{$value}&nbsp;</td>\n";
        }
        else
        {
            foreach ($this->result_headers as $header_item)
            {
                $item_name = $header_item['name'];
                $value = @$object->$item_name;
                if ($item_name === $this->generate_path_for)
                {
                    $value = self::resolve_path($object, $value);
                }
                $this->_static_items_html .= "<td class=\"{$item_name}\">{$value}&nbsp;</td>";
            }
        }

        $this->_static_items_html .= "<td>\n";
        $this->_static_items_html .= "<input type=\"checkbox\" name=\"{$this->_element_id}_selections[{$item_id}]\" id=\"{$this->_element_id}_existing_item_{$item_id}_value\" class=\"chooser_widget_existing_item_static_input\" checked=\"checked\"/>\n";
        $this->_static_items_html .= "</td>\n";

        $this->_static_items_html .= "</tr>\n";

        $this->_added_static_items[$item_id] = true;
    }

    private function _resolve_object_name($object)
    {
        if (!class_exists('midcom_helper_reflector'))
        {
            return get_class($object) . " #{$object->id}";
        }
        $ref = new midcom_helper_reflector($object);
        return $ref->get_object_label($object);
    }

    private function _object_to_jsdata($object)
    {
        $id = @$object->id;
        $guid = @$object->guid;

        $jsdata = array
        (
            'id' => $id,
            'guid' => $guid,
            'pre_selected' => true,
        );

        if (   !empty($this->reflector_key)
            && !$this->result_headers)
        {
            $value = @$object->get_label();
            $jsdata['label'] = $value;
        }
        else
        {
            foreach ($this->result_headers as $header_item)
            {
                $item_name = $header_item['name'];
                $value = @$object->$item_name;
                if ($item_name === $this->generate_path_for)
                {
                    $value = self::resolve_path($object, $value);
                }
                $tmp = str_replace('.', '_', $item_name);

                $jsdata[$tmp] = $value;
            }
        }

        return json_encode($jsdata);
    }

    private function _get_key_data($key, $in_render_mode = false, $return_object = false)
    {
        if ($this->_callback)
        {
            if ($in_render_mode)
            {
                return $this->_callback->get_name_for_key($key);
            }

            $results = $this->_callback->get_key_data($key);

            if (! $results)
            {
                return false;
            }

            if (   $this->_renderer_callback
                || $return_object)
            {
                return $results;
            }

            return $this->_object_to_jsdata($results);
        }

        midcom::get('auth')->request_sudo();

        if (!class_exists($this->class))
        {
            midcom::get('componentloader')->load_graceful($this->component);
        }

        $qb = @call_user_func(array($this->class, 'new_query_builder'));
        if (!$qb)
        {
            $qb = new midgard_query_builder($this->class);
        }

        $qb->add_constraint($this->id_field, '=', $key);
        foreach ($this->constraints as $constraint)
        {
            $qb->add_constraint($constraint['field'], $constraint['op'], $constraint['value']);
        }
        $results = $qb->execute();

        midcom::get('auth')->drop_sudo();

        if (count($results) == 0)
        {
            return false;
        }

        $object = $results[0];

        if ($return_object)
        {
            return $object;
        }

        if ($in_render_mode)
        {
            return $this->_resolve_object_name($object);
        }

        if ($this->_renderer_callback)
        {
            return $object;
        }

        return $this->_object_to_jsdata($object);
    }

    function freeze()
    {
        foreach ($this->widget_elements as $element)
        {
            if (method_exists($element, 'freeze'))
            {
                $element->freeze();
            }
        }
    }

    /**
     * Unfreezes all form elements associated with the widget.
     *
     * The default implementation works on the default field name, you don't need to
     * override this function unless you have multiple widgets in the form.
     *
     * This maps to the HTML_QuickForm_element::unfreeze() function.
     */
    function unfreeze()
    {
        foreach ($this->widget_elements as $element)
        {
            if (method_exists($element, 'freeze'))
            {
                $element->unfreeze();
            }
        }
    }

    function is_frozen()
    {
        foreach ($this->widget_elements as $element)
        {
            if (method_exists($element, 'isFrozen')
                && !$element->isFrozen())
            {
                return false;
            }
        }
        return true;
    }

    /**
     * The defaults of the widget are mapped to the current selection.
     */
    function get_default()
    {
        if (sizeof($this->_type->selection) == 0)
        {
            return null;
        }
        $defaults = array();
        foreach ($this->_type->selection as $key)
        {
            $defaults[$key] = true;
        }
        return array($this->name => $defaults);
    }

    /**
     * Reads the given get/post data and puts to type->selection
     */
    function sync_type_with_widget($results)
    {
        $this->_type->selection = array();
        if (!isset($results["{$this->_element_id}_selections"]))
        {
            return;
        }

        $real_results = $results["{$this->_element_id}_selections"];
        if (is_array($real_results))
        {
            foreach ($real_results as $key => $value)
            {
                if (   $value != "0"
                    || $value != 0)
                {
                    $this->_type->selection[] = $key;
                }
            }
        }
        else if (!$this->allow_multiple)
        {
            $this->_type->selection[] = $real_results;
        }

        if (   $this->sortable
            && isset($results[$this->_element_id])
            && isset($results[$this->_element_id]['sortable']))
        {
            $this->_type->sorted_order = array();

            foreach ($results[$this->_element_id]['sortable'] as $i => $id)
            {
                $this->_type->sorted_order[$id] = $i;
            }
        }
    }

    public function render_content()
    {
        $output = "<table class=\"chooser_results\">\n";
        if (count($this->_type->selection) == 0)
        {
            $output .= "    <tr>\n";
            $output .= "        <td>" . $this->_translate('type select: no selection') . "</td>\n";
            $output .= "    </tr>\n";
        }
        else
        {
            foreach ($this->_type->selection as $key)
            {
                if (   !$key
                    && count($this->_type->selection) == 1)
                {
                    $output .= "    <tr>\n";
                    $output .= "        <td>" . $this->_translate('type select: no selection') . "</td>\n";
                    $output .= "    </tr>\n";
                    continue;
                }

                $data = rawurldecode($this->_get_key_data($key, true));
                $output .= "    <tr>\n";
                $output .= "        <td>{$data}</td>\n";
                $output .= "    </tr>";
            }
        }
        $output .= "</table>\n";
        return $output;
    }

    /**
     * Helper method to resolve path for an object
     */
    public static function resolve_path(&$object, $title)
    {
        if (!class_exists('midcom_helper_reflector_tree'))
        {
            return $title;
        }
        return midcom_helper_reflector_tree::resolve_path($object);
    }
}
?>
