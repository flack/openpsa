<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: universalchooser.php 25328 2010-03-18 19:10:35Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/** Base class include */
require_once('radiocheckselect.php');

/**
 * Datamanager 2 universal "chooser" widget.
 *
 * Based on the radiocheckselect widget
 *
 * It can only be bound to a select type (or subclass thereof), and inherits the configuration
 * from there as far as possible.
 *
 * Note for this widget to work correctly you probably need these two set in type_config
 * (not strictly required, if your options list/callback always contains everything needed, which is unlikely)
 * <code>
 *     'require_corresponding_option' => false,
 *     'allow_other'    => true,
 * <code>
 * <b>Extra configurations:</b>
 *     'static_options' is an array of key => value pairs, shown always (use it to add for example "none" to a radio selection)
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_universalchooser extends midcom_helper_datamanager2_widget_radiocheckselect
{
    /**
     * Class to search for
     *
     * @var string
     */
    var $class = false;

    /**
     * The group of selected items as QuickForm elements
     */
    var $elements = array();

    /**
     * The group of search items as QuickForm elements
     */
    var $elements2 = array();

    /**
     * Which component the searched class belongs to
     *
     * @var string
     */
    var $component = false;

    /**
     * Field/property to use for the title in listings
     *
     * @var string
     */
    var $titlefield = 'name';

    /**
     * Field/property to use as the key/id
     *
     * @var string
     */
    var $idfield = 'guid';

    /**
     * Associative array of constraints (besides the search term), always AND
     *
     * Example:
     *     'constraints' => array
     *     (
     *         array
     *         (
     *             'field' => 'username',
     *             'op' => '<>',
     *             'value' => '',
     *         ),
     *     ),
     *
     * @var array
     */
    var $constraints = array();

    /**
     * Fields/properties to search the keyword for, always OR and specified after the constraints above
     *
     * Example:
     *      'searchfields' => array('firstname', 'lastname', 'email', 'username'),
     *
     * @var array
     */
    var $searchfields = array();

    /**
     * associative array of ordering info, always added last
     *
     * Example:
     *     'orders' => array(array('lastname' => 'ASC'), array('firstname' => 'ASC')),
     *
     * @var array
     */
    var $orders = array();
    /**
     * Allow creation of new objects (depends on components support as well for the actual work)
     *
     * @var boolean
     */
    var $allow_create = false;

    /**
     * Used as part of data for a hash the handler checks
     *
     * @var string (binary)
     */
    var $_shared_secret = null;

    /**
     * These options are always visible
     */
    var $static_options = array();

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
    var $auto_wildcards = false;

   /**
     * The initialization event handler verifies the correct type.
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if (is_a('midcom_helper_datamanager2_type_select', $this->_type))
        {
            debug_add("Warning, the field {$this->name} is not a select type or subclass thereof, you cannot use the universalchooser widget with it.",
                MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        if (empty($this->class))
        {
            debug_add("Warning, the field {$this->name} does not have class defined.",
                MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        if (empty($this->component))
        {
            debug_add("Warning, the field {$this->name} does not have component the class {$this->class} belongs to defined.",
                MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        if (empty($this->searchfields))
        {
            debug_add("Warning, the field {$this->name} does not have searchfields defined, it can never return results.",
                MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        if (!$this->_check_class())
        {
            debug_add("Warning, cannot get class {$this->class} loaded.",
                MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        if (   $this->allow_create
            && !$this->_check_create())
        {
            debug_add("Component {$this->component} does not have handler for creations, disallowing create", MIDCOM_LOG_WARN);
            $this->allow_create = false;
        }

        try
        {
            $key_snippet = new midgard_snippet();
            $key_snippet->get_by_path('/sitegroup-config/midcom.helper.datamanager2/widget_universalchooser_key');
        }
        catch (midgard_error_exception $e)
        {
            //FIXME: Make sure this is actually the correct code (and midgard-php sets the code)
            if ($e->getCode() !== 0)
            {
                throw $e;
            }
            $key_snippet = null;
        }
        if (   !is_object($key_snippet)
            || empty($key_snippet->doc))
        {
            debug_add("Warning, cannot get shared secret (either not generated or error loading), try generating with /midcom-exec-midcom.helper.datamanager2/universalchooser_create_secret.php.", MIDCOM_LOG_WARN);
        }
        else
        {
            $this->_shared_secret = $key_snippet->doc;
        }

        // TODO: These will need to be installed separately, Prototype is deprecated from Midgard
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/Pearified/JavaScript/Prototype/prototype.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/universalchooser.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.services.uimessages/protoGrowl.js');
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.services.uimessages/protoGrowl.css'
            )
        );
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/universalchooser.css'
            )
        );

        debug_add("Universalchooser widget used with field {$this->name} is deprecated, use chooser instead.", MIDCOM_LOG_WARN);
        debug_pop();
        return true;
    }

    function _check_create()
    {
        $component_path = MIDCOM_ROOT . '/' . str_replace('.', '/', $this->component);
        return file_exists($component_path . '/exec/universalchooser_createhandler.php');
    }

    function _check_class()
    {
        if (class_exists($this->class))
        {
            return true;
        }
        $_MIDCOM->componentloader->load($this->component);
        return class_exists($this->class);
    }

    function _get_single_key($key)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("calling {$this->class}::new_query_builder()");
        $qb = call_user_func(array($this->class, 'new_query_builder'));
        debug_add("adding constraint {$this->idfield}={$key}");
        $qb->add_constraint($this->idfield, '=', $key);
        $results = $qb->execute();
        debug_print_r('Got results:', $results);
        if (empty($results))
        {
            // FIXME: 1.7 fallback, GUIDs are not fetchable via QB
            if (mgd_is_guid($key))
            {
                debug_add("Trying to fetch by GUID (this is a Midgard 1.7 fallback)");
                $object = new $this->class($key);
                if ($object)
                {
                    debug_pop();
                    return $object;
                }
            }

            debug_pop();
            return false;
        }
        debug_pop();
        return $results[0];
    }

    function _get_key_value($key)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        $object = $this->_get_single_key($key);
        if (!is_object($object))
        {
            // Could not object, or got wrong type of object
            debug_add("Could not get object for key: {$key}", MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }
        if (is_array($this->titlefield))
        {
            foreach($this->titlefield as $field)
            {
                if ($object->$field)
                {
                    $titlefield = $field;
                    break;
                }
            }
        }
        else
        {
            $titlefield = $this->titlefield;
        }

        $value = $object->$titlefield;
        debug_pop();
        return $value;
    }


    function _add_single_option(&$elements, &$idsuffix, $key, $value)
    {
        if ($this->_type->allow_multiple)
        {
            $elements[] = HTML_QuickForm::createElement
            (
                'checkbox',
                $key,
                $key,
                $this->_translate($value),
                array
                (
                    'class' => 'checkbox'
                )
            );
        }
        else
        {
            $elements[] = HTML_QuickForm::createElement
            (
                'radio',
                null,
                $key,
                $this->_translate($value),
                $key,
                array
                (
                    'class' => 'radiobutton',
                    'id' => "universalchooser_{$idsuffix}_{$key}",
                )
            );
        }
    }

    /**
     * Adds checkboxes / radioboxes to the form.
     */
    function add_elements_to_form()
    {
        $idsuffix = $this->_create_random_suffix();
        debug_push_class(__CLASS__, __FUNCTION__);

        // Add static options
        foreach ($this->static_options as $key => $value)
        {
            $this->_add_single_option($this->elements, $idsuffix, $key, $value);
        }
        // Add existing selection
        $existing_elements = $this->_type->selection;
        foreach ($existing_elements as $key)
        {
            debug_add("Processing key {$key}");
            if (array_key_exists($key, $this->static_options))
            {
                debug_add("key '{$key}' found in static options, not adding it again", MIDCOM_LOG_INFO);
                continue;
            }
            $value = $this->_get_key_value($key);
            if ($value === false)
            {
                debug_add('Got strict boolean false as value, skipping field');
                continue;
            }
            debug_add("Adding field '{$key}' => '{$value}'");
            $this->_add_single_option($this->elements, $idsuffix, $key, $value);
        }

        // Create a hash of our constraints with a shared secret thrown in (to avoid leaking data outside the widget)
        $hashsource = $this->class . $this->idfield . $this->_shared_secret . $this->component . $idsuffix;

        // Serialize the parameter we need in the search end
        $searchconstraints_serialized = "idsuffix={$idsuffix}";
        $serialize = array('component', 'class', 'titlefield', 'idfield', 'searchfields', 'auto_wildcards');
        foreach ($serialize as $field)
        {
            $data = $this->$field;
            if (is_array($data))
            {
                foreach ($data as $k => $v)
                {
                    $searchconstraints_serialized .= '&' . rawurlencode("{$field}[{$k}]") . '=' . rawurlencode($v);
                }
            }
            else
            {
                $searchconstraints_serialized .= "&{$field}=" . rawurlencode($data);
            }
        }
        foreach ($this->constraints as $key => $data)
        {
            $hashsource .= $data['field'] . $data['op'] . $data['value'];
            $searchconstraints_serialized .= '&' . rawurlencode("constraints[{$key}][field]") . '=' . rawurlencode($data['field']);
            $searchconstraints_serialized .= '&' . rawurlencode("constraints[{$key}][op]") . '=' . rawurlencode($data['op']);
            $searchconstraints_serialized .= '&' . rawurlencode("constraints[{$key}][value]") . '=' . rawurlencode($data['value']);
        }
        foreach ($this->orders as $key => $data)
        {
            foreach ($data as $prop => $sort)
            {
                $searchconstraints_serialized .= '&' . rawurlencode("orders[{$key}][{$prop}]") . '=' . rawurlencode($sort);
            }
        }

        debug_add('widget hashsource: (B64)' . base64_encode($hashsource));
        $searchconstraints_serialized .= '&hash=' . md5($hashsource);
        // Start a new group to not to clutter the values

        // Hidden input for Ajax url
        $nav = new midcom_helper_nav();
        $root_node = $nav->get_node($nav->get_root_node());
        $url = $root_node[MIDCOM_NAV_FULLURL] . 'midcom-exec-midcom.helper.datamanager2/universalchooser_handler.php';

        $this->elements2[] = HTML_QuickForm::createElement
            (
                'hidden',
                "widget_universalchooser_search_{$idsuffix}_url",
                $url,
                array
                (
                    'id' => "widget_universalchooser_search_{$idsuffix}_url",
                )
            );
        if ($this->allow_create)
        {
            $createurl = $root_node[MIDCOM_NAV_FULLURL] . "midcom-exec-{$this->component}/universalchooser_createhandler.php";
            $this->elements2[] = HTML_QuickForm::createElement
                (
                    'hidden',
                    "widget_universalchooser_search_{$idsuffix}_createurl",
                    $createurl,
                    array
                    (
                        'id' => "widget_universalchooser_search_{$idsuffix}_createurl",
                    )
                );
        }

        // Hidden input for mode
        $mode = 'single';
        if ($this->_type->allow_multiple)
        {
            $mode = 'multiple';
        }
        $this->elements2[] = HTML_QuickForm::createElement
            (
                'hidden',
                "widget_universalchooser_search_{$idsuffix}_mode",
                $mode,
                array
                (
                    'id' => "widget_universalchooser_search_{$idsuffix}_mode",
                )
            );

        // Hidden input for the serialized constraints
        $this->elements2[] = HTML_QuickForm::createElement
            (
                'hidden',
                "widget_universalchooser_search_{$idsuffix}_constraints",
                $searchconstraints_serialized,
                array
                (
                    'id' => "widget_universalchooser_search_{$idsuffix}_constraints",
                )
            );

        // Hidden input for the id parent label
        $this->elements2[] = HTML_QuickForm::createElement
            (
                'hidden',
                "widget_universalchooser_search_{$idsuffix}_labelid",
                $this->name . '_universalchooser_' . $idsuffix . '_label',
                array
                (
                    'id' => "widget_universalchooser_search_{$idsuffix}_labelid",
                )
            );
        $this->elements2[] = HTML_QuickForm::createElement
            (
                'hidden',
                "widget_universalchooser_search_{$idsuffix}_fieldname",
                $this->name,
                array
                (
                    'id' => "widget_universalchooser_search_{$idsuffix}_fieldname",
                )
            );

        // Text input for the search box
        $this->elements2[] = HTML_QuickForm::createElement
            (
                'text',
                "widget_universalchooser_search_{$idsuffix}",
                "widget_universalchooser_search_{$idsuffix}",
                array
                (
                    'onKeyUp'       => "midcom_helper_datamanager2_widget_universalchooser_search_onkeyup('{$idsuffix}')",
                    'class'         => 'shorttext universalchooser_search',
                    'autocomplete'  => 'off',
                    'id'            => "widget_universalchooser_search_{$idsuffix}",
                )
            );

        $group = $this->_form->addGroup($this->elements, $this->name, $this->_translate($this->_field['title']), "<br />");
        /* PONDER: Why if the ones in elements2 are put to elements they all get value set to '2' ?? */
        $group2 = $this->_form->addGroup($this->elements2, $this->name . '_universalchooser_' . $idsuffix, '', '', array('class' => 'universalchooser_searchinput'));
        $this->_renderer->setElementTemplate('<div id="' . $this->name . '_universalchooser_' . $idsuffix . '_label">{element}</div>', $this->name . '_universalchooser_' . $idsuffix);
        $group2->setAttributes(Array('class' => 'midcom_helper_datamanager2_widget_universalchooser'));

        if ($this->_type->allow_multiple)
        {
            $group->setAttributes(Array('class' => 'radiobox'));
        }
        else
        {
            $group->setAttributes(Array('class' => 'checkbox'));
        }
        debug_pop();
    }

    /**
     * Creates random string of 8 characters
     *
     * Used to generate the random suffix to distinguish between instances
     * @todo Use together with hashed "password" to secure the searching interface
     * @return string random string
     */
    function _create_random_suffix()
    {
        //Use mt_rand if possible (faster, more random)
        if (function_exists('mt_rand'))
        {
            $rand = 'mt_rand';
        }
        else
        {
            $rand = 'rand';
        }
        $tokenchars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $token = $tokenchars[$rand(0, strlen($tokenchars) - 11)];
        for ($i = 1; $i < 8; $i++)
        {
            $token .= $tokenchars[$rand(0, strlen($tokenchars) - 1)];
        }
        return $token;
    }

    /**
     * The defaults of the widget are mapped to the current selection.
     */
    function get_default()
    {
        if ($this->_type->allow_multiple)
        {
            $defaults = Array();
            foreach ($this->_type->selection as $key)
            {
                $defaults[$key] = true;
            }
            return Array($this->name => $defaults);
        }
        else
        {
            if (count($this->_type->selection) > 0)
            {
                return Array($this->name => $this->_type->selection[0]);
            }
            else if ($this->_field['required'])
            {
                // Select the first radiobox always when this is a required field:
                $all = $this->_type->list_all();
                reset($all);
                return Array($this->name => key($all));
            }
            else
            {
                return null;
            }
        }
    }

    /**
     * Reads the given get/post data and puts to type->selection
     */
    function sync_type_with_widget($results)
    {
        $this->_type->selection = Array();
        if (!isset($results[$this->name]))
        {
            return;
        }
        $real_results =& $results[$this->name];

        // PONDER: Check type->require_corresponding_option setting and do checks against available options if set (very unlikely though...)
        if ($this->_type->allow_multiple)
        {
            foreach ($real_results as $key => $value)
            {
                $this->_type->selection[] = $key;
            }
        }
        else
        {
            $this->_type->selection[] = $real_results;
        }
    }

    function render_content()
    {
        if ($this->_type->allow_multiple)
        {
            echo '<ul>';
            if (count($this->_type->selection) == 0)
            {
                echo '<li>' . $this->_translate('type select: no selection') . '</li>';
            }
            else
            {
                foreach ($this->_type->selection as $key)
                {
                    echo '<li>' . $this->_get_key_value($key) . '</li>';
                }
            }
            echo '</ul>';
        }
        else
        {
            if (count($this->_type->selection) == 0)
            {
                echo $this->_translate('type select: no selection');
            }
            else
            {
                echo $this->_get_key_value($this->_type->selection[0]);
            }
        }
        /* TODO: What to do with this ??
        if ($this->_type->allow_other)
        {
            if (! $this->_type->allow_multiple)
            {
                echo '; ';
            }
            echo $this->_translate($this->othertext) . ': ';
            echo implode(',', $this->_type->others);
        }
        */
    }

    function freeze()
    {
        foreach ($this->elements as $element)
        {
            if (method_exists($element, 'freeze'))
            {
                $element->freeze();
            }
        }
        foreach ($this->elements2 as $element)
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
        foreach ($this->elements as $element)
        {
            if (method_exists($element, 'freeze'))
            {
                $element->unfreeze();
            }
        }
        foreach ($this->elements2 as $element)
        {
            if (method_exists($element, 'freeze'))
            {
                $element->unfreeze();
            }
        }
    }

}

?>