<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Widget base class.
 *
 * As with all subclasses, the actual initialization is done in the initialize() function.
 *
 * Quick glance at the changes
 *
 * - No more form prefixes, use the field name as a form field name
 * - Now uses class members, which should use initializers (var $name = 'default_value';)
 *   for configuration defaults.
 * - The schema configuration ('widget_config') is merged using the semantics
 *   $widget->$key = $value;
 *
 * @package midcom.helper.datamanager2
 */
abstract class midcom_helper_datamanager2_widget extends midcom_baseclasses_components_purecode
{
    /**
     * This is a reference to the type we're based on.
     *
     * @var midcom_helper_datamanager2_type
     * @access protected
     */
    var $_type;

    /**
     * This variable contains configuration data which is not directly related to the
     * operation of the type, but required for the operation of external tools like the
     * storage manager. The type should never touch this variable, which is controlled
     * by a corresponding getter/setter pair.
     *
     * @var Array
     * @see set_external_config()
     * @see get_external_config()
     */
    private $_external_config = array();

    /**
     * The name field holds the name of the field the widget is encapsulating.
     *
     * This maps to the schema's field name. You should never have to change them.
     *
     * @var string
     */
    public $name = '';

    /**
     * A reference to the schema field we should draw.
     *
     * Description texts etc. are taken from here.
     *
     * @var Array
     * @access protected
     */
    var $_field;

    /**
     * The schema (not the schema <i>database!</i>) to use for operation.
     *
     * This variable will always contain a parsed representation of the schema, so that
     * one can swiftly switch between individual schemas of the Database.
     *
     * @var midcom_helper_datamanager2_schema
     * @access protected
     */
    var $_schema;

    /**
     * The QuickForm we are using.
     *
     * @var HTML_QuickForm
     * @access protected
     */
    var $_form;

    /**
     * The QuickForm renderer we are using.
     *
     * @var HTML_QuickForm_Renderer
     * @access protected
     */
    var $_renderer;

    /**
     * This is the Namespace to use for all HTML/CSS/JS elements.
     *
     * It is deduced by the formmanager and tries to be as smart as possible to work safely with
     * more than one form on a page.
     *
     * You have to prefix all elements which must be unique using this string (it includes a
     * trailing underscore).
     *
     * @var string
     */
    protected $_namespace;

    /**
     * Whether widget should always load its dependencies on initialization, or only during
     * add_elements_to_form call.
     *
     * @var boolean
     */
    protected $_initialize_dependencies = false;

    /**
     * State of the form manager
     *
     * @var string
     */
    private $_state = 'edit';

    /**
     * Constructor.
     *
     * Nothing fancy, the actual startup work is done by the initialize call.
     */
    public function __construct($renderer)
    {
        $this->_renderer = $renderer;
        parent::__construct();
    }

    /**
     * Initializes and configures the widget.
     *
     * @param string $name The name of the field to which this widget is bound.
     * @param array $config The configuration data which should be used to customize the widget.
     * @param midcom_helper_datamanager2_schema $schema The full schema object.
     * @param midcom_helper_datamanager2_type $type The type to which we are bound.
     * @param string $namespace The namespace to use including the trailing underscore.
     * @param boolean $initialize_dependencies Whether to load JS and other dependencies on initialize
     */
    function initialize($name, $config, $schema, $type, $namespace, $initialize_dependencies = false)
    {
        $this->name = $name;
        $this->_schema = $schema;
        $this->_field = $schema->fields[$this->name];
        $this->_type = $type;
        $this->_namespace = $namespace;
        $this->_initialize_dependencies = $initialize_dependencies;

        // Call the event handler for configuration in case we have some defaults that cannot
        // be covered by the class initializers.
        $this->_on_configuring();

        // Assign the configuration values.
        foreach ($config as $key => $value)
        {
            $this->$key = $value;
        }

        $this->_on_initialize();
    }

    /**
     * Tests if selected type has a scalar or DateTime "value" property
     */
    protected function _require_type_value()
    {
        if (   !array_key_exists('value', $this->_type)
            || is_array($this->_type->value)
            || (   is_object($this->_type->value)
                && !$this->_type->value instanceof DateTime))
        {
            throw new midcom_error("The type {$this->name} does not have a value member or it is an array or object, you cannot use it with " . get_class($this));
        }
    }

    protected function _require_type_class($classes)
    {
        foreach ((array) $classes as $class)
        {
            if (is_a($this->_type, $class))
            {
                return true;
            }
        }

        throw new midcom_error("The field {$this->name} uses an invalid type: " . get_class($this) . ' requires one of ' . implode(' ', (array) $classes));
    }

