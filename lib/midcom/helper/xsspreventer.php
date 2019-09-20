<?php
/**
 * @package midcom.helper
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Library for escaping user provided values displayed for example in input etc
 *
 * @package midcom.helper
 */
class midcom_helper_xsspreventer
{
    /**
     * Escape value of an XML attribute, also adds quotes around it
     *
     * @param string $input Attribute value to escape
     */
    public static function escape_attribute($input) : string
    {
        $output = str_replace('"', '&quot;', $input);
        return '"' . $output . '"';
    }

    /**
     * Escape contents of an XML element
     * (basically prevents early closure of the element)
     *
     * @param string $element XML element to close
     * @param string $input Element content to escape
     */
    public static function escape_element($element, $input)
    {
        return preg_replace_callback(
            "%(<\s*)+(/\s*)+{$element}%i",
            function ($matches) {
                return htmlentities($matches[0]);
            },
            $input
        );
    }
}
