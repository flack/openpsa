<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: jsdate.php 25328 2010-03-18 19:10:35Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 JS date widget
 *
 * This widget is built around the JS date calendar, it requires the date type or a subclass thereof.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>boolean show_time:</i> Boolean that controls, if the stored value is to be shown with or without
 *   the time-of-day. If omitted, 00:00:00 is assumed. Defaults to true.
 * - <i>int minyear:</i> Minimum Year available for selection, default see below.
 * - <i>int maxyear:</i> Maximum Year available for selection, default see below.
 *
 * <b>Default values for min/maxyear</b>
 *
 * If unspecified, it defaults to the 0-9999 range *unless* the base date type uses
 * the UNIXDATE storage mode, in which case 1970-2030 will be used instead.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_jsdate extends midcom_helper_datamanager2_widget
{
    /**
     * Indicates whether the timestamp should be shown or not.
     *
     * @var boolean
     */
    var $show_time = true;

    /**
     * Indicates whether the timestamp should show seconds or not.
     *
     * @var boolean
     */
    var $hide_seconds = false;

    /**
     * Minimum Year available for selection.
     *
     * @var int
     */
    var $minyear = 0;

    /**
     * Maximum Year available for selection.
     *
     * @var int
     */
    var $maxyear = 9999;

    /**
     * Used date format
     *
     * @var String
     */
    var $format = '%Y-%m-%d %H:%M:%S';

    /**
     * Adapts the min/maxyear defaults if the base date is set to UNIXDATE storage.
     */
    function _on_configuring()
    {
        if (   is_a($this->_type, 'midcom_helper_datamanager2_type_date')
            && $this->_type->storage_type == 'UNIXDATE')
        {
            $this->minyear = 1970;
            $this->maxyear = 2030;
        }
    }

    /**
     * Validates the base type.
     */
    function _on_initialize()
    {
        if (! is_a($this->_type, 'midcom_helper_datamanager2_type_date'))
        {
            debug_add("Warning, the field {$this->name} is not a select type or subclass thereof, you cannot use the select widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        if ($this->_initialize_dependencies)
        {
            $this->_add_external_html_elements();
        }

        return true;
    }

    /**
     * Adds the external HTML dependencies, both JS and CSS. A static flag prevents
     * multiple insertions of these dependencies.
     *
     * @access private
     */
    function _add_external_html_elements()
    {
        static $executed = false;

        if ($executed)
        {
            return;
        }

        $executed = true;

        $_MIDCOM->enable_jquery();
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . "/ui/jquery.ui.datepicker.min.js");

        $lang = $_MIDCOM->i18n->get_current_language();
        /*
         * The calender doesn't have all lang files and some are named differently
         * Since a missing lang file causes the calendar to break, let's make extra sure
         * that this won't happen
         */
        if (!file_exists(MIDCOM_STATIC_ROOT . "/jQuery/jquery.ui-{$GLOBALS['midcom_config']['jquery_ui_version']}/ui/i18n/jquery.ui.datepicker-{$lang}.min.js"))
        {
            $lang = $_MIDCOM->i18n->get_fallback_language();
            if (!file_exists(MIDCOM_STATIC_ROOT . "/jQuery/jquery.ui-{$GLOBALS['midcom_config']['jquery_ui_version']}/ui/i18n/jquery.ui.datepicker-{$lang}.min.js"))
            {
                $lang = 'en';
            }
        }

        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . "/ui/i18n/jquery.ui.datepicker-{$lang}.min.js");


        $attributes = array
        (
            'rel' => 'stylesheet',
            'type' => 'text/css',
            'href' => MIDCOM_JQUERY_UI_URL . '/themes/base/jquery.ui.theme.min.css',
        );
        $_MIDCOM->add_link_head($attributes);

        $attributes = array
        (
            'rel' => 'stylesheet',
            'type' => 'text/css',
            'href' => MIDCOM_JQUERY_UI_URL . '/themes/base/jquery.ui.datepicker.min.css',
        );
        $_MIDCOM->add_link_head($attributes);

    }

    /**
     * Generates the initscript for the current field.
     *
     * @return string The init script.
     */
    function _create_initscript()
    {
        $script = <<<EOT
<script type="text/javascript">
        jQuery("#{$this->_namespace}{$this->name}").datepicker(
        {
          maxDate: new Date({$this->maxyear}, 1, 1), 
          minDate: new Date({$this->minyear}, 1, 1),
          dateFormat: 'yy-mm-dd',
                //altFormat: 'yyyy-mm-dd',
                //altField: '#ID',
                });
</script>
EOT;
        return $script;
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    function add_elements_to_form()
    {
        $this->_add_external_html_elements();

        $elements = Array();
        $this->_create_unfrozen_elements($elements);

        $this->_form->addGroup($elements, $this->name, $this->_translate($this->_field['title']), '', false);

        if($this->_field['required'])
        {
            $errmsg = sprintf($this->_l10n->get('field %s is required'), $this->_field['title']);

            $this->_form->addRule($this->name, $errmsg, 'regex', '/^[^0]/');
        }
        $this->_form->addRule($this->name, $this->_translate('validation failed: date'), 'checkjsdate');
    }

    /**
     * Create the unfrozen element listing.
     */
    function _create_unfrozen_elements(&$elements)
    {
        $attributes = Array
        (
            'class' => 'date',
            'id'    => "{$this->_namespace}{$this->name}",
        );
        $elements[] = HTML_QuickForm::createElement('text', $this->name, '', $attributes);

        if ($this->show_time)
        {
            $attributes = Array
            (
                'class' => 'jsdate_hours',
                'id'    => "{$this->_namespace}{$this->name}_hours",
            );
            $elements[] = HTML_QuickForm::createElement('text', "{$this->name}_hours", '', $attributes);
            $attributes = Array
            (
                'class' => 'jsdate_minutes',
                'id'    => "{$this->_namespace}{$this->name}_minutes",
            );
            $elements[] = HTML_QuickForm::createElement('text', "{$this->name}_minutes", '', $attributes);

            if (!$this->hide_seconds)
            {
                $attributes = Array
                (
                    'class' => 'jsdate_minutes',
                    'id'    => "{$this->_namespace}{$this->name}_seconds",
                );
                $elements[] = HTML_QuickForm::createElement('text', "{$this->name}_secondss", '', $attributes);
            }
        }

        $elements[] = HTML_QuickForm::createElement('static', "{$this->name}_initscript", '', $this->_create_initscript());
    }

    /**
     * Create the frozen element listing.
     */
    function _create_frozen_elements(&$elements)
    {
        $attributes = Array
        (
            'class' => 'date',
            'id'    => $this->name,
        );
        $element = HTML_QuickForm::createElement('text', $this->name, '', $attributes);
        $element->freeze();
        $elements[] = $element;
    }

    /**
     * Freeze the entire group, special handling applies, the formgroup is replaced by a single
     * static element.
     */
    function freeze()
    {
        $new_elements = Array();
        $this->_create_frozen_elements($new_elements);

        $group = $this->_form->getElement($this->name);
        $group->setElements($new_elements);
    }

    /**
     * Unfreeze the entire group, special handling applies, the formgroup is replaced by a the
     * full input widget set.
     */
    function unfreeze()
    {
        $new_elements = Array();
        $this->_create_unfrozen_elements($new_elements);

        $group = $this->_form->getElement($this->name);
        $group->setElements($new_elements);
    }

    /**
     * The default call produces a simple text representation of the current date.
     */
    function get_default()
    {
        if ($this->_type->value->year == 0)
        {
            $this->_type->value->year = '0000';
        }
        if ($this->show_time)
        {
            if ($this->hide_seconds)
            {
                $this->format = '%Y-%m-%d %H:%M';
            }
            else
            {
                $this->format = '%Y-%m-%d %H:%M:%S';
            }
        }
        else
        {
            $this->format = '%Y-%m-%d';
        }

        return $this->_type->value->format($this->format);
    }

    /**
     * Check against partially missing user input
     *
     * Be liberal with input, strict with output
     *
     * @access public
     * @param mixed $input  User input
     * @return String       Formatted date
     */
    function check_user_input($input)
    {
        $input = trim($input);

        // If we have hidden seconds, we need to change format to save those seconds
        if ($this->format == '%Y-%m-%d %H:%M')
        {
            $this->format = '%Y-%m-%d %H:%M:%S';
        }

        static $valid_date_format = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';
        static $valid_datetime_format = '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/';

        // Input is strict ISO date with time
        if (preg_match($valid_datetime_format, $input))
        {
            return $input;
        }

        // Input is strict ISO date
        if (preg_match($valid_date_format, $input))
        {
            return "{$input} 00:00:00";
        }

        // Value is numeric, expecting UNIXTIME
        if (is_numeric($input))
        {
            return strftime($this->format, $input);
        }

        $date = null;
        $time = null;

        // Check against missing leading zeros from years, months, and days
        if (preg_match('/^([0-9]{2,4})-([0-9]{1,2})-([0-9]{1,2})/', $input, $regs))
        {
            $date = str_pad($regs[1], 4, date('Y')) . '-' . str_pad($regs[2], 2, '0') . '-' . str_pad($regs[3], 2, 0);
        }

        // Check against missing leading zeros from minutes and seconds
        if (preg_match('/^[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}\s*(.*)$/', $input, $regs))
        {
            // Fill in the leading zeros to hours
            if (!preg_match('/^[0-9]{2}/', $regs[1]))
            {
                $regs[1] = str_pad($regs[1], 2, '0');
            }

            // The rest should be all about filling in the missing end of the timestamp
            $time = $regs[1] . substr('00:00:00', strlen($regs[1]));
        }

        // Both date and time found, convert the input to hopefully full-fletched ISO datetime
        if (   $date
            && $time)
        {
            $input = "{$date} {$time}";
        }

        // Try to convert the input string to date
        $timestamp = strtotime($input);

        // Expected output is higher than zero with strtotime
        if ($timestamp > 0)
        {
            return strftime($this->format, $timestamp);
        }

        // Could not determine the datetime, give an empty date
        return '0000-00-00 00:00:00';
    }

    /**
     * Tells the base date class instance to parse the value from the input field.
     */
    function sync_type_with_widget($results)
    {
        // Try to fix the incorrect input
        $date = $this->check_user_input($results[$this->name]);

        $this->_type->value = new Date($date);
    }

    /**
     * Renders the date in the ISO format.
     */
    function render_content()
    {
        if ($this->show_time)
        {
            if ($this->hide_seconds)
            {
                $format = '%Y-%m-%d %H:%M';
            }
            else
            {
                $format = '%Y-%m-%d %H:%M:%S';
            }
        }
        else
        {
            $format = '%Y-%m-%d';
        }
        echo $this->_type->value->format($format);
    }
}
?>