<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Simple number datatype. The type is based on the PHP floating point
 * types, not an arbitrary precision number system.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>int precision:</i> The maximum precision that should be kept in the value.
 *   Rounding will happen either during validation or when using the set_value
 *   member function instead of setting the value directly. The PHP function round()
 *   is used for rounding, see its documentation of the precision parameter for further
 *   details. If you set this value to null, rounding is skipped (the default).
 * - <i>float minimum:</i> The minimum value the type may take, inclusive. Set this to
 *   null to disable the lower bound, which is the default.
 * - <i>float maximum:</i> The maximum value the type may take, inclusive. Set this to
 *   null to disable the upper bound, which is the default.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_number extends midcom_helper_datamanager2_type
{
    /**
     * The current string encapsulated by this type. This may be null
     * for undefined values.
     *
     * @var float
     */
    public $value = 0.0;

    /**
     * The precision of the type, null means full available precision, while 0 emulates
     * an integer type. See also the PHP round() function's documentation about precision
     * specifiers.
     *
     * @var int
     */
    public $precision;

    /**
     * The lower bound of valid values, set to null to disable checking (default).
     *
     * @var float
     */
    public $minimum;

    /**
     * The upper bound of valid values, set to null to disable checking (default).
     *
     * @var float
     */
    public $maximum;

    /**
     * Explicitly converts the passed value into a float, there is a str_replace()
     * added to account for possibly broken localized strings (although they shouldn't
     * happen during saving, you never know).
     *
     * @param mixed $source The storage data structure.
     */
    public function convert_from_storage ($source)
    {
        if ($source === false || $source === null || trim($source) === '')
        {
            $this->value = null;
        }
        else
        {
            $this->value = $this->sanitize_number($source);
        }
        $this->round_value();
    }

    /**
     * The current value is converted into a string before being passed to the
     * caller.
     *
     * This conversion assumes that the current value is already rounded (usually
     * done by either set_value() or validate().
     *
     * @return string The string representation of the floating point number.
     */
    public function convert_to_storage()
    {
        return (string) $this->sanitize_number($this->value);
    }

    /**
     * Any decimal characters that are not in a form PHP can recognize
     * on parsing will be unified before returning them.
     *
     * @param string $input
     * @return float
     */
    private function sanitize_number($input)
    {
        if (is_float($input))
        {
            return $input;
        }
        return (float) str_replace(',', '.', (string) $input);
    }

    /**
     * Renders localized and rounded to specified precision.
     */
    public function convert_to_html()
    {
        $value = $this->sanitize_number($this->value);
        if ($this->precision !== null)
        {
            $locale_info = localeconv();
            return number_format($value, $this->precision, $locale_info['decimal_point'], $locale_info['thousands_sep']);
        }
        return htmlspecialchars($value);
    }

    /**
     * Wrapper to set the value of this instance type aware: It enforces conversion
     * to a float type and rounds it to the correct precision if applicable. This
     * function should be preferred to regular assignment operations in case you plan
     * to do further work with the types value.
     *
     * Special care is taken for string values, which enforce a dot a decimal separator.
     *
     * @param float $value The value to set, enforces float conversion, so you may
     *     assign strings as well, as long as the automatic php float casting works.
     */
    function set_value($value)
    {
        $this->value = $this->sanitize_number($value);
        $this->round_value();
    }

    /**
     * Rounds the value according to the precision rules. If arbitrary precision is set,
     * no rounding is done, and the function exits without changing the value.
     */
    private function round_value()
    {
        if (!$this->value)
        {
            // Skip process, we are undefined.
            return;
        }
        $this->value = $this->sanitize_number($this->value);
        if ($this->precision !== null)
        {
            $this->value = round($this->value, $this->precision);
        }
    }

    /**
     * CSV conversion is mapped to regular type conversion.
     */
    public function convert_from_csv ($source)
    {
        $this->convert_from_storage($source);
    }

    /**
     * CSV conversion is mapped to regular type conversion.
     */
    public function convert_to_csv()
    {
        return $this->convert_to_storage();
    }

    /**
     * The validation callback ensures that we are in the bounds defined by the
     * type configuration. The value is rounded prior to processing.
     *
     * @return boolean Indicating validity.
     */
    public function _on_validate()
    {
        $this->round_value();

        if (   $this->maximum !== null
            && $this->value > $this->maximum)
        {
            $this->validation_error = sprintf($this->_l10n->get('type number: value must not be larger than %s'),
                $this->maximum);
            return false;
        }
        if (   $this->minimum !== null
            && $this->value < $this->minimum)
        {
            $this->validation_error = sprintf($this->_l10n->get('type number: value must not be smaller than %s'),
                $this->minimum);
            return false;
        }

        return true;
    }
}
