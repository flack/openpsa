<?php
/**
 * @package midcom.grid
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\grid;

use midcom;
use midcom_error;

/**
 * Helper class for jqgrid widgets
 *
 * @package midcom.grid
 */
class grid
{
    /**
     * The grid's ID
     */
    private string $_identifier;

    /**
     * The grid's options (converted for use in JS constructor)
     */
    private array $_options = [];

    /**
     * The grid's options as passed in PHP
     */
    private array $_raw_options = [];

    /**
     * The grid's columns
     *
     * They have the following structure
     *
     * 'key' => [
     *     'label' => "Some label",
     *     'options' => 'javascript option string'
     * ]
     */
    private array $_columns = [];

    /**
     * Flag that tracks if JS/CSS files have already been added
     */
    private static bool $_head_elements_added = false;

    /**
     * Data for the table footer
     */
    private array $_footer_data = [];

    /**
     * Should formatters be applied to footer row
     */
    private bool $format_footer = true;

    /**
     * The data provider, if any
     */
    private ?provider $_provider = null;

    /**
     * Javascript code that should be prepended to the widget constructor
     */
    private string $_prepend_js = '';

    /**
     * Adds the necessary javascript & css files for jqgrid
     */
    public static function add_head_elements()
    {
        if (self::$_head_elements_added) {
            return;
        }
        $version = '4.15.4';
        $jqgrid_path = '/midcom.grid/jqGrid-' . $version . '/';

        $head = midcom::get()->head;
        $head->enable_jquery_ui(['button', 'mouse', 'resizable']);

        //needed js/css-files for jqgrid
        $lang = "en";
        $language = midcom::get()->i18n->get_current_language();
        if (file_exists(MIDCOM_STATIC_ROOT . $jqgrid_path . 'i18n/grid.locale-' . $language . '.js')) {
            $lang = $language;
        }
        $head->add_jsfile(MIDCOM_STATIC_URL . $jqgrid_path . 'i18n/grid.locale-'. $lang . '.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . $jqgrid_path . 'jquery.jqgrid.min.js');

        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.grid/jqGrid.custom.js');

        $head->add_stylesheet(MIDCOM_STATIC_URL . $jqgrid_path . 'ui.jqgrid.min.css');
        $head->add_stylesheet(MIDCOM_STATIC_URL . "/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css");
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.grid/jqGrid.custom.css');
        self::$_head_elements_added = true;
    }

    /**
     * Constructor. Add head elements when necessary and the ID column
     */
    public function __construct(string $identifier, string $datatype)
    {
        $this->_identifier = $identifier;
        $this->set_column('id', 'id', 'hidden:true, key:true');
        $this->set_option('datatype', $datatype);
        self::add_head_elements();
    }

    public function set_provider(provider $provider)
    {
        $this->_provider = $provider;
    }

    public function get_provider()
    {
        return $this->_provider;
    }

    /**
     * Returns the grid's ID
     */
    public function get_identifier() : string
    {
        return $this->_identifier;
    }

    /**
     * Set an option
     */
    public function set_option(string $key, $value, bool $autoquote_string = true) : self
    {
        $this->_raw_options[$key] = $value;
        if ($autoquote_string || !is_string($value)) {
            $value = json_encode($value);
        }
        $this->_options[$key] = $value;
        return $this;
    }

    public function get_option(string $key)
    {
        if (empty($this->_raw_options[$key])) {
            return null;
        }
        return $this->_raw_options[$key];
    }

    /**
     * Set a column
     *
     * @param array $selectdata Should the column have a separate index, if so, which sort type
     */
    public function set_select_column(string $name, string $label, string $options, array $selectdata) : self
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
     * @param string $separate_index Should the column have a separate index, if so, which sort type
     */
    public function set_column(string $name, string $label, string $options = '', $separate_index = false) : self
    {
        if (empty($name)) {
            throw new midcom_error('Invalid column name ' . $name);
        }
        $this->_columns[$name] = [
            'label' => $label,
            'options' => $options,
            'separate_index' => $separate_index
        ];
        return $this;
    }

    public function add_pager(int $rows_per_page = 30) : self
    {
        $this->set_option('pager', '#p_' . $this->_identifier);
        $this->set_option('rowNum', $rows_per_page);
        return $this;
    }

    /**
     * Removes a column
     */
    public function remove_column(string $name)
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
    public function set_footer_data($data, $value = null, bool $formatted = true)
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
     */
    public function prepend_js(string $string)
    {
        $this->_prepend_js .= $string . "\n";
    }

    /**
     * Renders the grid as HTML
     */
    public function render(?array $entries = null)
    {
        if (is_array($entries)) {
            if (null !== $this->_provider) {
                $this->_provider->set_rows($entries);
            } else {
                $this->_provider = new provider($entries, $this->get_option('datatype'));
                $this->_provider->set_grid($this);
            }
        }
        echo (string) $this;
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
        $string .= 'midcom_grid_helper.setup_grid("' . $this->_identifier . '", {';

        $colnames = [];
        foreach ($this->_columns as $name => $column) {
            if ($column['separate_index']) {
                $colnames[] = 'index_' . $name;
            }
            $colnames[] = $column['label'];
        }
        $string .= "\ncolNames: " . json_encode($colnames) . ",\n";

        $string .= $this->_render_colmodel();

        $total_options = count($this->_options);
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
            $format = json_encode($this->format_footer);
            $string .= 'jQuery("#' . $this->_identifier . '").jqGrid("footerData", "set", ' . json_encode($this->_footer_data) . ", " . $format . ");\n";
        }

        $string .= '//]]></script>';
        return $string;
    }

    private function _render_colmodel() : string
    {
        $string = "colModel: [\n";
        $total_columns = count($this->_columns);
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
