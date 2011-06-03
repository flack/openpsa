<?php
/**
 * @package org.openpsa.core
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for jqgrid widgets
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_grid_widget extends midcom_baseclasses_components_purecode
{
    /**
     * The grid's ID
     *
     * @var string
     */
    private $_identifier;

    /**
     * The grid's options
     *
     * @var array
     */
    private $_options = array();

    /**
     * The grid's columns
     *
     * They have the following structure
     *
     * 'key' => array
     * (
     *     'label' => "Some label",
     *     'options' => 'javascript option string'
     * )
     *
     * @var array
     */
    private $_columns = array();

    /**
     * Flag that tracks if JS/CSS files have already been added
     *
     * @var boolean
     */
    private static $_head_elements_added = false;

    /**
     * function that loads the necessary javascript & css files for jqgrid
     */
    public static function add_head_elements()
    {
        if (self::$_head_elements_added)
        {
            return;
        }
        $version = midcom_baseclasses_components_configuration::get('org.openpsa.core', 'config')->get('jqgrid_version');
        $jqgrid_path = '/org.openpsa.core/jquery.jqGrid-' . $version . '/';

        $head = midcom::get('head');
        //first enable jquery - just in case it isn't loaded
        $head->enable_jquery();

        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');

        //needed js/css-files for jqgrid
        $lang = "en";
        $language = $_MIDCOM->i18n->get_current_language();
        if (file_exists(MIDCOM_STATIC_ROOT . $jqgrid_path . 'js/i18n/grid.locale-' . $language . '.js'))
        {
            $lang = $language;
        }
        $head->add_jsfile(MIDCOM_STATIC_URL . $jqgrid_path . 'js/i18n/grid.locale-'. $lang . '.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . $jqgrid_path . 'js/jquery.jqGrid.min.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.core/jqGrid.custom.js');

        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.mouse.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.resizable.min.js');

        $head->add_stylesheet(MIDCOM_STATIC_URL . $jqgrid_path . 'css/ui.jqgrid.css');
        $head->add_jquery_ui_theme();
        self::$_head_elements_added = true;
    }

    /**
     * Constructor. Add head elements when necessary and the ID column
     *
     * @param string $identifier The grid's ID
     * @param string $datatype The gird's data type
     */
    public function __construct($identifier, $datatype)
    {
        $this->_identifier = $identifier;
        $this->set_column('id', 'id', 'hidden:true, key:true');
        $this->set_option('datatype', $datatype);
        self::add_head_elements();
    }

    /**
     * Returns the grid's ID
     *
     * @return string
     */
    public function get_identifier()
    {
        return $this->_identifier;
    }

    /**
     * Set an option
     *
     * @param string $key The option's name
     * @param mixed $value The option's value
     * @param boolean $autoquote_string Should string values be quoted
     */
    public function set_option($key, $value, $autoquote_string = true)
    {
        if (   $autoquote_string
            && is_string($value))
        {
            $value = '"' . $value . '"';
        }
        else if ($value === true)
        {
            $value = 'true';
        }
        else if ($value === false)
        {
            $value = 'false';
        }
        $this->_options[$key] = $value;
        return $this;
    }

    /**
     * Set a column
     *
     * @param string $name The column's name
     * @param string $label The column's label
     * @param string $options The column's options
     * @param string $separate_index Should the column have a separate index, if so, which sort type
     */
    public function set_column($name, $label, $options = '', $separate_index = false)
    {
        if (empty($name))
        {
            throw new midcom_error('Invalid column name ' . $name);
        }
        $this->_columns[$name] = array
        (
            'label' => $label,
            'options' => $options,
            'separate_index' => $separate_index
        );
        return $this;
    }

    public function add_pager()
    {
        $this->set_option('pager', '#p_' . $this->_identifier);
    }

    /**
     * Removes a column
     *
     * @param string $name The column's name
     */
    public function remove_column($name)
    {
        if (   empty($name)
            || emtpy($this->_columns[$name]))
        {
            throw new midcom_error('Invalid column name ' . $name);
        }
        if ($this->_columns[$name]['separate_index'])
        {
            $this->_columns[$name . '_index'];
        }
        unset($this->_columns[$name]);
    }

    /**
     * Set the grid's footer data
     *
     * @param array $data The data to set
     */
    public function set_footer_data(array $data)
    {
        $this->_footer_data = $data;
        $this->set_option('footerrow', true);
    }

    /**
     * Renders the grid as HTML
     */
    public function render($entries = false)
    {
        echo '<table id="' . $this->_identifier . '"></table>';
        echo '<div id="p_' . $this->_identifier . '"></div>';
        echo '<script type="text/javascript">//<![CDATA[' . "\n";
        if (is_array($entries))
        {
            echo "var " . $this->_identifier . '_entries = ' . json_encode($entries) . "\n";
            $this->set_option('data', $this->_identifier . '_entries', false);
            $this->set_option('rowNum', sizeof($entries));
        }

        echo 'jQuery("#' . $this->_identifier . '").jqGrid({';

        $colnames = array();
        foreach ($this->_columns as $name => $column)
        {
            if ($column['separate_index'])
            {
                $colnames[] = 'index_' . $name;
            }
            $colnames[] = $column['label'];
        }
        echo "colNames: " . json_encode($colnames) . ",\n";

        $this->_render_colmodel();

        $total_options = sizeof($this->_options);
        $i = 0;
        foreach ($this->_options as $name => $value)
        {
            echo $name . ': ' . $value;
            if (++$i < $total_options)
            {
                echo ',';
            }
            echo "\n";
        }
        echo "});\n";
        if (is_array($this->_footer_data))
        {
            echo 'jQuery("#' . $this->_identifier . '").jqGrid("footerData", "set", ' . json_encode($this->_footer_data) . ");\n";
        }
        echo '//]]></script>';
    }

    private function _render_colmodel()
    {
        echo "colModel: [\n";
        $total_columns = sizeof($this->_columns);
        $i = 0;
        foreach ($this->_columns as $name => $column)
        {
            if ($column['separate_index'])
            {
                echo '{name: "index_' . $name . '", index: "index_' . $name . '", ';
                echo 'sorttype: "' . $column['separate_index'] . '", hidden: true}' . ",\n";
            }

            echo '{name:"' . $name . '", ';
            if ($column['separate_index'])
            {
                echo 'index: "index_' . $name . '"';
            }
            else
            {
                echo 'index: "' . $name . '"';
            }
            if (!empty($column['options']))
            {
                echo ', ' . $column['options'];
            }
            echo '}';
            if (++$i < $total_columns)
            {
                echo ",\n";
            }
        }
        echo "\n],\n";
    }
}
?>