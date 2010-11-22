<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: date.php 4985 2007-01-16 18:47:47Z flack $
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
class midcom_helper_datamanager2_qfrule_date_manager
{
    var $rules = array
    (
        'checkjsdate',
        'checksimpledate',
    );

    function register_rules(&$form)
    {
        $current_file = __FILE__;
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('called');
        foreach ($this->rules as $rule_name)
        {
            $rule_class = "midcom_helper_datamanager2_qfrule_date_{$rule_name}";

            debug_add("form->registerRule('{$rule_name}', null, '{$rule_class}', '{$current_file}')");
            $stat = $form->registerRule($rule_name, null, $rule_class, $current_file);
            if (is_a($stat, 'pear_error'))
            {
                $msg = $stat->getMessage();
                debug_add("Got PEAR error '{$msg}' from form->registerRule(), when adding date rule", MIDCOM_LOG_WARN);
                continue;
            }
        }
        debug_pop();
    }
}

/**
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_qfrule_date_checkjsdate extends HTML_QuickForm_Rule
{
    function validate($value, $options = null)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('called');

        if ( !is_string($value))
        {
            debug_add('value is not a string');
            debug_pop();
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
            debug_pop();
            return true;
        }
        if ( preg_match("/^\d{4}-\d{2}-\d{2}/", $value) == 0
           && preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $value) == 0)
        {
            debug_add("value {$value} is incorrectly formatted");
            debug_pop();
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

/**
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_qfrule_date_checksimpledate extends HTML_QuickForm_Rule
{
    function validate($value, $options = null)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('called');

        if ( !is_array($value)
             || empty($value))
        {
            debug_add('value is not an array or empty');
            debug_pop();
            return false;
        }
        /*
         * if the fields have the default value, the user gets a free pass
         * (if the field is required, this is caught by a separate rule)
         */
        if ( $value['d'] == 0
             && $value['m'] == 0
             && $value['Y'] == 0)

        {
            debug_add("value is assumed to be intentionally blank");
            debug_pop();
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