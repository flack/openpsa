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
        if (!is_array($value)) {
            debug_add('value is not an array');
            return false;
        }
        // This comes from AJAX editor. @todo: Find out why the format is different
        if (is_string($value[0])) {
            $date = $value[0];
        } else {
            if (!isset($value[0][$options . '_date'])) {
                debug_add('date is missing');
                return false;
            }
            $date = $value[0][$options . '_date'];
        }

        if (isset($value[0][$options . '_hours'])) {
            $time = $this->_sanitize_time($value, $options);
            if ($time === false) {
                return false;
            }
            $date .= $time;
        }

        /*
         * if the field has the default value or is empty, the user gets a free pass
         * (if the field is required, this is caught by a separate rule)
         */
        if (   $date == ""
            || $date == "0000-00-00"
            || $date == "0000-00-00 00:00:00") {
            debug_add("value {$date} is assumed to be intentionally blank");
            return true;
        }
        if (    preg_match("/^\d{4}-\d{2}-\d{2}/", $date) == 0
             && preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $date) == 0) {
            debug_add("value {$date} is incorrectly formatted");
            return false;
        }

        $date_time = explode(" ", $date);
        $date_array = explode("-", $date);

        if (is_array($date_time)) {
            $date_array = explode("-", $date_time[0]);
        }

        return checkdate($date_array[1], $date_array[2], $date_array[0]);
    }

    private function _sanitize_time($value, $options)
    {
        if (   empty($value[0][$options . '_hours'])
            && empty($value[0][$options . '_minutes'])
            && empty($value[0][$options . '_seconds'])) {
            return '';
        }
        $hours = $value[0][$options . '_hours'];
        if (!$this->_check_time($hours, 24)) {
            return false;
        }

        if (isset($value[0][$options . '_minutes'])) {
            $minutes = $value[0][$options . '_minutes'];
            if (!$this->_check_time($minutes, 60)) {
                return false;
            }
        } else {
            $minutes = '00';
        }
        if (isset($value[0][$options . '_seconds'])) {
            $seconds = $value[0][$options . '_seconds'];
            if (!$this->_check_time($seconds, 60)) {
                return false;
            }
        } else {
            $seconds = '00';
        }

        return ' ' . sprintf('%2s', $hours) . ':' . sprintf('%2s', $minutes) . ':' . sprintf('%2s', $seconds);
    }

    private function _check_time($input, $max)
    {
        return (is_numeric($input) && $input >= 0 && $input < $max);
    }
}
