<?php
/**
 * @package midcom.helper.datamanager2
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

if (! class_exists('midcom_helper_reflector'))
{
    $_MIDCOM->load_library('midcom.helper.reflector');
}

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
     * Associative array of constraints (besides the search term), always AND
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
     *     'auto_wildcards' => 'both',
     * </code>
     *
     * @var string
     */
    public $auto_wildcards = 'end';

    public $creation_mode_enabled = false;
    public $creation_handler = null;
    public $creation_default_key = null;


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
     *
     * @return boolean Indicating Success
     */
    public function _on_initialize()
    {
        if (   !is_a($this->_type, 'midcom_helper_datamanager2_type_select')
            && !is_a($this->_type, 'midcom_helper_datamanager2_type_mnrelation'))
        {
            debug_add("Warning, the field {$this->name} is not a select type or subclass thereof, you cannot use the autocomplete widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        self::add_head_elements();

        $this->_element_id = "{$this->_namespace}{$this->name}_autocomplete_widget";

        return true;
    }

    public static function add_head_elements()
    {
        $_MIDCOM->enable_jquery();

        $_MIDCOM->add_stylesheet(MIDCOM_JQUERY_UI_URL . '/themes/base/jquery.ui.theme.css');
        $_MIDCOM->add_stylesheet(MIDCOM_JQUERY_UI_URL . '/themes/base/jquery.ui.autocomplete.css');
        $_MIDCOM->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/autocomplete.css');

        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.position.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.autocomplete.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/autocomplete.js');
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
        if (   !$root_node
            || empty($root_node))
        {
            return;
        }
        $handler_url = $root_node[MIDCOM_NAV_FULLURL] . 'midcom-exec-midcom.helper.datamanager2/autocomplete_handler.php';
        $selection = $this->_get_selection();

        $this->_widget_elements[] = HTML_QuickForm::createElement
        (
            'hidden',
            "{$this->_element_id}_selection",
            json_encode($selection),
            array
            (
                'id' => "{$this->_element_id}_selection",
            )
        );

        $preset = '';
        if (!empty($selection))
        {
            $identifier = $selection[0];
            if ($this->id_field == 'id')
            {
                $identifier = (int) $identifier;
            }
            $object = new $this->class($identifier);
            $preset = self::create_item_label($object, $this->result_headers, $this->get_label_for);
        }

        // Text input for the search box
        $this->_widget_elements[] = HTML_QuickForm::createElement
        (
            'text',
            "{$this->_element_id}_search_input",
            $this->_translate($this->_field['title']),
            array_merge($attributes, array
            (
                'class' => 'shorttext autocomplete_input',
                'id' => "{$this->_element_id}_search_input",
                'value' => $preset,
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
        ));

        $script = <<<EOT
            var {$this->_element_id}_handler_options = {$handler_options}
        jQuery(document).ready(
        function()
        {
            jQuery('#{$this->_element_id}_search_input').autocomplete(
            {
                minLength: {$this->min_chars},
                source: midcom_helper_datamanager2_autocomplete.query,
                select: midcom_helper_datamanager2_autocomplete.select
            })
        });
EOT;

        if ($this->creation_mode_enabled)
        {
            $script .= <<<EOT
            jQuery(document).ready(
                function()
                {
                    midcom_helper_datamanager2_autocomplete.enable_creation_mode('{$this->_element_id}', '{$this->creation_handler}');
                });
EOT;
        }

        $script = '<script type="text/javascript">' . $script . '</script>';

        $this->_widget_elements[] = HTML_QuickForm::createElement
        (
            'static',
            "{$this->_element_id}_initscript",
            '',
            $script
        );

        $this->_form->addGroup($this->_widget_elements, $this->name, $this->_translate($this->_field['title']), '', array('class' => 'midcom_helper_datamanager2_widget_autocomplete'));
    }

    private function _get_selection()
    {
        $selection = array();
        foreach ($this->_type->selection as $value)
        {
            if (   $value != "0"
                || $value != 0)
            {
                $selection[] = $value;
            }
        }

        $form_selection = $this->_get_form_selection($_REQUEST);
        if (!empty($form_selection))
        {
            $selection = array_merge($selection, $form_selection);
        }
        return $selection;
    }

    private function _get_form_selection($data)
    {
        $selection = array();
        if (!isset($data[$this->name]["{$this->_element_id}_selection"]))
        {
            return $selection;
        }
        if (empty($data[$this->name]["{$this->_element_id}_search_input"]))
        {
            //if the input is empty, we suppose that the user tries to remove their previous selection
            return $selection;
        }

        $real_results = json_decode($data[$this->name]["{$this->_element_id}_selection"]);

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
        else if (!$this->allow_multiple)
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
    public function get_default()
    {
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
        $this->_type->selection = $this->_get_form_selection($results);
    }

    function render_content()
    {
        if (count($this->_type->selection) == 0)
        {
            echo $this->_translate('type select: no selection');
        }
        else
        {
            $selection = array();
            foreach ($this->_type->selection as $key)
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

                if (!class_exists('midcom_helper_reflector'))
                {
                     $selection[] = get_class($object) . " #{$object->id}";
                     continue;
                }

                $ref = new midcom_helper_reflector($object);
                $selection[] = $ref->get_object_label($object);
            }
            echo implode(', ', $selection);
        }
    }

    public static function create_item_label($object, $result_headers, $get_label_for)
    {
        $label = array();
        foreach ($result_headers as $header_item)
        {
            $item_name = $header_item['name'];

            if (preg_match('/^metadata\.(.+)$/', $item_name, $regs))
            {
                $metadata_property = $regs[1];
                $value = $object->metadata->$metadata_property;

                switch ($metadata_property)
                {
                    case 'created':
                    case 'revised':
                    case 'published':
                    case 'schedulestart':
                    case 'scheduleend':
                    case 'imported':
                    case 'exported':
                    case 'approved':
                        if ($value)
                        {
                            $value = strftime('%x %X', $value);
                        }
                        break;

                    case 'creator':
                    case 'revisor':
                    case 'approver':
                    case 'locker':
                        if ($value)
                        {
                            $person = new midcom_db_person($value);
                            $value = $person->name;
                        }
                        break;
                }
            }
            else if ($get_label_for == $item_name)
            {
                $value = $object->get_label();
            }
            else
            {
                $value = $object->$item_name;
            }

            $label[] = $value;
        }
        return implode(', ', $label);
    }

    /**
     * Statically callable helper method to resolve path for an object
     */
    function resolve_path(&$object, $title)
    {
        if (!class_exists('midcom_helper_reflector_tree'))
        {
            return $title;
        }
        return midcom_helper_reflector_tree::resolve_path($object);
    }
}
?>
