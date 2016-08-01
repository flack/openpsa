<?php
/**
 * @package midcom.services
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\Intl\Intl;
use OpenPsa\Ranger\Ranger;

/**
 * Symfony Intl integration
 *
 * @package midcom.services
 */
class midcom_services_i18n_formatter
{
    /**
     *
     * @var string
     */
    private $language;

    public function __construct($language)
    {
        $this->language = $language;
    }

    public function number($value, $precision = 2)
    {
        if (   is_float($value)
            && version_compare(Intl::getIcuVersion(), '49', '<'))
        {
            // workaround for http://bugs.icu-project.org/trac/ticket/8561
            if ($precision == 0)
            {
                $value = (int) $precision;
            }
            else
            {
                $value = number_format($value, $precision, '|', '');
                $parts = explode('|', $value);
                $formatter = new NumberFormatter($this->get_locale(), NumberFormatter::DECIMAL);
                $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);

                $val = $formatter->format($parts[0]);
                return substr($val, 0, strlen($val) - $precision) . $parts[1];
            }
        }

        // The fallback implementation in Intl only supports DECIMAL, so we hardcode the style here..
        $formatter = new NumberFormatter($this->get_locale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);
        return $formatter->format($value);
    }

    public function date($value = null, $dateformat = 'medium', $timeformat = 'none')
    {
        if ($value === null)
        {
            $value = time();
        }
        //PHP < 5.3.4 compat
        if (   version_compare(PHP_VERSION, '5.3.5', '<')
            && $value instanceof DateTime)
        {
            $value = (int) $value->format('U') + timezone_offset_get($value->getTimeZone(), $value);
        }
        $formatter = new IntlDateFormatter($this->get_locale(), $this->constant($dateformat), $this->constant($timeformat));
        return $formatter->format($value);
    }

    public function time($value = null, $dateformat = 'none', $timeformat = 'short')
    {
        return $this->date($value, $dateformat, $timeformat);
    }

    public function datetime($value = null, $dateformat = 'medium', $timeformat = 'short')
    {
        return $this->date($value, $dateformat, $timeformat);
    }

    public function timeframe($start, $end, $mode = 'both', $range_separator = null, $fulldate = false)
    {
        $ranger = new Ranger($this->get_locale());
        if ($mode !== 'date')
        {
            $ranger->setTimeType(IntlDateFormatter::SHORT);
        }
        if ($fulldate)
        {
            $ranger->setDateType(IntlDateFormatter::FULL);
        }
        if ($range_separator !== null)
        {
            $ranger->setRangeSeparator($range_separator);
        }
        return $ranger->format($start, $end);
    }

    private function constant($input)
    {
        if (is_int($input))
        {
            return $input;
        }
        return constant('IntlDateFormatter::' . strtoupper($input));
    }

    private function get_locale()
    {
        return Intl::isExtensionLoaded() ? $this->language : 'en';
    }
}