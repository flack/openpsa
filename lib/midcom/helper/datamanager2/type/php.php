<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Simple php datatype. The php value encapsulated by this type is
 * passed as-is to the storage layers, no specialties done, just a string.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_php extends midcom_helper_datamanager2_type
{
    /**
     * Modes used in editor
     *
     * @var array
     */
    public $modes = array('xml', 'javascript', 'css', 'clike', 'php');

    /**
     * Widget version
     */
    public $version = '5.7.0';

    /**
     * The current string encapsulated by this type.
     *
     * @var string
     */
    public $value;

    var $code_valid = true;
    var $code_valid_errors = array();

    /**
     * Whether to enable the widget for preview
     */
    public $enabled = true;

    public function _on_initialize()
    {
        if ($this->enabled) {
            $prefix = MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/codemirror-' . $this->version;

            midcom::get()->head->add_stylesheet($prefix . '/lib/codemirror.css');
            midcom::get()->head->add_stylesheet($prefix . '/theme/eclipse.css');

            midcom::get()->head->add_jsfile($prefix . '/lib/codemirror.js');
            foreach ($this->modes as $mode) {
                midcom::get()->head->add_jsfile($prefix . '/mode/' . $mode . '/' . $mode . '.js');
            }
        }
    }

    public function convert_from_storage($source)
    {
        $this->value = $source;
    }

    public function convert_to_storage()
    {
        // Normalize line breaks to the UNIX format
        $this->value = preg_replace("/\n\r|\r\n|\r/", "\n", (string) $this->value);

        return $this->value;
    }

    public function convert_from_csv($source)
    {
        $this->value = $source;
    }

    public function convert_to_csv()
    {
        return (string) $this->value;
    }

    /**
     * The validation callback ensures that we don't have an array or an object
     * as a value, which would be wrong. It also checks for syntax errors
     *
     * @return boolean Indicating validity.
     */
    public function _on_validate()
    {
        if (   is_array($this->value)
            || is_object($this->value)) {
            $this->validation_error = $this->_l10n->get('type text: value may not be array or object');
            return false;
        }

        $tmpfile = tempnam(midcom::get()->config->get('midcom_tempdir'), 'midcom_helper_datamanager2_type_php_');
        file_put_contents($tmpfile, $this->value);
        $return_status = 0;
        $parse_results = array();
        exec("php -l {$tmpfile} 2>&1", $parse_results, $return_status);
        unlink($tmpfile);

        debug_print_r("'php -l {$tmpfile}' returned:", $parse_results);

        if ($return_status !== 0) {
            $parse_result = array_pop($parse_results);
            if (strpos($parse_result, 'No syntax errors detected in ' . $tmpfile) !== false) {
                // We have an error, but it's most likely a false positive,
                // e.g. a PHP startup error under mgd2
                return true;
            }
            $parse_result = array_pop($parse_results);
            $line = preg_replace('/^.+?on line (\d+).*?$/s', '\1', $parse_result);
            $this->validation_error = sprintf($this->_l10n->get('type php: parse error in line %s'), $line);

            return false;
        }

        return true;
    }

    public function convert_to_html()
    {
        if (!$this->enabled) {
            return highlight_string((string) $this->value, true);
        }

        $html = "<textarea rows=\"30\" cols=\"100%\" class=\"codemirror php\" id=\"codemirror_{$this->name}\" name=\"codemirror_{$this->name}\">{$this->value}</textarea>";

        $html .= '<script type="text/javascript">$(document).ready(function(){';
        $config = midcom_helper_misc::get_snippet_content_graceful($this->_config->get('codemirror_config_snippet'));
        $config = str_replace('{$id}', 'codemirror_' . $this->name, $config);
        $config = str_replace('{$read_only}', '"nocursor"', $config);

        $html .= $config . '});</script>';

        return $html;
    }
}
