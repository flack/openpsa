<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_qfrule_checkjsdate extends HTML_QuickForm_Rule
{
    function validate($value, $options = null)
    {
        if ( !is_string($value))
        {
            debug_add('value is not a string');
            return false;
        }
        /*
         * if the field has the default value or is empty, the user gets a free pass
         * (if the field is required, this is caught by a separate rule)
         */
        if (   $value == ""
            || $value == "0000-00-00"
            || $value == "0000-00-00 00:00:00")
        {
            debug_add("value {$value} is assumed to be intentionally blank");
            return true;
        }
        if ( preg_match("/^\d{4}-\d{2}-\d{2}/", $value) == 0
           && preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $value) == 0)
        {
            debug_add("value {$value} is incorrectly formatted");
            return false;
        }

        $date_time = explode(" ", $value);
        $date_array = explode("-", $value);

        if (is_array($date_time))
        {
            $date_array = explode("-", $date_time[0]);
        }

        $ret = checkdate($date_array[1], $date_array[2], $date_array[0]);
        return $ret;
    }

    function getValidationScript($options = null)
    {
        return array('', '');
    }
}
?>