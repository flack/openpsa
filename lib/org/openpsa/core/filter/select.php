<?php
/**
 * @package org.openpsa.core
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class that encapsulates a single select filter
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_filter_select extends org_openpsa_core_filter
{
    /**
     * The filter's options, if any
     *
     * @var array
     */
    protected $_options;

    /**
     * Callback to load the filter's options
     *
     * @var callable
     */
    protected $_option_callback;

    /**
     * The query operator
     *
     * @var string
     */
    protected $_operator;

    /**
     * Constructor
     *
     * @param string $name The filter's name
     * @param string $operator The constraint operator
     * @param array $options The filter's options, if any
     */
    public function __construct($name, $operator = '=', array $options = [])
    {
        $this->name = $name;
        $this->_operator = $operator;
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
        foreach ($this->_selection as $id) {
            $query->add_constraint($this->name, $this->_operator, (int) $id);
        }
        $query->end_group();
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $options = $this->_get_options();

        if (!empty($options)) {
            echo '<label>' . $this->_label . ': </label>';
            echo '<select class="filter_input" onchange="document.forms[\'' . $this->name . '_filter\'].submit();" name="' . $this->name . '">';

            foreach ($options as $option) {
                echo '<option value="' . $option['id'] . '"';
                if ($option['selected'] == true) {
                    echo " selected=\"selected\"";
                }
                echo '>' . $option['title'] . '</option>';
            }
            echo "\n</select>\n";
        }
    }

    public function set_callback($callback)
    {
        $this->_option_callback = $callback;
    }

    /**
     * Returns an option array for rendering,
     *
     * May use option_callback config setting to populate the options array
     *
     * @return array The options array
     */
    protected function _get_options() : array
    {
        if (!empty($this->_options)) {
            $data = $this->_options;
        } elseif (!empty($this->_option_callback)) {
            $data = call_user_func($this->_option_callback);
        }

        $options = [];
        foreach ($data as $id => $title) {
            $options[] = [
                'id' => $id,
                'title' => $title,
                'selected' => in_array($id, $this->_selection)
            ];
        }
        return $options;
    }
}
