<?php
/**
 * @package midcom.helper.datamanager2
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Autocomplete widget
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_autocomplete extends midcom_helper_datamanager2_widget
{
    /**
     * Minimum characters necessary to trigger a search
     *
     * @var integer
     */
    public $min_chars = 2;

    /**
     * Class to search for
     *
     * @var string
     */
    public $class = null;

    /**
     * Which component the searched class belongs to
     *
     * @var string
     */
    public $component = null;

    /**
     * Should the results be grouped by their parent label
     *
     * @var boolean
     */
    public $categorize_by_parent_label = false;

    /**
     * Array of constraints (besides the search term), always AND
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
     * Please note that this field is ignored if the used type already defines constraints
     *
     * @var array
     */
    public $constraints = array();

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
    public $result_headers = array();

    /**
     * In search results replaces given field with the object's label
     *
     * Example:
     * <code>
     *      'get_label_for' => 'title',
     * </code>
     *
     * @var string
     */
    public $get_label_for;

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
    public $searchfields = array();

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
    public $orders = array();

    /**
     * Field/property to use as the key/id
     *
     * @var string
     */
    public $id_field = 'guid';

    /**
     * Whether to automatically append/prepend wildcards to the query
     *
     * Valid values: 'both', 'start', 'end' and <empty> (0, '', false & null)
     *
     * Example:
     * <code>
     *     'auto_wildcards' => 'end',
     * </code>
     *
     * @var string
     */
    public $auto_wildcards = 'both';

    /**
     * Clever class
     *
     * @var string
     */
    public $clever_class;

    public $creation_mode_enabled = false;
    public $creation_handler = null;
    public $creation_default_key = null;

    /**
     * Sortable
     *
     * @var boolean
     */
    public $sortable = false;

    /**
     * the element's ID
     *
     * @var string
     */
    private $_element_id;

    /**
     * The group of widgets items as QuickForm elements
     *
     * @var array
     */
    private $_widget_elements = array();

    /**
     * The initialization event handler post-processes the maxlength setting.
     */
    public function _on_initialize()
    {
        $this->_require_type_class(array('midcom_helper_datamanager2_type_select', 'midcom_helper_datamanager2_type_mnrelation'));

        if (!empty($this->clever_class))
        {
            $this->_load_clever_class();
        }

        if (!empty($this->_type->constraints))
        {
            $this->constraints = $this->_type->constraints;
        }

        self::add_head_elements($this->creation_mode_enabled, $this->sortable);

        $this->_element_id = "{$this->_namespace}{$this->name}_autocomplete_widget";
    }

    private function _load_clever_class()
    {
        $config = $this->_config->get('clever_classes');
        if (empty($config[$this->clever_class]))
        {
            throw new midcom_error('Invalid clever class specified');
        }
        $config = array_merge($config[$this->clever_class], $this->_field['widget_config']);
        foreach ($config as $key => $value)
        {
            if (property_exists($this, $key))
            {
                $this->$key = $value;
            }
        }
    }

    public static function add_head_elements($creation_mode_enabled = false, $sortable = false)
    {
        $head = midcom::get()->head;

        $head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/autocomplete.css');

        $components = array('position', 'menu', 'autocomplete');
        if ($sortable)
        {
            $components[] = 'sortable';
        }
        if ($creation_mode_enabled)
        {
            $components = array_merge($components, array('mouse', 'draggable', 'resizable', 'button', 'dialog'));
        }
        $head->enable_jquery_ui($components);
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/autocomplete.js');
    }

    public static function get_widget_config($type)
    {
        $handler_url = midcom_connection::get_url('self') . 'midcom-exec-midcom.helper.datamanager2/autocomplete_handler.php';

        $widget_config = midcom_baseclasses_components_configuration::get('midcom.helper.datamanager2', 'config')->get('clever_classes');
        $config = $widget_config[$type];
        $config['handler_url'] = $handler_url;
        return $config;
    }

    /**
     * Adds a simple search form and place holder for results.
     * Also adds static options to results.
     */
    public function add_elements_to_form($attributes)
    {
        // Get url to search handler
        $handler_url = midcom_connection::get_url('self') . 'midcom-exec-midcom.helper.datamanager2/autocomplete_handler.php';
        $selection = $this->_get_selection();

        $this->_widget_elements[] = $this->_form->createElement
        (
            'hidden',
            "selection",
            json_encode($selection),
            array
            (
                'id' => "{$this->_element_id}_selection",
            )
        );

        $preset = array();
        if (!empty($selection))
        {
            $qb = new midcom_core_querybuilder($this->class);
            $qb->add_constraint($this->id_field, 'IN', $selection);
            $results = $qb->execute();
            foreach ($results as $result)
            {
                $identifier = $result->{$this->id_field};
                $preset[$identifier] = self::create_item_label($result, $this->result_headers, $this->get_label_for);
            }
        }

        // Text input for the search box
        $this->_widget_elements[] = $this->_form->createElement
        (
            'text',
            "search_input",
            $this->_translate($this->_field['title']),
            array_merge($attributes, array
            (
                'class' => 'shorttext autocomplete_input',
                'id' => "{$this->_element_id}_search_input",
            ))
        );

        $handler_options = json_encode(array
        (
            'handler_url' => $handler_url,
            'class' => $this->class,
            'component' => $this->component,
            'id_field' => $this->id_field,
            'constraints' => $this->constraints,
            'result_headers' => $this->result_headers,
            'searchfields' => $this->searchfields,
            'orders' => $this->orders,
            'auto_wildcards' => $this->auto_wildcards,
            'get_label_for' => $this->get_label_for,
            'categorize_by_parent_label' => $this->categorize_by_parent_label,
            'preset' => $preset,
            'preset_order' => array_reverse(array_keys($preset)),
            'sortable' => $this->sortable,
            'allow_multiple' => $this->_type->allow_multiple,
            'creation_mode_enabled' => $this->creation_mode_enabled,
            'creation_handler' => $this->creation_handler,
            'creation_default_key' => $this->creation_default_key
        ));

        $script = <<<EOT
            var {$this->_element_id}_handler_options = {$handler_options}
        jQuery(document).ready(
        function()
        {
            midcom_helper_datamanager2_autocomplete.create_dm2_widget('{$this->_element_id}_search_input', {$this->min_chars});
        });
EOT;

        $script = '<script type="text/javascript">' . $script . '</script>';

        $this->_widget_elements[] = $this->_form->createElement
        (
            'static',
            "{$this->_element_id}_initscript",
            '',
            $script
        );

        $this->_form->addGroup($this->_widget_elements, $this->name, $this->_translate($this->_field['title']), '', array('class' => 'midcom_helper_datamanager2_widget_autocomplete'));
        if ($this->_field['required'])
        {
            $errmsg = sprintf($this->_l10n->get('field %s is required'), $this->_translate($this->_field['title']));
            $this->_form->addGroupRule($this->name, array
            (
                "selection" => array
                (
                    array($errmsg, 'required'),
                    array($errmsg, 'regex', '/\[.+?\]/')
                )
            ));
        }
    }

    private function _get_selection()
    {
        $selection = array_filter($this->_type->selection);

        $form_selection = $this->_get_form_selection($_REQUEST);
        if (!empty($form_selection))
        {
            $selection = array_merge($selection, $form_selection);
        }
        if ($this->id_field == 'id')
        {
            // This is a workaround mainly for default values, which are always strings
            // for some reason
            $selection = array_map('intVal', $selection);
        }
        return $selection;
    }

    private function _get_form_selection($data)
    {
        $selection = array();
        if (   !isset($data[$this->name])
            || !is_array($data[$this->name])
            || !array_key_exists("selection", $data[$this->name]))
        {
            return $selection;
        }

        $real_results = json_decode($data[$this->name]["selection"]);

        if (is_array($real_results))
        {
            foreach ($real_results as $value)
            {
                if (   $value != "0"
                    || $value != 0)
                {
                    $selection[] = $value;
                }
            }
        }
        elseif (!$this->_type->allow_multiple)
        {
            $selection[] = $real_results;
        }
        return $selection;
    }

    public function freeze()
    {
        foreach ($this->_widget_elements as $element)
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
    public function unfreeze()
    {
        foreach ($this->_widget_elements as $element)
        {
            if (method_exists($element, 'unfreeze'))
            {
                $element->unfreeze();
            }
        }
    }

    public function is_frozen()
    {
        foreach ($this->_widget_elements as $element)
        {
            if (   method_exists($element, 'isFrozen')
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
    public function get_default()
    {
        if (sizeof($this->_type->selection) == 0)
        {
            return null;
        }
        return array($this->name => array_fill_keys($this->_type->selection, true));
    }

    /**
     * Reads the given get/post data and puts to type->selection
     */
    public function sync_type_with_widget($results)
    {
        $this->_type->selection = $this->_get_form_selection($results);
    }

    public function render_content()
    {
        $selection = array_filter($this->_type->selection);

        if (count($selection) == 0)
        {
            return $this->_translate('type select: no selection');
        }
        $labels = array();
        foreach ($selection as $key)
        {
            if ($this->id_field == 'id')
            {
                $key = (int) $key;
            }
            try
            {
                $object = new $this->class($key);
            }
            catch (midcom_error $e)
            {
                $e->log();
                continue;
            }

            $ref = new midcom_helper_reflector($object);

            $labels[] = $ref->get_object_label($object);
        }
        return implode(', ', $labels);
    }

    public static function create_item_label($object, $result_headers, $get_label_for)
    {
        $label = array();
        foreach ($result_headers as $header_item)
        {
            $item_name = $header_item['name'];

            if ($get_label_for == $item_name)
            {
                $value = midcom_helper_reflector::get($object)->get_object_label($object);
            }
            else
            {
                $value = midcom_helper_datamanager2_ajax_autocomplete::get_property_string($object, $item_name);
            }
            $value = strip_tags($value);
            if (trim($value) !== '')
            {
                $label[] = $value;
            }
        }
        $label = implode(', ', $label);
        if (empty($label))
        {
            $label = get_class($object) . ' #' . $object->id;
        }
        return $label;
    }

    public static function sort_items($a, $b)
    {
        if (isset($a['category']))
        {
            $cmp = strcmp($a['category'], $b['category']);
            if ($cmp != 0)
            {
                return $cmp;
            }
        }
        return strcmp($a['label'], $b['label']);
    }
}