    /**
     * Set the form reference.
     *
     * @param HTMLQuickForm $form The form to use.
     */
    function set_form(HTML_QuickForm $form)
    {
        $this->_form = $form;
    }

    /**
     * Set the formmanager state
     *
     * @param string $state The state we're in.
     */
    function set_state($state)
    {
        $this->_state = $state;
    }

    /**
     * This function is called  before the configuration keys are merged into the types
     * configuration.
     */
    public function _on_configuring() {}

    /**
     * This event handler is called during construction, so passing references to $this to the
     * outside is unsafe at this point.
     */
    public function _on_initialize()
    {
    }

    /**
     * Gets an external configuration option referenced by its key.
     *
     * Besides other parts in the datamanager framework, nobody should ever have to
     * touch this information.
     *
     * @param string $key The key by which this configuration option is referenced.
     * @return mixed The configuration value, which is null if the key wasn't found.
     */
    function get_external_config($key)
    {
        if (!array_key_exists($key, $this->_external_config))
        {
            return null;
        }
        return $this->_external_config[$key];
    }

    /**
     * Sets an external configuration option.
     *
     * Besides other parts in the datamanager framework, nobody should ever have to
     * touch this information.
     *
     * @param string $key The key by which this configuration option is referenced.
     * @param mixed $value The configuration value.
     */
    function set_external_config($key, $value)
    {
        $this->_external_config[$key] = $value;
    }

    /**
     * This call adds the necessary form elements to the form passed by reference.
     */
    abstract public function add_elements_to_form($attributes);

    /**
     * Returns the default value for this field as required by HTML_Quickform.
     *
     * You may either return a single value for simple types, or an array of form
     * field name / value pairs in case of composite types. A value of null indicates
     * no applicable default from the type or storage, in which case defaults from the
     * handler or the DM2 schema can be applied.
     *
     * This default implementation returns null unconditionally.
     *
     * @return mixed The default value as outlined above.
     */
    public function get_default()
    {
        return null;
    }

    /**
     * This is a shortcut to the translate_schema_string function.
     *
     * @param string $string The string to be translated.
     * @return string The translated string.
     * @see midcom_helper_datamanager2_schema::translate_schema_string()
     */
    protected function _translate($string)
    {
        return $this->_schema->translate_schema_string($string);
    }

    /**
     * This function is invoked if the widget should extract the corresponding data
     * from the form results passed in $results.
     *
     * Form validation has already been done before, this function will only be called
     * if and only if the form validation succeeds.
     *
     * @param array $results The complete form results, you need to extract all values
     *     relevant for your type yourself.
     */
    abstract public function sync_type_with_widget($results);

    /**
     * This event handler is called if and only if the Formmanager detects an actual
     * form submission (this is tracked using a hidden form member).
     *
     * No Form validation has been done at this point. The event is triggered on all
     * submissions with the exception of the cancel and previous form events.
     *
     * You should be careful when using this event for data
     * processing therefore. Its main application is the processing of additional buttons
     * placed into the form by the widget.
     *
     * The implementation of this handler is optional.
     *
     * @param array $results The complete form results, you need to extract all values
     *     relevant for your type yourself.
     */
    function on_submit($results) {}

    /**
     * When called, this method should display the current data without any
     * editing widget or surrounding braces in plain and simple HTML.
     *
     * The default implementation calls the type's convert_to_html method.
     *
     * @return string The rendered HTML
     */
    public function render_content()
    {
        return $this->_type->convert_to_html();
    }

    /**
     * Freezes all form elements associated with the widget.
     *
     * The default implementation works on the default field name, you don't need to override
     * this function unless you have multiple widgets in the form.
     *
     * This maps to the HTML_QuickForm_element::freeze() function.
     */
    public function freeze()
    {
        $element = $this->_form->getElement($this->name);
        if (method_exists($element, 'freeze'))
        {
            $element->freeze();
        }
    }

    /**
     * Unfreezes all form elements associated with the widget.
     *
     * The default implementation works on the default field name, you don't need to override
     * this function unless you have multiple widgets in the form.
     *
     * This maps to the HTML_QuickForm_element::unfreeze() function.
     */
    public function unfreeze()
    {
        $element = $this->_form->getElement($this->name);
        $element->unfreeze();
    }

    /**
     * Checks if the widget is frozen.
     *
     * The default implementation works on the default field name, usually you don't need to
     * override this function unless you have some strange form element logic.
     *
     * This maps to the HTML_QuickForm_element::isFrozen() function.
     *
     * @return boolean True if the element is frozen, false otherwise.
     */
    public function is_frozen()
    {
        $element = $this->_form->getElement($this->name);
        return $element->isFrozen();
    }
}
