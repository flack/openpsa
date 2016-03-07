<?php
/**
 * @package midcom.services
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\Intl\Intl;

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

    public function number($value)
    {
        // The fallback implementation in Intl only supports DECIMAL, so we hardcode the style here..
        $formatter = new NumberFormatter($this->get_locale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        return $formatter->format($value);
    }

    public function date($value = null, $dateformat = 'medium', $timeformat = 'none')
    {
        if ($value === null)
        {
            $value = time();
        }
        //PHP < 5.3.4 compat
        if ($value instanceof DateTime)
        {
            $value = (int) $value->format('U');
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

    public function timeframe($start, $end, $mode = 'both')
    {
        $startday = $this->date($start);
        $endday = $this->date($end);
        if ($mode == 'date')
        {
            return $startday . ' ' . json_decode('"\u2013"') . ' ' . $endday;
        }

        $starttime = $this->date($start, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
        $endtime = $this->date($end, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);

        $ret = $starttime . ' ' . json_decode('"\u2013"');

        if ($mode == 'both')
        {
            if ($startday == $endday)
            {
                $ret = $startday . ', ' . $ret;
            }
            else
            {
                $ret = $startday . ' ' . $ret . ' ' . $endday;
            }
        }
        return $ret . ' ' . $endtime;
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