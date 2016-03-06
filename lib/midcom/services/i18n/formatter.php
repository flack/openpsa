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

    public function date($value = null, $dateformat = 'medium', $timeformat = 'none')
    {
        if ($value === null)
        {
            $value = time();
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

    public function timeframe($start, $end, $include_date = true)
    {
        $startday = $this->date($start);
        $endday = $this->date($end);
        $starttime = $this->date($start, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
        $endtime = $this->date($end, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);

        $ret = $starttime . ' ' . json_decode('"\u2013"');

        if ($include_date)
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
        return Intl::isExtensionLoaded() ? $this->language : null;
    }
}