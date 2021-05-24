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
     */
    public static function escape_attribute(string $input) : string
    {
        $output = str_replace('"', '&quot;', $input);
        return '"' . $output . '"';
    }
}
