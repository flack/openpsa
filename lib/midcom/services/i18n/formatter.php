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

    public function __construct(string $language)
    {
        $this->language = $language;
    }

    public function number($value, int $precision = 2)
    {
        // The fallback implementation in Intl only supports DECIMAL, so we hardcode the style here..
        $formatter = new NumberFormatter($this->get_locale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);
        return $formatter->format($value);
    }

    public function date($value = null, $dateformat = 'medium')
    {
        return $this->datetime($value, $dateformat, IntlDateFormatter::NONE);
    }

    public function time($value = null, $timeformat = 'short')
    {
        return $this->datetime($value, IntlDateFormatter::NONE, $timeformat);
    }

    public function datetime($value = null, $dateformat = 'medium', $timeformat = 'short')
    {
        if ($value === null) {
            $value = time();
        }
        $formatter = new IntlDateFormatter($this->get_locale(), $this->constant($dateformat), $this->constant($timeformat));
        return $formatter->format($value);
    }

    public function customdate($value, string $pattern)
    {
        $formatter = new IntlDateFormatter($this->get_locale(), IntlDateFormatter::FULL, IntlDateFormatter::FULL);
        $formatter->setPattern($pattern);
        return $formatter->format($value);
    }

    public function timeframe($start, $end, string $mode = 'both', string $range_separator = null, bool $fulldate = false) : string
    {
        $ranger = new Ranger($this->get_locale());
        if ($mode !== 'date') {
            $ranger->setTimeType(IntlDateFormatter::SHORT);
        }
        if ($fulldate) {
            $ranger->setDateType(IntlDateFormatter::FULL);
        }
        if ($range_separator !== null) {
            $ranger->setRangeSeparator($range_separator);
        }
        return $ranger->format($start, $end);
    }

    private function constant($input) : int
    {
        if (is_int($input)) {
            return $input;
        }
        return constant('IntlDateFormatter::' . strtoupper($input));
    }

    private function get_locale() : string
    {
        return Intl::isExtensionLoaded() ? $this->language : 'en';
    }
}
