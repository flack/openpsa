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
class midcom_helper_datamanager2_qfrule_checksimpledate extends HTML_QuickForm_Rule
{
    function validate($value, $options = null)
    {
        if ( !is_array($value)
             || empty($value))
        {
            debug_add('value is not an array or empty');
            return false;
        }
        /*
         * if the fields have the default value, the user gets a free pass
         * (if the field is required, this is caught by a separate rule)
         */
        if (    $value['d'] == 0
             && $value['m'] == 0
             && $value['Y'] == 0)
        {
            debug_add("value is assumed to be intentionally blank");
            return true;
        }
        $ret = checkdate($value['m'], $value['d'], $value['Y']);
        return $ret;
    }

    function getValidationScript($options = null)
    {
        return array('', '');
    }
}
?>