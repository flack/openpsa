<?php
/**
 * Created on Jan 12, 2006
 * @author tarjei huse based on comments found on php.net
 * @package midcom.helper.xml
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * A simple XML to array parser using expat.
 *
 * This class is based on some of the comments found on the
 * php.net manual pages, especially
 * bbellwfu at gmail dot com
 *
 * The parser works using a stack to add tads so it knows
 * where it is in the taglist.
 *
 * Usage:
 * $data = '<person><name>Jacop</name><id>7</id></person>';
 *
 * $array = midcom_helper_xml_toarray::parse($data);
 *
 *
 * var_dump($data);
 * array
 * 'person' =>
 *   array
 *     'name' =>
 *       array
 *         '_content' => 'Jacop'
 *     'id' =>
 *       array
 *         '_content' => '7'
 *
 * Please note that the '_content' key is always set.
 *
 * @package midcom.helper.xml
 */
class midcom_helper_xml_toarray
{
    /**
     * An instance of the Expat parser
     * @access private
     * @var resource
     */
    var $_parser;

    /**
     * Error string
     *
     * @var string
     */
    public $errstr = "";

    /**
     * The stack of tags currently being processed
     * @access private
     * @var array
     */
    var $_stack = array ();

    /**
     * reference to the current element
     * @var reference
     * @access private
     */
    var $_stack_ref;

    /**
     * The end output
     * @var array
     * @access private
     */
    var $_output = array ();

    /**
     * Simple wrapper.
     *
     * @param string the xml data
     * @return array the parsed data
     */
    public function parse($data)
    {
        return $this->_parse($data);
    }

    /**
     * Push an element to the stack
     * @access private
     */
    function _push_pos(&$pos)
    {
        $this->_stack[count($this->_stack)] = & $pos;
        $this->_stack_ref = & $pos;
    }

    function _pop_pos()
    {
        unset ($this->_stack[count($this->_stack) - 1]);
        $this->_stack_ref = & $this->_stack[count($this->_stack) - 1];
    }

    /**
     * Parse the data and build the array
     * @param string the xml to be parsed
     * @return array the new representation.
     */
    function _parse($xml)
    {
        if ($xml == '') {
            $this->errstr = "Empty string. Nothing to parse.";
            return false;
        }

        $this->_parser = xml_parser_create($_MIDCOM->i18n->get_current_charset());
        xml_set_object($this->_parser, $this);
        xml_set_element_handler($this->_parser, "_tag_open", "_tag_closed");
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_character_data_handler($this->_parser, "_tag_data");

        $this->_push_pos($this->_output);

        // Hide newlines from XML parser
        $xml = str_replace("\n", 'MIDCOM_HELPER_XML_NEWLINE', $xml);

        $result = xml_parse($this->_parser, $xml);
        if (!$result)
        {
            $this->errstr = sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($this->_parser)), xml_get_current_line_number($this->_parser));
            return false;
        }
        xml_parser_free($this->_parser);

        // Restore newlines from XML parsing
        foreach ($this->_output as $type => $fields)
        {
            foreach ($fields as $field => $value)
            {
                if (   is_array($this->_output[$type][$field])
                    && array_key_exists('_content', $this->_output[$type][$field]))
                {
                    $this->_output[$type][$field]['_content'] = str_replace('MIDCOM_HELPER_XML_NEWLINE', "\n", $value['_content']);
                }
            }
        }

        return $this->_output;
    }

    /**
     * This callback function is called when the parser finds an open
     * tag.
     * @param resource parser
     * @param string name of the xml tag
     * @param array attrs the attributes found in the tag.
     * @access private
     */
    function _tag_open($parser, $name, $attrs = array ())
    {
        if (isset ($this->_stack_ref[$name]))
        {
            if (!isset ($this->_stack_ref[$name][0]))
            {
                $tmp = $this->_stack_ref[$name];
                unset ($this->_stack_ref[$name]);
                $this->_stack_ref[$name][0] = $tmp;
            }
            $cnt = count($this->_stack_ref[$name]);
            $this->_stack_ref[$name][$cnt] = array ();
            if (count($attrs) > 0)
            {
                $this->_stack_ref[$name][$cnt]['attributes'] = $attrs;
            }
            $this->_stack_ref[$name][$cnt]['_content'] ="";

            $this->_push_pos($this->_stack_ref[$name][$cnt]);
        }
        else
        {
            $this->_stack_ref[$name] = array ('_content' => '');
            if (   isset ($attrs)
                && count($attrs) > 0)
            {
                $this->_stack_ref[$name]['attributes'] = $attrs;
            }
            $this->_push_pos($this->_stack_ref[$name]);
        }
    }

    /**
     * This function handles the data that is within
     * an xml tag.
     * @param resource parser
     * @param string the data.
     * @access private
     */
    function _tag_data($parser, $tag_data)
    {
        if (trim($tag_data))
        {
            if (isset ($this->_stack_ref['_content']))
                $this->_stack_ref['_content'] .= $tag_data;
            else
                $this->_stack_ref['_content'] = $tag_data;
        }
    }

    /**
     * this function is called when a tag ends.
     * @param resource parser
     * @param string the name of the tag.
     * @access private
     */
    function _tag_closed($parser, $name)
    {
        $this->_pop_pos();
    }
}
?>