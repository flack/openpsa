<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: tags.php 25328 2010-03-18 19:10:35Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Tags widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
 *
 * It can only be bound to a tagselect type (or subclass thereof), and inherits the configuration
 * from there as far as possible.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>integer min_chars:</i> Minimum amount of chars to be inserted before search starts. Default: 1
 * - <i>integer result_limit:</i> Number max Limit the number of items in the select box.
 *   Is also sent as a "limit" parameter with a remote request. Default: 10
 * - <i>boolean autofill_enabled:</i> Fill the textinput while still selecting a value, replacing the value
 *   if more is typed or something else is selected. Default: false
 * - <i>boolean select_first:</i> If this is set to true, the first result will be automatically selected on tab/return. Default: true
 * - <i>integer delay:</i> The delay in milliseconds that search waits after a keystroke before activating itself. Default: 400
 * - <i>boolean allow_create:</i> If this is set to true, then when user presses tab it creates a tag from current input value
 *   if we don't have any results. Default: false (true if we are configured to work with component & class)
 * - <i>integer width:</i> Specify a custom width for the select box. Default: width of the input element
 *
 * Example:
 * <code>
 *  'tags' => Array
 *  (
 *      'title' => 'event tags',
 *      'storage' => null,
 *      'type' => 'tagselect',
 *      'type_config' => array
 *      (
 *          'option_callback' => 'org_maemo_calendar_callbacks_personstags',
 *          'enable_saving_to_callback' => false,
 *          'force_saving_to_tag_library' => true,
 *      ),
 *      'widget' => 'tags',
 *  ),
 * </code>
 * OR
 * <code>
 *  'tags' => Array
 *  (
 *      'title' => 'tags',
 *      'storage' => null,
 *      'type' => 'tagselect',
 *      'widget' => 'tags',
 *      'widget_config' => array
 *      (
 *          'component' => 'net.nemein.wiki',
 *          'class' => 'net_nemein_wiki_wikipage',
 *          'id_field' => 'guid',
 *      ),
 *  ),
 * </code>
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_tags extends midcom_helper_datamanager2_widget
{
    /**
     * id of the input element
     *
     * @var String
     * @access private
     */
    var $_input_element_id = "tags-widget";

    /**
     * Array of options that are passed to javascript widget
     *
     * @var Array
     * @access private
     */
    var $_js_widget_options = array();

    var $_input_element = null;

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
     * Object's id/guid which is to be tagged
     *
     * @var string
     */
    var $object_id = null;

    /**
     * Field/property to use as the key for object_id
     *
     * @var string
     */
    var $id_field = null;

    /**
     * The javascript to append to the page
     *
     * @var string
     */
    var $_jscript = '';

    /**
     * The group of widgets items as QuickForm elements
     */
    var $widget_elements = array();

    /**
     * The initialization event handler post-processes the maxlength setting.
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        if (is_a('midcom_helper_datamanager2_type_tagselect', $this->_type))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Warning, the field {$this->name} is not a tagselect type or subclass thereof, you cannot use the tags widget with it.",
                MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        if (   !isset($this->component)
            || empty($this->component))
        {
            if (   !isset($this->_type->option_callback)
                || empty($this->_type->option_callback))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("You must give either object to be edited or callback for action handling!",
                    MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
        }

        if (   isset($this->component)
            || !empty($this->component))
        {
            $object = $this->_type->storage->object;
            $idfield = $this->id_field;
            $this->object_id = @$object->$idfield;
            if (!isset($this->allow_create))
            {
                $this->allow_create = true;
            }
            if (empty($this->object_id))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("We didn't manage to get object_id from type! Quitting now.",
                    MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
        }

        $_MIDCOM->enable_jquery();

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/tags/jquery.tags_widget.css'
            )
        );

        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.bgiframe.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/tags/jquery.tags_widget.js');

        $this->_input_element_id = "{$this->_namespace}{$this->name}-tags-widget";

        $this->_init_widget_options();

        return true;
    }

    function _init_widget_options()
    {
        $this->_js_widget_options['widget_type_name'] = "'{$this->name}'";
        $this->_js_widget_options['min_chars'] = 1;
        $this->_js_widget_options['result_limit'] = 10;
        $this->_js_widget_options['autofill_enabled'] = "false";
        $this->_js_widget_options['select_first'] = "true";
        $this->_js_widget_options['extra_params'] = "{}";
        $this->_js_widget_options['delay'] = 400;
        $this->_js_widget_options['allow_create'] = "false";
        $this->_js_widget_options['width'] = 0;

        if (isset($this->min_chars))
        {
            $this->_js_widget_options['min_chars'] = $this->min_chars;
        }
        if (isset($this->max_results))
        {
            $this->_js_widget_options['result_limit'] = $this->max_results;
        }
        if (isset($this->autofill_enabled))
        {
            if ($this->autofill_enabled)
            {
                $this->_js_widget_options['autofill_enabled'] = "true";
            }
            else
            {
                $this->_js_widget_options['autofill_enabled'] = "false";
            }
        }
        if (isset($this->select_first))
        {
            if ($this->select_first)
            {
                $this->_js_widget_options['select_first'] = "true";
            }
            else
            {
                $this->_js_widget_options['select_first'] = "false";
            }
        }
        if (isset($this->delay))
        {
            $this->_js_widget_options['delay'] = $this->delay;
        }
        if (isset($this->allow_create))
        {
            if ($this->allow_create)
            {
                $this->_js_widget_options['allow_create'] = "true";
            }
            else
            {
                $this->_js_widget_options['allow_create'] = "false";
            }
        }
        if (isset($this->width))
        {
            $this->_js_widget_options['width'] = $this->width;
        }

        $this->_generate_extra_params();
    }

    function _generate_extra_params()
    {
        $params = "{";

        if (   !empty($this->component)
            && !empty($this->class)
            && !empty($this->object_id)
            && !empty($this->id_field))
        {
            $params .= "component: '{$this->component}',";
            $params .= "class: '{$this->class}',";
            $params .= "object_id: '{$this->object_id}',";
            $params .= "id_field: '{$this->id_field}'";
        }
        else if (isset($this->_type->option_callback))
        {
            $params .= "callback: '{$this->_type->option_callback}'";
            if (   isset($this->_type->option_callback_args)
                && is_array($this->_type->option_callback_args))
            {
                $params .= ", callback_args: ".json_encode($this->_type->option_callback_args);
            }
        }

        $params .= "}";

        $this->_js_widget_options['extra_params'] = $params;
    }

    function _get_key_data($key)
    {
        $data = $this->_type->get_data_for_key($key);

        $value = "{";

        $value .= "id: '{$key}',";
        $value .= "name: '{$data['name']}',";
        $value .= "color: '{$data['color']}'";

        $value .= "}";

        return $value;
    }

    /**
     * Adds a simple single-line text form element and place holder for tags.
     */
    function add_elements_to_form()
    {
        $attributes = Array
        (
            'class' => "shorttext",
            'id'    => $this->_input_element_id,
        );

        $this->widget_elements[] = HTML_QuickForm::createElement
        (
            'text',
            "{$this->name}_input",
            $this->_translate($this->_field['title']),
            $attributes
        );

        // Get url to search handler
        $nav = new midcom_helper_nav();
        $root_node = $nav->get_node($nav->get_root_node());
        $this->_handler_url = $root_node[MIDCOM_NAV_FULLURL] . 'midcom-exec-midcom.helper.datamanager2/tags_handler.php';

        $this->_jscript .= '<script type="text/javascript">';
        $this->_jscript .= 'jQuery().ready(function(){';

        $script = "jQuery('#{$this->_input_element_id}').midcom_helper_datamanager2_widget_tags_widget('{$this->_handler_url}', {\n";
        foreach ($this->_js_widget_options as $key => $value)
        {
            $script .= "{$key}: {$value},\n";
        }
        $script .= "});";
        $this->_jscript .= $script;

        // Add existing selection
        $existing_elements = $this->_type->selection;
        $ee_script = '';
        foreach ($existing_elements as $key)
        {
            $data = $this->_get_key_data($key);
            $ee_script .= "jQuery('#{$this->_input_element_id}').midcom_helper_datamanager2_widget_tags_add_selection_item({$data});\n";
        }
        $this->_jscript .= $ee_script;

        $this->_jscript .= '});';
        $this->_jscript .= '</script>';

        $this->widget_elements[] = HTML_QuickForm::createElement
        (
            'static',
            "{$this->name}_initscripts",
            '',
            $this->_jscript
        );

        $this->_form->addGroup($this->widget_elements, $this->name, $this->_translate($this->_field['title']), '', array('class' => 'midcom_helper_datamanager2_widget_tags'));
    }

     /**
      * The defaults of the widget are mapped to the current selection.
      */
     function get_default()
     {
         $defaults = Array();
         foreach ($this->_type->selection as $key)
         {
             $defaults[$key] = true;
         }
         return Array($this->name => $defaults);
     }

    /**
     * Reads the given get/post data and puts to type->selection
     */
    function sync_type_with_widget($results)
    {
        $this->_type->selection = Array();
        if (!isset($results["{$this->name}_tags"]))
        {
            return;
        }

        foreach ($results["{$this->name}_tags"] as $key => $value)
        {
            if ($value > 0)
            {
                $this->_type->selection[] = $key;
            }
        }
    }

    /**
     * @todo Implement freezing and unfreezing
     */
    function freeze()
    {
        //We should freeze the inputs here
    }
    function unfreeze()
    {
        //We should unfreeze the inputs here
    }
    function is_frozen()
    {
        return false;
    }

    function render_content()
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
                $data = $this->_get_key_data($key);
                echo '<li>' . $data['name'] . '</li>';
            }
        }
        echo '</ul>';
    }
}

?>