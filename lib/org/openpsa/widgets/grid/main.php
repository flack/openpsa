<?php
/**
 * @package org.openpsa.widgets
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for jqgrid widgets
 *
 * @package org.openpsa.widgets
 */
class org_openpsa_widgets_grid extends midcom_baseclasses_components_purecode
{
    /**
     * The grid's ID
     *
     * @var string
     */
    private $_identifier;

    /**
     * The grid's options (converted for use in JS constructor)
     *
     * @var array
     */
    private $_options = array();

    /**
     * The grid's options as passed in PHP
     *
     * @var array
     */
    private $_raw_options = array();

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
     * Data for the table footer
     *
     * @var array
     */
    private $_footer_data = array();

    /**
     * Should formatters be applied to footer row
     *
     * @var boolean
     */
    private $format_footer = true;

    /**
     * The data provider, if any
     *
     * @var org_openpsa_widgets_grid_provider
     */
    private $_provider;

    /**
     * Javascript code that should be prepended to the widget constructor
     *
     * @var string
     */
    private $_prepend_js;

    /**
     * Adds the necessary javascript & css files for jqgrid
     */
    public static function add_head_elements()
    {
        if (self::$_head_elements_added) {
            return;
        }
        $version = '4.14.0';
        $jqgrid_path = '/org.openpsa.widgets/jqGrid-' . $version . '/';

        $head = midcom::get()->head;
        $head->enable_jquery_ui(array('button', 'mouse', 'resizable'));

        //needed js/css-files for jqgrid
        $lang = "en";
        $language = midcom::get()->i18n->get_current_language();
        if (file_exists(MIDCOM_STATIC_ROOT . $jqgrid_path . 'i18n/grid.locale-' . $language . '.js')) {
            $lang = $language;
        }
        $head->add_jsfile(MIDCOM_STATIC_URL . $jqgrid_path . 'i18n/grid.locale-'. $lang . '.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . $jqgrid_path . 'jquery.jqgrid.min.js');

        org_openpsa_widgets_ui::add_head_elements();
        $head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.widgets/jqGrid.custom.js');

        $head->add_stylesheet(MIDCOM_STATIC_URL . $jqgrid_path . 'ui.jqgrid.min.css');
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.widgets/jqGrid.custom.css');
        self::$_head_elements_added = true;
    }

    /**
     * Constructor. Add head elements when necessary and the ID column
     *
     * @param string $identifier The grid's ID
     * @param string $datatype The grid's data type
     */
    public function __construct($identifier, $datatype)
    {
        $this->_identifier = $identifier;
        $this->set_column('id', 'id', 'hidden:true, key:true');
        $this->set_option('datatype', $datatype);
        self::add_head_elements();
    }

    public function set_provider(org_openpsa_widgets_grid_provider $provider)
    {
        $this->_provider = $provider;
    }

    public function get_provider()
    {
        return $this->_provider;
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
        $this->_raw_options[$key] = $value;
        if (   $autoquote_string
            && is_string($value)) {
            $value = '"' . str_replace('"', '\\"', $value) . '"';
        } elseif ($value === true) {
            $value = 'true';
        } elseif ($value === false) {
            $value = 'false';
        } elseif (is_array($value)) {
            $value = json_encode($value);
        }
        $this->_options[$key] = $value;
        return $this;
    }

    public function get_option($key)
    {
        if (empty($this->_raw_options[$key])) {
            return null;
        }
        return $this->_raw_options[$key];
    }

    /**
     * Set a column
     *
     * @param string $name The column's name
     * @param string $label The column's label
     * @param string $options The column's options
     * @param array $selectdata Should the column have a separate index, if so, which sort type
     */
    public function set_select_column($name, $label, $options, array $selectdata)
    {
        $selectstring = implode(';', array_map(
            function ($key, $value) {
                return $key . ':' . $value;
            },
            array_keys($selectdata),
            $selectdata
        ));

        if ($options !== '') {
            $options .= ', ';
        }
        $options .= 'stype: "select", searchoptions: {sopt: ["eq"], value: ":;' . $selectstring . '"}';
        $options .= ', edittype:"select", formatter:"select", editoptions:{value:"' . $selectstring . '"}';

        return $this->set_column($name, $label, $options);
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
        if (empty($name)) {
            throw new midcom_error('Invalid column name ' . $name);
        }
        $this->_columns[$name] = array(
            'label' => $label,
            'options' => $options,
            'separate_index' => $separate_index
        );
        return $this;
    }

    public function add_pager($rows_per_page = 30)
    {
        $this->set_option('pager', '#p_' . $this->_identifier);
        $this->set_option('rowNum', $rows_per_page);
        return $this;
    }

    /**
     * Removes a column
     *
     * @param string $name The column's name
     */
    public function remove_column($name)
    {
        if (   empty($name)
            || !array_key_exists($name, $this->_columns)) {
            throw new midcom_error('Invalid column name ' . $name);
        }
        if ($this->_columns[$name]['separate_index']) {
            unset($this->_columns[$name . '_index']);
        }
        unset($this->_columns[$name]);
    }

    /**
     * Set the grid's footer data
     *
     * @param mixed $data The data to set as array or the column name
     * @param mixed $value The value, if setting individual columns
     * @param boolean $formatted Should formatters be applied to footer data
     */
    public function set_footer_data($data, $value = null, $formatted = true)
    {
        if (null == $value) {
            $this->_footer_data = $data;
        } else {
            $this->_footer_data[$data] = $value;
        }
        $this->format_footer = $formatted;
        $this->set_option('footerrow', true);
    }

    /**
     * Add Javascript code that should be run before the widget constructor
     *
     * @param string $string
     */
    public function prepend_js($string)
    {
        $this->_prepend_js .= $string . "\n";
    }

    /**
     * Renders the grid as HTML
     */
    public function render($entries = false)
    {
        if (is_array($entries)) {
            if (null !== $this->_provider) {
                $this->_provider->set_rows($entries);
            } else {
                $this->_provider = new org_openpsa_widgets_grid_provider($entries, $this->get_option('datatype'));
                $this->_provider->set_grid($this);
            }
        }
        echo $this->__toString();
    }

    public function __toString()
    {
        if ($this->_provider) {
            $this->_provider->setup_grid();
        }

        $string = '<table id="' . $this->_identifier . '"></table>';
        $string .= '<div id="p_' . $this->_identifier . '"></div>';
        $string .= '<script type="text/javascript">//<![CDATA[' . "\n";
        $string .= $this->_prepend_js;
        $string .= 'org_openpsa_grid_helper.setup_grid("' . $this->_identifier . '", {';

        $colnames = array();
        foreach ($this->_columns as $name => $column) {
            if ($column['separate_index']) {
                $colnames[] = 'index_' . $name;
            }
            $colnames[] = $column['label'];
        }
        $string .= "\ncolNames: " . json_encode($colnames) . ",\n";

        $string .= $this->_render_colmodel();

        $total_options = sizeof($this->_options);
        $i = 0;
        foreach ($this->_options as $name => $value) {
            $string .= $name . ': ' . $value;
            if (++$i < $total_options) {
                $string .= ',';
            }
            $string .= "\n";
        }
        $string .= "});\n";

        if ($this->_footer_data) {
            $format = $this->format_footer ? 'true' : 'false';
            $string .= 'jQuery("#' . $this->_identifier . '").jqGrid("footerData", "set", ' . json_encode($this->_footer_data) . ", " . $format . ");\n";
        }

        $string .= '//]]></script>';
        return $string;
    }

    private function _render_colmodel()
    {
        $string = "colModel: [\n";
        $total_columns = sizeof($this->_columns);
        $i = 0;
        foreach ($this->_columns as $name => $column) {
            if ($column['separate_index']) {
                $string .= '{name: "index_' . $name . '", index: "index_' . $name . '", ';
                $string .= 'sorttype: "' . $column['separate_index'] . '", hidden: true}' . ",\n";
            }

            $string .= '{name: "' . $name . '", ';
            if ($column['separate_index']) {
                $string .= 'index: "index_' . $name . '"';
            } else {
                $string .= 'index: "' . $name . '"';
            }
            if (!empty($column['options'])) {
                $string .= ', ' . $column['options'];
            }
            $string .= '}';
            if (++$i < $total_columns) {
                $string .= ",\n";
            }
        }
        $string .= "\n],\n";
        return $string;
    }
}
