<?php
/**
 * @package org.openpsa.core
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class that encapsulates a single query filter
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_filter
{
    /**
     * The filter's unique name
     *
     * @var string
     */
    public $name;

    /**
     * The filter selection, if any
     *
     * @var array
     */
    private $_selection;

    /**
     * The filter's options, if any
     *
     * @var array
     */
    private $_options;

    /**
     * The filter's configuration.
     *
     * Currently supported keys are 'mode', 'fieldname', 'operator' and 'option_callback'
     *
     * @var array
     */
    private $_config = array('mode' => 'singleselect');

    /**
     * Constructor
     *
     * @param string $name The filter's name
     * @param string $operator The constraint operator
     * @param array $options The filter's options, if any
     */
    public function __construct($name, $operator = '=', array $options = array())
    {
        $this->name = $name;
        $this->_config['fieldname'] = $name;
        $this->_config['operator'] = $operator;
        $this->_options = $options;
    }

    /**
     * Apply filter to given query
     *
     * @param array $selection The filter selection
     * @param midcom_core_query $query The query object
     */
    public function apply(array $selection, midcom_core_query $query)
    {
        $this->_selection = $selection;

        $query->begin_group('OR');
        foreach ($this->_selection as $id)
        {
            $query->add_constraint($this->_config['fieldname'], $this->_config['operator'], (int) $id);
        }
        $query->end_group();
    }

    public function add_head_elements()
    {
        $head = midcom::get('head');

        if ($this->_config['mode'] == 'multiselect')
        {
            $head->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/dropdown-check-list.1.4/css/ui.dropdownchecklist.standalone.css");

            $head->enable_jquery();
            $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
            $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
            $head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.core/dropdown-check-list.1.4/js/ui.dropdownchecklist-1.4-min.js');
        }
    }

    /**
     * Renders the filter widget according to mode
     */
    public function render()
    {
        $method = '_render_' . $this->_config['mode'];
        $options = $this->_get_options();

        if (!empty($options))
        {
            $this->$method($options);
        }
    }

    /**
     * Modify filter configuration
     *
     * @param string $key The config key to set
     * @param mixed $value The config value
     */
    public function set($key, $value)
    {
        $this->_config[$key] = $value;
    }

    /**
     * Get filter configuration setting
     *
     * @param string $key The config key to get
     * @return mixed The current config value or null
     */
    public function get_config($key)
    {
        if (!isset($this->_config[$key]))
        {
            return null;
        }
        return $this->_config[$key];
    }

    /**
     * Renderer for 'singleselect' mode
     *
     * @param array $options The options to render
     */
    private function _render_singleselect(array $options)
    {
        echo '<form id="' . $this->name . '_filter" class="filter" action="" method="post" style="display:inline">';
        echo '<select onchange="document.forms[\'' . $this->name . '_filter\'].submit();" name="' . $this->name . '">';

        foreach ($options as $option)
        {
            echo '<option value="' .  $option['id'] . '"';
            if ($option['selected'] == true)
            {
                echo " selected=\"selected\"";
            }
            echo '>' . $option['title'] . '</option>';
        }
        echo "\n</select>\n</form>\n";
    }

    /**
     * Renderer for 'multiselect' mode
     *
     * @param array $options The options to render
     */
    private function _render_multiselect(array $options)
    {
        $l10n = midcom::get('i18n')->get_l10n('org.openpsa.core');
        echo '<form id="' . $this->name . '_form" action="" class="filter" method="post">';
        echo '<select id="select_' . $this->name . '" name="' . $this->name . '[]" multiple="multiple" >';

        foreach ($options as $option)
        {
            echo '<option value="' . $option['id'] . '"';
            if ($option['selected'] == true)
            {
                echo "selected=\"selected\"";
            }
            echo '>' . $option['title'];
            echo "\n</option>\n";
        }
        echo "\n</select>\n";
        echo '<img src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/ok.png" class="filter_action filter_apply" title="' . $l10n->get("apply") . '" />';
        echo '<img src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png" class="filter_action filter_unset" title="' . $l10n->get("unset") . '" />';
        echo "\n</form>\n";
        echo '<script type="text/javascript">';
        echo "\$(document).ready(function()\n{\n\n$('#select_" . $this->name . "').dropdownchecklist({\n";
        echo " maxDropHeight: 200,\n emptyText: '" . $this->_config['helptext'] . "' });\n});\n";
        echo "\n</script>\n";
    }

    /**
     * Returns an option array for rendering,
     *
     * May use option_callback config setting to populate the options array
     *
     * @param array The options array
     */
    private function _get_options()
    {
        if (!empty($this->_options))
        {
            $data = $this->_options;
        }
        else if (isset($this->_config['option_callback']))
        {
            $data = call_user_func($this->_config['option_callback']);
        }

        $options = array();
        foreach ($data as $id => $title)
        {
            $option = array('id' => $id, 'title' => $title);
            if (   !empty($this->_selection)
                && in_array($id, $this->_selection))
            {
                $option['selected'] = true;
            }
            else
            {
                $option['selected'] = false;
            }
            $options[] = $option;
        }
        return $options;
    }
}
?>