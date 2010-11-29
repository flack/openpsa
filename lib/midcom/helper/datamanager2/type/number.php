<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: number.php 14329 2008-01-08 23:20:19Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Simple number datatype. The type is based on the PHP floating point
 * types, not an arbitrary precision number system.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>int precision:</i> The maximum precision that should be kept in then value.
 *   Rounding will happen either during validation or when using the set_value
 *   member function instead of setting the value directly. The PHP function round()
 *   is used for rounding, see its documentation of the precision parameter for further
 *   details. If you set this value to null, rounding is skipped (the default).
 * - <i>int minimum:</i> The minimum value the type may take, inclusive. Set this to
 *   null to disable the lower bound, which is the default.
 * - <i>int maximum:</i> The maximum value the type may take, inclusive. Set this to
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
    var $value = 0.0;

    /**
     * The precision of the type, null means full available precision, while 0 emulates
     * an integer type. See also the PHP round() function's documentation about precision
     * specifiers.
     *
     * @var int
     * @see round()
     */
    var $precision = null;

    /**
     * The lower bound of valid values, set to null to disable checking (default).
     *
     * @var float
     * @see round()
     */
    var $minimum = null;

    /**
     * The upper bound of valid values, set to null to disable checking (default).
     *
     * @var float
     * @see round()
     */
    var $maximum = null;

    /**
     * Explicitly converts the passed value into a float, there is a str_replace()
     * added to account for possibly broken localized strings (although they shouldn't
     * happen during saving, you never know).
     *
     * @param mixed $source The storage data structure.
     */
    function convert_from_storage ($source)
    {
        if ($source === false || $source === null || $source == '')
        {
            $this->value = null;
        }
        else
        {
            $this->value = (float) str_replace(',', '.', $source);
        }
        $this->_round_value();
    }

    /**
     * The current value is converted into a string before being passed to the
     * caller. Any decimal characters that are not in a form PHP can recognize
     * on parsing will be unified before returning them.
     *
     * This conversion assumes that the current value is already rounded (usually
     * done by either set_value() or validate().
     *
     * @return string The string representation of the flaoting point number.
     */
    function convert_to_storage()
    {
        return str_replace(',', '.', (string) $this->value);
    }

    /**
     * Renders localized and rounded to specified precision.
     */
    function convert_to_html()
    {
        if ($this->precision !== null)
        {
            $locale_info = localeconv();
            return number_format($this->value, $this->precision, $locale_info['decimal_point'], $locale_info['thousands_sep']);
        }
        else
        {
            return htmlspecialchars($this->value);
        }
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
        if (is_string($value))
        {
            $value = (float) str_replace(',', '.', $value);
        }

        $this->value = (float) $value;
        $this->_round_value();
    }

    /**
     * Rounds the value according to the precision rules. If arbitrary precision is set,
     * no rounding is done, and the function exits without changing the value.
     */
    function _round_value()
    {
        if (! $this->value)
        {
            // Skip process, we are undefined.
            return;
        }
        if (! is_float($this->value))
        {
            $this->value = (float) str_replace(',', '.', $this->value);
        }
        if ($this->precision !== null)
        {
            $this->value = round($this->value, $this->precision);
        }
    }

    /**
     * CVS conversion is mapped to regular type conversion.
     */
    function convert_from_csv ($source)
    {
        $this->convert_from_storage($source);
    }

    /**
     * CVS conversion is mapped to regular type conversion.
     */
    function convert_to_csv()
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
        $this->_round_value();

        if (   $this->maximum !== null
            && $this->value > $this->maximum)
        {
            $this->validation_error = sprintf($this->_l10n->get('type number: value must not be larger then %s'),
                $this->maximum);
            return false;
        }
        if (   $this->minimum !== null
            && $this->value < $this->minimum)
        {
            $this->validation_error = sprintf($this->_l10n->get('type number: value must not be smaller then %s'),
                $this->minimum);
            return false;
        }

        return true;
    }
}
?>