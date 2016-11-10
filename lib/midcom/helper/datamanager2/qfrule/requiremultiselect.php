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
class midcom_helper_datamanager2_qfrule_requiremultiselect extends HTML_QuickForm_Rule
{
    function validate($value, $options = null)
    {
        if (   !is_array($value)
            || empty($value)) {
            debug_print_r('got value', $value);
            debug_add('value is not array or is empty');
            return false;
        }
        debug_add('value is non-empty array');
        return true;
    }
}
