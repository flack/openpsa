<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Form Manager core class.
 *
 * This class controls all form rendering and basic form data i/o. It works independent
 * of any data storage, getting its defaults from some external controlling instance in
 * the form of a type array (f.x. a datamanager class can provide this). The list of types
 * is taken by-reference.
 *
 * The form rendering is done using the widgets and is based on HTML_QuickForm.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_formmanager extends midcom_baseclasses_components_purecode
{
    /**
     * The schema (not the schema <i>database!</i>) to use for operation.
     *
     * This variable will always contain a parsed representation of the schema,
     * so that one can swiftly switch between individual schemas of the Database.
     *
     * @var midcom_helper_datamanager2_schema
     * @access protected
     */
    var $_schema = null;

    /**
     * The list of types which should be used for rendering. They must match the schemadb passed
     * to the class.
     *
     * The member is initialized by-reference.
     *
     * @var Array
     * @access protected
     */
    var $_types = null;

    /**
     * A list of widgets, indexed by the field names from the schema, thus matching the type
     * listing.
     *
     * @var Array
     */
    var $widgets = [];

    /**
     * This is the QuickForm generated out of the schema. This member is set during the
     * initialize call.
     *
     * @var HTML_Quickform
     */
    var $form = null;

    /**
     * This is the renderer that quickform will use.
     *
     * <i>It is set using the set_renderer() or create_renderer() calls.</i>
     *
     * If the configuration option 'default_renderer' (and possibly 'default_renderer_src')
     * are set, the class will create instances of these renderers during startup, so that
     * site users can directly use these defaults without further work. The following
     * rules are used when determining which renderer to use:
     *
     * If the default_renderer_src config option is null (the default), the class assumes that
     * a default MidCOM renderer from the renderer subdirectory of this component should be used.
     * In this case, you set only the actual renderer name (e.g. "simple") in default_renderer.
     *
     * On the other hand, if you set the default_renderer_src option, the system first loads
     * the snippet identified by that config option. the default_renderer option then has to
     * hold the name of the renderer class that should be created. It must be default
     * constructible.
     *
     * If you don't want any renderer to kick in, set the (default) renderer to 'none'
     * will stick to the QF Default renderer.
     *
     * @var HTML_Quickform_Renderer
     */
    var $renderer = null;

    /**
     * The namespace of the form. This value is to be considered read only.
     *
     * This is the Namespace to use for all HTML/CSS/JS elements. It is deduced by the formmanager
     * and tries to be as smart as possible to work safely with more than one form on a page.
     *
     * You have to prefix all elements which must be unique using this string (it includes a trailing
     * underscore).
     *
     * @var const string
     */
    public $namespace = '';

    /**
     * Number of open fieldsets. This will be used to close the same amount to prevent open fieldset tags
     *
     * @var integer
     */
    private $_fieldsets = 0;

    private $_defaults = [];

    /**
     * State of the form manager
     *
     * @var string
     */
    public $state = 'edit';

    /**
     * Initializes the Form manager with a list of types for a given schema.
     *
     * @param midcom_helper_datamanager2_schema $schema The schema to use for processing.
     * @param array &$types A list of types matching the passed schema, used as a basis for the
     *     form types. This variable is taken by reference.
     */
    public function __construct(midcom_helper_datamanager2_schema $schema, array &$types, $state = 'edit')
    {
        parent::__construct();

        $this->_schema = $schema;
        $this->_types =& $types;
        $this->state = $state;
    }

    /**
     * This function will create all widget objects for the current schema.
     *
     * @param string $name The name of the field for which we should load the widget.
     */
    protected function _load_widget($name, $initialize_dependencies = false)
    {
        $config = $this->_schema->fields[$name];
        $classname = $config['widget'];
        if (strpos($classname, '_') === false) {
            // Built-in widget called using the shorthand notation
            $classname = "midcom_helper_datamanager2_widget_{$config['widget']}";
        }

        $this->widgets[$name] = new $classname($this->renderer);
        if (!$this->widgets[$name] instanceof midcom_helper_datamanager2_widget) {
            throw new midcom_error("{$classname} is not a valid DM2 widget");
        }

        if ($this->widgets[$name]->initialize($name, $config['widget_config'], $this->_schema, $this->_types[$name], $this->namespace, $initialize_dependencies) === false) {
            throw new midcom_error("Failed to initialize the widget for {$name}");
        }
    }

    /**
     * This function fully initializes the class for operation.
     *
     * @param mixed $name The name of the form. This defaults to the name of the currently active component, which should
     *     suffice in most cases.
     * @return boolean Indicating success.
     */
    function initialize($name = null)
    {
        if ($name === null) {
            $name = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT);
            // Replace the dots in the component name with underscores
            $name = midcom::get()->componentloader->path_to_prefix($name);
        }
        if (!$name) {
            // Fallback for componentless operation
            $name = 'midcom_helper_datamanager2';
        }

        $this->namespace = "{$name}_";

        // TODO: make configurable to get URL from midcom_core_context::get()->get_key(MIDCOM_CONTEXT_URI) instead, see #1262
        $this->form = new HTML_QuickForm($name, 'post', $_SERVER['REQUEST_URI'], '_self', ['id' => $name], true);
        $this->_defaults = [];
        $this->widgets = [];

        // Create the default renderer specified in the configuration.
        $this->_create_default_renderer();
        $this->_create_form();

        return true;
    }

    private function _create_form()
    {
        $rulemanager = new midcom_helper_datamanager2_qfrule_manager($this->form, $this->_l10n);
        // iterate over all widgets so that they can add their piece to the form
        foreach ($this->_schema->field_order as $name) {
            if (!isset($this->_schema->fields[$name])) {
                debug_add("Field {$name} is not present in \$this->_schema->fields (read from \$this->_schema->field_order)", MIDCOM_LOG_ERROR);
                continue;
            }
            $config = $this->_schema->fields[$name];
            if (!$this->_is_widget_visible($name, $config)) {
                // Naturally we should skip invisible objects
                continue;
            }

            $this->_load_widget($name);

            // Start the fieldsets if required
            $this->_start_fieldset($name, $config);

            if ($config['static_prepend'] !== null) {
                $static_name = "static_prepend_{$name}";
                $this->form->addElement('static', $static_name, '', "<span class=\"static_prepend\">" . $this->_translate($config['static_prepend']) . "</span>");
            }

            $this->widgets[$name]->set_form($this->form);
            $this->widgets[$name]->set_state($this->state);

            //Load custom QF rules, so that they can be used in widgets' add_element_to_form calls
            $rulemanager->load_type_rules($this->_schema->fields[$name]['type']);
            $attributes = [
                'helptext' => $this->_translate($config['helptext']),
                'id' => $this->namespace . $name
            ];
            $this->widgets[$name]->add_elements_to_form($attributes);
            if (!$this->_check_freeze_status($name, $config)) {
                // rules make only sense if an element is not frozen, e.g. editable by the user
                $rulemanager->add_type_rules($this->_types[$name], $config);
            }

            $this->_load_field_default($name, $config);

            if ($config['static_append'] !== null) {
                $static_name = "__static_append_{$name}";
                $this->form->addElement('static', $static_name, '', "<span class=\"static_append\">" . $this->_translate($config['static_append']) . "</span>");
            }

            // End the fieldsets if required
            $this->_end_fieldset($name, $config);
        }

        // Set the collected defaults.
        $this->form->setDefaults($this->_defaults);

        // Close the fieldsets left open
        if ($this->_fieldsets > 0) {
            $this->_end_fieldset('', ['end_fieldset' => $this->_fieldsets]);
        }

        $this->_add_operation_buttons();
        $rulemanager->add_validation_rules($this->_schema->validation);
        $this->_add_filter_rules();

        // Translate the required note
        $this->form->setRequiredNote(
            '<span style="font-size:80%; color:#ff0000;">*</span><span style="font-size:80%;">' .
            $this->_l10n->get('denotes required field') .
            '</span>'
        );
    }

    private function _load_field_default($name, $config)
    {
        $field_default = $this->widgets[$name]->get_default();
        if (   null === $field_default
            && !empty($config['default'])) {
            // Empty value from widget, run defaults
            $field_default = $config['default'];
        }

        if ($field_default !== null) {
            if (is_object($field_default)) {
                debug_add("An object has been passed as default value for {$name}, this is not allowed, skipping default.",
                    MIDCOM_LOG_WARN);
                debug_print_r('Passed object was:', $field_default);
                return;
            }
            if (is_array($field_default)) {
                $this->_defaults = array_merge($this->_defaults, $field_default);
            } else {
                $this->_defaults[$name] = $field_default;
            }
        }
    }

    private function _add_operation_buttons()
    {
        $buttons = [];
        foreach ($this->_schema->operations as $operation => $button_labels) {
            foreach ((array) $button_labels as $key => $label) {
                if ($label == '') {
                    $label = "form submit: {$operation}";
                }
                $buttonname = "midcom_helper_datamanager2_{$operation}[{$key}]";
                $buttonlabel = $this->_translate($label);

                $class = 'submit ' . $operation;
                $accesskey = '';
                if ($operation == 'save') {
                    $accesskey = 's';
                    $class .= ' save_'.$key;
                } elseif ($operation == 'cancel') {
                    $accesskey = 'c';
                }

                $buttons[] = $this->form->createElement('submit', $buttonname, $buttonlabel, ['class' => $class, 'accesskey' => $accesskey]);
            }
        }

        $this->form->addGroup($buttons, 'form_toolbar', null, '&nbsp;', false);
    }

    /**
     * Add form-wide filter rules
     */
    private function _add_filter_rules()
    {
        foreach ($this->_schema->filters as $config) {
            if (!class_exists($config['callback'])) {
                // Try autoload:
                if (array_key_exists('autoload_snippet', $config)) {
                    midcom_helper_misc::include_snippet_php($config['autoload_snippet']);
                }
                if (array_key_exists('autoload_file', $config)) {
                    require_once($config['autoload_file']);
                }

                if (!class_exists($config['callback'])) {
                    debug_add("Failed to register the callback {$config['callback']} for validation, the class is not defined.", MIDCOM_ERRCRIT);
                    continue;
                }
            }

            // Now create the instance
            if (array_key_exists('constructor_argument', $config)) {
                $arg = $config['constructor_argument'];
            } else {
                $arg = null;
            }
            $callback_object = new $config['callback']($this, $arg);
            $callback = [&$callback_object, 'execute'];

            // Compute the field list.
            if (!empty($config['fields'])) {
                $fields = (array) $config['fields'];
            } else {
                $fields = $this->_schema->field_order;
            }

            // Now fire away.
            foreach ($fields as $name) {
                $callback_object->set_fieldname($name);
                $this->form->applyFilter($name, $callback);
            }
        }
    }

    /**
     * Start a fieldset and set the styles accordingly. Fieldsets can be initialized
     * by key `start_fieldset`
     * @see midcom_helper_datamanager2_schema
     *
     * @param string $name
     * @param array $config
     */
    private function _start_fieldset($name, $config)
    {
        // Return if fieldsets are not requested
        if (!isset($config['start_fieldset'])) {
            return;
        }

        // Enable multiple fieldset starts in the same schema field
        $fieldsets = [];
        if (isset($config['start_fieldset']['title'])) {
            $fieldsets[] = $config['start_fieldset'];
        } else {
            $fieldsets = $config['start_fieldset'];
        }

        // Output the fieldsets
        foreach ($fieldsets as $key => $fieldset) {
            if (isset($fieldset['css_group'])) {
                $class = $fieldset['css_group'];
            } else {
                $class = $name;
            }

            $html = "<fieldset class=\"fieldset {$class}\">\n";

            if (!empty($fieldset['title'])) {
                if (isset($fieldset['css_title'])) {
                    $class = " class=\"{$fieldset['css_title']}\"";
                } else {
                    $class = " class=\"{$name}\"";
                }

                $html .= "    <legend{$class}>\n";
                $html .= "        ". $this->_translate($fieldset['title']) ."\n";
                $html .= "    </legend>\n";
            }

            if (isset($fieldset['description'])) {
                $html .= "<p>". $this->_translate($fieldset['description']) . "</p>\n";
            }

            $set = $this->form->createElement('static', "__fieldset_start_{$name}_{$key}", "");
            $this->renderer->setElementTemplate($html, "__fieldset_start_{$name}_{$key}");
            $this->form->addElement($set);
            $this->_fieldsets++;
        }
    }

    /**
     * End a fieldset. Ends the requested amount of fieldsets when 'end_fieldset' key is
     * defined in the schema field or at least once when the key exists in the field.
     *
     * @param string $name
     * @param array $config
     */
    private function _end_fieldset($name, $config)
    {
        if (   !isset($config['end_fieldset'])
            || $this->_fieldsets <= 0) {
            return;
        }

        $html = '';

        // Interface for closing the fieldsets
        if (is_numeric($config['end_fieldset'])) {
            for ($i = 0; $i < $config['end_fieldset']; $i++) {
                $html .= "</fieldset>\n";
                $this->_fieldsets--;

                if ($this->_fieldsets <= 0) {
                    break;
                }
            }
        } else {
            $html .= "</fieldset>\n";
            $this->_fieldsets--;
        }

        $set = $this->form->createElement('static', "__fieldset_end_{$name}", "");
        $this->renderer->setElementTemplate($html, "__fieldset_end_{$name}");
        $this->form->addElement($set);
    }

    /**
     * Sets the form's renderer based on an existing renderer instance.
     *
     * @param HTML_QuickForm_Renderer $renderer A prepared renderer instance.
     */
    function set_renderer(HTML_QuickForm_Renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Creates a new instance of the renderer specified by $name. It must be a valid renderer
     * name of the renderers defined in the renderer subdirectory of this component. The file
     * loaded is renderer/$name.php, while the class instance created is
     * midcom_helper_datamanager2_renderer_{$name}. The class must be default constructible
     * and will be available in the renderer member of this class.
     *
     * If name is 'none', no renderer instance is created, instead the default QuickForm
     * Renderer is activated.
     *
     * You cannot create custom renderer instances with this function, you need to create the
     * instance manually and set it using set_renderer().
     *
     * @param string $name The renderer to create
     */
    function create_renderer($name)
    {
        if ($name == 'none') {
            $this->renderer = 'none';
        } else {
            $classname = "midcom_helper_datamanager2_renderer_{$name}";
            $this->renderer = new $classname($this->namespace);
        }
    }

    /**
     * This helper function checks whether a given widget is visible.
     *
     * @param string $name The name of the widget.
     * @param array $config Widget configuration.
     * @return boolean Visibility state.
     */
    private function _is_widget_visible($name, $config)
    {
        if ($config['hidden']) {
            return false;
        }

        if ($config['read_privilege'] !== null) {
            if (   array_key_exists('group', $config['read_privilege'])
                && !midcom::get()->auth->is_group_member($config['read_privilege']['group'])) {
                return false;
            }
            if (   array_key_exists('privilege', $config['read_privilege'])
                && !$this->_types[$name]->can_do($config['read_privilege']['privilege'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Special form freeze handling.
     */
    public function freeze()
    {
        $this->form->freeze();
        foreach ($this->widgets as $widget) {
            $widget->freeze();
        }
    }

    /**
     * Special form freeze handling.
     */
    public function unfreeze()
    {
        $this->form->unfreeze();
        foreach ($this->widgets as $widget) {
            $widget->unfreeze();
        }
    }

    /**
     * Checks if widget is editable and sets freeze status accordingly
     *
     * @param string $name The name of the widget.
     * @param array $config Widget configuration.
     * @return True when widget is frozen, false otherwise
     */
    private function _check_freeze_status($name, array $config)
    {
        $widget = $this->widgets[$name];
        if ($config['readonly']) {
            $widget->freeze();
        }
        if ($config['write_privilege'] !== null) {
            if (   array_key_exists('group', $config['write_privilege'])
                && !midcom::get()->auth->is_group_member($config['write_privilege']['group'])) {
                $widget->freeze();
            }
            if (   array_key_exists('privilege', $config['write_privilege'])
                && !$this->_types[$name]->can_do($config['write_privilege']['privilege'])) {
                $widget->freeze();
            }
        }
        return $widget->is_frozen();
    }

    /**
     * Set the value of a formelement.
     *
     * @param string $key the form field name
     * @param string $value the new value to set
     */
    function set_value($key, $value)
    {
        $element = $this->form->getElement($key);
        $element->setValue($value);
    }

    /**
     * Creates an instance of the renderer set in the system configuration.
     *
     * This is called during the initialize code and will make the renderer
     * available immediately after startup.
     */
    private function _create_default_renderer()
    {
        $default = $this->_config->get('default_renderer');
        if ($default == 'none') {
            $this->renderer = 'none';
        } elseif (strpos($default, '_') === false) {
            $this->create_renderer($default);
        } else {
            if (!class_exists($default)) {
                $src = $this->_config->get('default_renderer_src');

                if (!$src) {
                    throw new midcom_error('Invalid renderer configuration');
                }
                midcom_helper_misc::include_snippet_php($src);
                if (!class_exists($default)) {
                    throw new midcom_error("The renderer class '{$default}' set in the DM2 configuration does not exist.");
                }
            }
            $this->renderer = new $default($this->namespace);
        }
    }

    /**
     * This call will render the form.
     */
    function display_form()
    {
        if (   !$this->renderer
            ||  (is_string($this->renderer) && $this->renderer == 'none')) {
            echo $this->form->toHtml();
        } else {
            $this->form->accept($this->renderer);
            echo $this->renderer->toHtml();
        }
    }

    /**
     * This function displays a quick view of the record, using some simple div based layout,
     * which can be formatted using CSS.
     *
     * @todo Make this better customizable
     * @todo Factor this out into a separate class This function should be deprecated.
     */
    function display_view()
    {
        // iterate over all widgets so that they can add their piece to the form
        foreach ($this->widgets as $name => $widget) {
            echo '<div class="title" style="font-weight: bold;">' . $this->_translate($this->_schema->fields[$name]['title']) . "</div>\n";
            echo '<div class="value" style="margin-left: 5em;">';
            echo $widget->render_content();
            echo "</div>\n";
        }
    }

    /**
     * This function computes the form result based on the button clicked and on the current
     * validation result.
     *
     * Validation is only checked when save/next has been clicked; if it fails, the exitcode
     * reverts to 'edit'.
     *
     * @return string One of 'editing', 'save', 'next', 'previous' and 'cancel'
     */
    function compute_form_result()
    {
        $this->form->getSubmitValues(true);
        $exitcode = self::get_clicked_button();

        if (   $exitcode == 'save'
            || $exitcode == 'next') {
            // Validate the form.
            if (!$this->form->validate()) {
                debug_add('Failed to validate the form, reverting to edit mode.');
                $exitcode = 'edit';
            }
        }
        return $exitcode;
    }

    /**
     * Use this function to get the values of submitted form without going through
     * a storage backend.
     *
     * @return array the submitted values.
     */
    function get_submit_values()
    {
        return $this->form->getSubmitValues( true );
    }

    /**
     * Get the user input for one particular field in the storage format
     *
     * @param string $field The field to query
     */
    public function get_value($field)
    {
        if (!array_key_exists($field, $this->widgets)) {
            debug_add('Widget ' . $field . ' not found in form', MIDCOM_LOG_WARN);
            return null;
        }
        $this->widgets[$field]->sync_type_with_widget($this->get_submit_values());
        return $this->_types[$field]->convert_to_storage();
    }

    /**
     * Call this before any output is made. It will process the form results, if applicable,
     * and return an according exit code.
     *
     * This indicates which (if any) submit button was pressed. If 'editing' is
     * returned, this means that either there was not data submitted yet, or that
     * form validation has failed.
     *
     * //This call ensures that MidCOM runs uncached.
     *
     * @return string One of 'editing', 'save', 'next', 'previous' and 'cancel'
     */
    function process_form($ajax_mode = false)
    {
        // Make sure we have CSS loaded
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");

        $results = $this->form->getSubmitValues(true);

        // Get the result (this includes validation)
        $exitcode = $this->compute_form_result();

        // Check if we were really submitted, if yes, trigger the on_submit event
        // on the widgets as well:
        if (   $exitcode != 'cancel'
            && $exitcode != 'previous') {
            foreach ($this->widgets as $name => $copy) {
                $this->widgets[$name]->on_submit($results);
            }
        }

        if (   $exitcode == 'save'
            || $exitcode == 'next'
            || $exitcode == 'preview') {
            // Iterate over the widgets and tell them to re-synchronize with their
            // types.
            foreach (array_keys($this->widgets) as $name) {
                if (!array_key_exists($name, $results)) {
                    if ($ajax_mode) {
                        continue;
                    }
                    $results[$name] = null;
                }
                $this->widgets[$name]->sync_type_with_widget($results);
            }
        }

        return $exitcode;
    }

    /**
     * This is a shortcut to the translate_schema_string function.
     *
     * @param string $string The string to be translated.
     * @return string The translated string.
     * @see midcom_helper_datamanager2_schema::translate_schema_string()
     */
    function _translate($string)
    {
        return $this->_schema->translate_schema_string($string);
    }

    /**
     * This is a shortcut function which allows the calling application to
     * determine the pre-validation return code of the current form.
     *
     * This function does *not* do any form specific operations.
     * It is primarily geared on shortcutting out of existing processing chains
     * to avoid the expensive controller startup operation in cases where cancel
     * or similar buttons are clicked.
     *
     * @return string One of 'edit', 'save', 'next', 'previous', 'preview' and 'cancel'
     */
    public static function get_clicked_button()
    {
        $available_buttons = [
            'save',
            'next',
            'previous',
            'cancel',
            'preview',
            'delete'
        ];
        foreach ($available_buttons as $button) {
            if (array_key_exists('midcom_helper_datamanager2_' . $button, $_REQUEST)) {
                return $button;
            }
        }
        return 'edit';
    }
}
