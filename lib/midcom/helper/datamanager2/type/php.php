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
     * The current string encapsulated by this type.
     *
     * @var string
     */
    public $value = '';

    var $code_valid = true;
    var $code_valid_errors = array();

    /**
     * Whether to actually enable editarea for preview
     */
    var $editarea_enabled = true;

    public function _on_initialize()
    {
        //if (strpos($_SERVER['HTTP_USER_AGENT'], 'WebKit') !== false)
        //{
        //    // EditArea really messes up Asgard for WebKit browsers
        //    $this->editarea_enabled = false;
        //}

        if ($this->editarea_enabled)
        {
            $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/edit_area/edit_area_full.js');
        }

        return true;
    }

    function convert_from_storage ($source)
    {
        $this->value = $source;
    }

    function convert_to_storage()
    {
        // Normalize line breaks to the UNIX format
        $this->value = preg_replace("/\n\r|\r\n|\r/", "\n", $this->value);

        return $this->value;
    }

    function convert_from_csv ($source)
    {
        $this->value = $source;
    }

    function convert_to_csv()
    {
        return $this->value;
    }

    /**
     * The validation callback ensures that we don't have an array or an object
     * as a value, which would be wrong.
     *
     * @return boolean Indicating validity.
     */
    public function _on_validate()
    {
        if (   is_array($this->value)
            || is_object($this->value))
        {
            $this->validation_error = $this->_l10n->get('type text: value may not be array or object');
            return false;
        }

        $tmpfile = tempnam($GLOBALS['midcom_config']['midcom_tempdir'], 'midcom_helper_datamanager2_type_php_');
        $fp = fopen($tmpfile, 'w');
        fwrite($fp, $this->value);
        fclose($fp);
        $parse_results = `php -l {$tmpfile}`;
        debug_add("'php -l {$tmpfile}' returned: \n===\n{$parse_results}\n===\n");
        unlink($tmpfile);

        if (strstr($parse_results, 'Parse error'))
        {
            $line = preg_replace('/\n.+?on line (\d+?)\n.*\n/', '\1', $parse_results);
            $this->validation_error = sprintf($this->_l10n->get('type php: parse error in line %s'), $line);

            return false;
        }

        return true;
    }

    function convert_to_html()
    {
        if (!$this->editarea_enabled)
        {
            return highlight_string($this->value, true);
        }

        $html = "<textarea rows=\"30\" cols=\"100%\" class=\"editarea php\" id=\"editarea_{$this->name}\" name=\"editarea_{$this->name}\">{$this->value}</textarea>";

        $_MIDCOM->add_jscript("
            editAreaLoader.init({
                id : 'editarea_" . $this->name . "',
                syntax: 'php',
                start_highlight: true,
                allow_toggle: false,
                show_line_colors: true,
                is_editable: false,
                fullscreen: false
            });
        ");

        return $html;
    }
}
?>