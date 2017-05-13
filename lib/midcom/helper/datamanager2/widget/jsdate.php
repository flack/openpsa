<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 JS date widget
 *
 * This widget is built around the jQuery UI datepicker, it requires the date type or a subclass thereof.
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
 * the UNIXTIME storage mode, in which case 1970-2030 will be used instead.
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
    public $show_time = true;

    /**
     * Indicates whether the timestamp should include seconds or not.
     *
     * @var boolean
     */
    public $hide_seconds = true;

    /**
     * Minimum Year available for selection.
     *
     * @var int
     */
    public $minyear = 0;

    /**
     * Maximum Year available for selection.
     *
     * @var int
     */
    public $maxyear = 9999;

    /**
     * Used date format
     *
     * @var String
     */
    public $format = '%Y-%m-%d %H:%M';

    /**
     * When should the calendar be shown (possible values: button, focus, both)
     *
     * @var String
     */
    public $showOn = 'both';

    private $lang;

    /**
     * Adapts the min/maxyear defaults if the base date is set to UNIXTIME storage.
     */
    public function _on_configuring()
    {
        if (   is_a($this->_type, 'midcom_helper_datamanager2_type_date')
            && $this->_type->storage_type == 'UNIXTIME') {
            $this->minyear = 1970;
            $this->maxyear = 2030;
        }
    }

    /**
     * Validates the base type.
     */
    public function _on_initialize()
    {
        $this->_require_type_class('midcom_helper_datamanager2_type_date');
        $this->lang = self::add_head_elements();
    }

    /**
     * Adds the external HTML dependencies, both JS and CSS. A static flag prevents
     * multiple insertions of these dependencies.
     */
    public static function add_head_elements()
    {
        static $lang = false;

        if ($lang === false) {
            midcom::get()->head->enable_jquery_ui(array('datepicker'));

            $lang = midcom::get()->i18n->get_current_language();
            /*
             * The calendar doesn't have all lang files and some are named differently
             * Since a missing lang file causes the calendar to break, let's make extra sure
             * that this won't happen
             */
            if (!file_exists(MIDCOM_STATIC_ROOT . "/jQuery/jquery-ui-" . midcom::get()->config->get('jquery_ui_version') . "/i18n/datepicker-{$lang}.min.js")) {
                $lang = midcom::get()->i18n->get_fallback_language();
                if (!file_exists(MIDCOM_STATIC_ROOT . "/jQuery/jquery-ui-" . midcom::get()->config->get('jquery_ui_version') . "/i18n/datepicker-{$lang}.min.js")) {
                    $lang = null;
                }
            }

            if ($lang) {
                midcom::get()->head->add_jsfile(MIDCOM_JQUERY_UI_URL . "/i18n/datepicker-{$lang}.min.js");
            }
        }
        return $lang;
    }

    /**
     * Generates the initscript for the current field.
     *
     * @return string The init script.
     */
    private function _create_initscript()
    {
        $init_max = new DateTime($this->maxyear . '-12-31');
        $init_min = new DateTime($this->minyear . '-01-01');
        if (!empty($this->_type->max_date)) {
            $init_max = new DateTime($this->_type->max_date);
        }
        if (!empty($this->_type->min_date)) {
            $init_min = new DateTime($this->_type->min_date);
        }
        //need this due to js Date begins to count the months with 0 instead of 1
        $init_max_month = $init_max->format('n') - 1;
        $init_min_month = $init_min->format('n') - 1;
        $date_format = $this->lang ? '$.datepicker.regional.' . $this->lang . '.dateFormat' : '$.datepicker.ISO_8601';
        $script = <<<EOT
<script type="text/javascript">
        $(document).ready(
        function()
        {
            $("#{$this->_namespace}{$this->name}_input").datepicker(
            {
              maxDate: new Date({$init_max->format('Y')}, {$init_max_month}, {$init_max->format('d')}),
              minDate: new Date({$init_min->format('Y')}, {$init_min_month}, {$init_min->format('d')}),
              dateFormat: {$date_format},
              altField: "#{$this->_namespace}{$this->name}_date",
              altFormat: $.datepicker.ISO_8601,
              prevText: '',
              nextText: '',
              showOn: '{$this->showOn}'
            }).on('change', function() {
                if ($(this).val() == '') {
                    $("#{$this->_namespace}{$this->name}_date").val('');
                }
            });
            if ($("#{$this->_namespace}{$this->name}_date").val() && $("#{$this->_namespace}{$this->name}_date").val() !== '0000-00-00') {
                $("#{$this->_namespace}{$this->name}_input").datepicker('setDate', new Date($("#{$this->_namespace}{$this->name}_date").val()));
            }
        });
</script>
EOT;
        return $script;
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    public function add_elements_to_form($attributes)
    {
        self::add_head_elements();

        $elements = $this->_create_elements();

        $this->_form->addGroup($elements, $this->name, $this->_translate($this->_field['title']), array(' ', '', '', '', ''), false);

        $rules = array();
        if ($this->_field['required']) {
            $errmsg = sprintf($this->_l10n->get('field %s is required'), $this->_translate($this->_field['title']));
            $rules = array(
                array($errmsg, 'required'),
                array($errmsg, 'regex', '/^[^0]/')
            );
        }

        $this->_form->addRule(array($this->name), $this->_translate('validation failed: date'), 'checkjsdate', $this->name);
        $this->_form->addGroupRule($this->name, array($this->name . '_date' => $rules));
    }

    /**
     * Create the unfrozen element listing.
     *
     * @param boolean $frozen Should the elements be frozen or not
     */
    private function _create_elements($frozen = false)
    {
        $elements = array();
        $attributes = array(
            'id'    => "{$this->_namespace}{$this->name}_date",
        );
        $elements[] = $this->_form->createElement('hidden', $this->name . '_date', '', $attributes);
        $attributes = array(
            'class' => 'jsdate',
            'id'    => "{$this->_namespace}{$this->name}_input",
            'size'  => 10
        );
        $elements[] = $this->_form->createElement('text', $this->name . '_input', '', $attributes);

        if ($this->show_time) {
            $attributes = array(
                'class' => 'jsdate_hours',
                'id'    => "{$this->_namespace}{$this->name}_hours",
                'size'  => 2,
                'maxlength'  => 2
            );
            $elements[] = $this->_form->createElement('text', "{$this->name}_hours", '', $attributes);
            $elements[] = $this->_form->createElement('static', "{$this->name}_hours_separator", '', ':');
            $attributes = array(
                'class' => 'jsdate_minutes',
                'id'    => "{$this->_namespace}{$this->name}_minutes",
                'size'  => 2,
                'maxlength'  => 2
            );
            $elements[] = $this->_form->createElement('text', "{$this->name}_minutes", '', $attributes);

            if (!$this->hide_seconds) {
                $elements[] = $this->_form->createElement('static', "{$this->name}_minutes_separator", '', ':');
                $attributes = array(
                    'class' => 'jsdate_seconds',
                    'id'    => "{$this->_namespace}{$this->name}_seconds",
                    'size'  => 2,
                    'maxlength'  => 2
                );
                $elements[] = $this->_form->createElement('text', "{$this->name}_seconds", '', $attributes);
            }
        }

        if (!$frozen) {
            $elements[] = $this->_form->createElement('static', "{$this->name}_initscript", '', $this->_create_initscript());
        }
        return $elements;
    }

    /**
     * Freeze the entire group, special handling applies, the formgroup is replaced by a single
     * static element.
     */
    public function freeze()
    {
        $new_elements = $this->_create_elements(true);

        foreach ($new_elements as $element) {
            $element->freeze();
        }

        $group = $this->_form->getElement($this->name);
        $group->setElements($new_elements);
        $group->freeze();
    }

    /**
     * Unfreeze the entire group, special handling applies, the formgroup is replaced by a the
     * full input widget set.
     */
    public function unfreeze()
    {
        $new_elements = $this->_create_elements();

        $group = $this->_form->getElement($this->name);
        $group->setElements($new_elements);
    }

    /**
     * The default call produces a simple text representation of the current date.
     *
     * @todo do we really need to set this->format here?
     */
    public function get_default()
    {
        if (null === $this->_type->value) {
            return null;
        }

        $defaults = array($this->name . '_date' => '0000-00-00');
        $this->format = 'Y-m-d';

        if ($this->show_time) {
            $this->format = 'Y-m-d H:i';
            $defaults[$this->name . '_hours'] = '00';
            $defaults[$this->name . '_minutes'] = '00';
            if (!$this->hide_seconds) {
                $defaults[$this->name . '_seconds'] = '00';
                $this->format = 'Y-m-d H:i:s';
            }
        }

        if (!$this->_type->is_empty()) {
            $defaults[$this->name . '_date'] = $this->_type->value->format('Y-m-d');

            if ($this->show_time) {
                $defaults[$this->name . '_hours'] = $this->_type->value->format('H');
                $defaults[$this->name . '_minutes'] = $this->_type->value->format('i');

                if (!$this->hide_seconds) {
                    $defaults[$this->name . '_seconds'] = $this->_type->value->format('s');
                }
            }
        }
        return $defaults;
    }

    private function _normalize_time_input($string)
    {
        $output = trim($string);
        if (strlen($output) == 0) {
            $output = '00';
        }
        if (strlen($output) == 1) {
            $output = '0' . $output;
        }
        return $output;
    }

    /**
     * Check against partially missing user input
     *
     * Be liberal with input, strict with output
     *
     * @param array $results  User input
     * @return string       Formatted date
     */
    public function check_user_input($results)
    {
        $empty_date = "0000-00-00 00:00:00";

        // Could not find any input
        if (empty($results[$this->name . '_date'])) {
            return $empty_date;
        }

        $input = trim($results[$this->name . '_date']);

        if ($this->is_frozen()) {
            return $input;
        }
        if ($this->show_time) {
            $minutes = $this->_normalize_time_input($results[$this->name . '_minutes']);
            $hours = $this->_normalize_time_input($results[$this->name . '_hours']);

            $input .= ' ' . $hours . ':' . $minutes . ':';
            // If we have hidden seconds, we need to change format to save those seconds
            $this->format = '%Y-%m-%d %H:%M:%S';
            if ($this->hide_seconds) {
                $input .= '00';
            } else {
                $input .= $this->_normalize_time_input($results[$this->name . '_seconds']);
            }
        }

        $valid_date_format = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';
        $valid_datetime_format = '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/';

        // Input is strict ISO date with time
        if (preg_match($valid_datetime_format, $input)) {
            return $input;
        }

        // Input is strict ISO date
        if (preg_match($valid_date_format, $input)) {
            return "{$input} 00:00:00";
        }

        // Value is numeric, expecting UNIXTIME
        if (is_numeric($input)) {
            return strftime($this->format, $input);
        }

        $date = null;
        $time = null;

        // Check against missing leading zeros from years, months, and days
        if (preg_match('/^([0-9]{2,4})-([0-9]{1,2})-([0-9]{1,2})/', $input, $regs)) {
            $date = str_pad($regs[1], 4, date('Y')) . '-' . str_pad($regs[2], 2, '0') . '-' . str_pad($regs[3], 2, 0);
        }

        // Check against missing leading zeros from minutes and seconds
        if (preg_match('/^[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}\s*(.*)$/', $input, $regs)) {
            // Fill in the leading zeros to hours
            if (!preg_match('/^[0-9]{2}/', $regs[1])) {
                $regs[1] = str_pad($regs[1], 2, '0');
            }

            // The rest should be all about filling in the missing end of the timestamp
            $time = $regs[1] . substr('00:00:00', strlen($regs[1]));
        }

        // Both date and time found, convert the input to hopefully full-fletched ISO datetime
        if (   $date
            && $time) {
            $input = "{$date} {$time}";
        }

        // Try to convert the input string to date
        $timestamp = strtotime($input);

        // Expected output is higher than zero with strtotime
        if ($timestamp > 0) {
            return strftime($this->format, $timestamp);
        }

        // Could not determine the datetime, give an empty date
        return $empty_date;
    }

    /**
     * Tells the base date class instance to parse the value from the input field.
     */
    public function sync_type_with_widget($results)
    {
        // Try to fix the incorrect input
        $date = $this->check_user_input($results);
        $this->_type->value = new DateTime($date);
    }
}
