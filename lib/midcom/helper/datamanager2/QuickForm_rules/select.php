<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/** @ignore */
if (!class_exists('HTML_QuickForm_Rule'))
{
    require_once('HTML/QuickForm/Rule.php');
}

/**
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_qfrule_select_manager
{
    var $rules = array
    (
        'requiremultiselect',
    );

    function register_rules(&$form)
    {
        $current_file = __FILE__;
        debug_add('called');
        foreach ($this->rules as $rule_name)
        {
            $rule_class = "midcom_helper_datamanager2_qfrule_select_{$rule_name}";

            debug_add("form->registerRule('{$rule_name}', null, '{$rule_class}', '{$current_file}')");
            $stat = $form->registerRule($rule_name, null, $rule_class, $current_file);
            if (is_a($stat, 'pear_error'))
            {
                $msg = $stat->getMessage();
                debug_add("Got PEAR error '{$msg}' from form->registerRule(), when adding multiselect required rule", MIDCOM_LOG_WARN);
                continue;
            }
        }
    }
}

/**
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_qfrule_select_requiremultiselect extends HTML_QuickForm_Rule
{
    function validate($value, $options = null)
    {
        debug_add('called');
        debug_print_r('got value', $value);
        debug_print_r('got options', $options);
        if (   !is_array($value)
            || empty($value))
        {
            debug_add('value is not array or is empty');
            return false;
        }
        debug_add('value is non-empty array');
        return true;
    }

    function getValidationScript($options = null)
    {
        return array('', '');
    }
}
?>