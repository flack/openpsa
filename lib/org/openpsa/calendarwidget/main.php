<?php
/**
 * @package org.openpsa.calendarwidget
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Class for rendering calendar widgets
 *
 * Calendarwidget uses the hCalendar microformat to produce output that is easy to style via CSS
 * and can be easily converted to machine-readable iCalendar.
 *
 * Inspiration from http://www.meyerweb.com/eric/css/discuss/examples/notable-calendar.html
 *
 * @link http://www.microformats.org/wiki/hcalendar hCalendar microformat
 * @package org.openpsa.calendarwidget
 */
class org_openpsa_calendarwidget extends midcom_baseclasses_components_purecode
{
    /**
     * Which calendar are we showing,
     * Use constants like ORG_OPENPSA_CALENDARWIDGET_MONTH
     *
     * @var int
     */
    var $type = ORG_OPENPSA_CALENDARWIDGET_WEEK;

    /**
     * Length of default reservation slots, in seconds
     *
     * @var int
     */
    var $calendar_slot_length = 3600;

    /**
     * Year being currently shown
     *
     * @var int
     */
    var $year;

    /**
     * Month being currently shown
     *
     * @var int
     */
    var $month;

    /**
     * Day being currently shown
     *
     * @var int
     */
    var $day;

    /**
     * Hour to start the day view
     *
     * @var int
     */
    var $start_hour = 8;

    /**
     * Hour to end the day view
     *
     * @var int
     */
    var $end_hour = 17;

    /**
     * How wide the reservation columns should be
     * Value must be a valid CSS size option (pixels, percentage, em)
     *
     * @var string
     */
    var $column_width = 30;

    /**
     * How high the reservation cell units should be
     * Value must be integer of pixels
     *
     * @var int
     */
    var $cell_height = 40;

    /**
     * Optional HTML attributes for reservation slot <div />s
     *
     * @var Array
     */
    var $reservation_div_options = array();

    /**
     * Optional HTML attributes for free slot <div />s
     *
     * @var Array
     */
    var $free_div_options = array();

    /**
     * Resources and reservations to be rendered in the calendar as PHP array
     *
     * Example:
     *
     * <code>
     * <?php
     * $this->_resources = Array
     * (
     *     'c8b76e1e47b3427dfba717aad7a7c6a3' => array
     *     (
     *         'name'          => 'Henri Bergius',
     *         'resource_type' => ORG_OPENPSA_CALENDARWIDGET_RESOURCE_PERSON,
     *         'info_text'     => null,
     *         'css_class'     => 'blue',
     *         'reservations'  => array
     *         (
     *             '<event GUID>' => array
     *             (
     *                 'name'      => 'Training flight',
     *                 'location'  => 'Helsinki-Malmi airport',
     *                 'start'  => 1118005200,
     *                 'end'  => 1118005500,
     *             ),
     *         ),
     *     ),
     * );
     * ?>
     * </code>
     *
     * @var Array
     */
    public $_resources = Array();

    /**
     * Cache of reservations we've shown already
     *
     * @var Array
     */
    private $_reservations_shown = Array();

    /**
     * Cache of different timestamps used internally
     *
     * @var Array
     */
    private $_timestamp_cache = Array();

    /**
     * Initializes the class and sets the selected date to be shown
     *
     * @param int $year Selected year YYYY
     * @param int $month Selected month MM
     * @param int $day Selected day DD
     */
    public function __construct($year = null, $month = null, $day = null)
    {
        parent::__construct();

        // Default time shown is current
        if ($year)
        {
            $this->year = $year;
        }
        else
        {
            $this->year = date('Y');
        }

        if ($month)
        {
            $this->month = $month;
        }
        else
        {
            $this->month = date('m');
        }

        if ($day)
        {
            $this->day = $day;
        }
        else
        {
            $this->day = date('d');
        }
    }

    /**
     * Get start timestamp of the current month
     * @return integer Timestamp showing first second of the month
     */
    function get_month_start()
    {
        if (!array_key_exists('month_start', $this->_timestamp_cache))
        {
            $this->_timestamp_cache['month_start'] = mktime(0, 0, 0, $this->month, 1, $this->year);
        }
        return $this->_timestamp_cache['month_start'];
    }

    /**
     * Get end timestamp of the current month
     * @return integer Timestamp showing last second of the month
     */
    function get_month_end()
    {
        if (!array_key_exists('month_end', $this->_timestamp_cache))
        {
            $this->_timestamp_cache['month_end'] = mktime(23, 59, 59, $this->month + 1, 0, $this->year);
        }
        return $this->_timestamp_cache['month_end'];
    }

    /**
     * Get start timestamp of the current day
     * @return integer Timestamp showing first second of the day
     */
    function get_day_start()
    {
        if (!array_key_exists('day_start', $this->_timestamp_cache))
        {
            $this->_timestamp_cache['day_start'] = mktime(0, 0, 0, $this->month, $this->day, $this->year);
        }
        return $this->_timestamp_cache['day_start'];
    }

    /**
     * Get end timestamp of the current day
     * @return integer Timestamp showing last second of the day
     */
    function get_day_end()
    {
        if (!array_key_exists('day_end', $this->_timestamp_cache))
        {
            $this->_timestamp_cache['day_end'] = mktime(23, 59, 59, $this->month, $this->day, $this->year);
        }
        return $this->_timestamp_cache['day_end'];
    }


    /**
     * Get start timestamp of the selected week. Use this to tune queries for selecting reservations
     * @param integer $timestamp Timestamp to use instead of the current date
     * @return integer Timestamp showing first second of the week
     */
    function get_week_start($timestamp = null)
    {
        if ($timestamp)
        {
            return mktime(0, 0, 0, $this->month, date('d',$timestamp) - strftime('%u', $timestamp) + 1, $this->year);
        }
        else if (!array_key_exists('week_start', $this->_timestamp_cache))
        {
            $this->_timestamp_cache['week_start'] = mktime(0, 0, 0, $this->month, $this->day - strftime('%u', $this->get_day_start()) + 1, $this->year);
        }
        return $this->_timestamp_cache['week_start'];
    }

    /**
     * Get end timestamp of the selected week. Use this to tune queries for selecting reservations
     * @param integer $timestamp Timestamp to use instead of the current date
     * @return integer Timestamp showing last second of the week
     */
    function get_week_end($timestamp = null)
    {
        if ($timestamp)
        {
            return mktime(23, 59, 59, $this->month, strftime('%d', $this->get_week_start($timestamp)) + 6, $this->year);
        }
        if (!array_key_exists('week_end', $this->_timestamp_cache))
        {
            $this->_timestamp_cache['week_end'] = mktime(23, 59, 59, strftime('%m',$this->get_week_start()), strftime('%d',$this->get_week_start()) + 6, strftime('%Y',$this->get_week_start()));
        }
        return $this->_timestamp_cache['week_end'];
    }

    private function _get_reservations_between($resource, $start, $end)
    {
        $reservations_found = array();
        $reservations_added = 0;
        if (    !isset($resource['reservations'])
             || !is_array($resource['reservations']))
        {
            debug_add('resource[reservations] is not an array, aborting', MIDCOM_LOG_WARN);
            return $reservations_found;
        }
        // TODO: This is friggin' slow
        foreach ($resource['reservations'] as $res_guid => $reservation)
        {
            if (array_key_exists("{$resource['guid']}_{$res_guid}", $this->_reservations_shown))
            {
                // We've shown this already. Skip.
                continue;
            }

            if (!isset($reservation['start']))
            {
                continue;
            }

            if (    $reservation['start'] >= $start
                 && $reservation['start'] < $end)
            {
                // This reservation starts in current slot
                $reservation['guid'] = $res_guid;
                $reservations_found[$reservations_added] = $reservation;
                $reservations_added++;
                continue;
            }

            if ($reservation['end'] <= $end)
            {
                // This reservation ends in current slot
                $reservation['guid'] = $res_guid;
                $reservations_found[$reservations_added] = $reservation;
                $reservations_added++;
                continue;
            }

            if (   $reservation['end'] > $end
                && $reservation['start'] < $start)
            {
                // This reservation ends in current slot
                $reservation['guid'] = $res_guid;
                $reservations_found[$reservations_added] = $reservation;
                $reservations_added++;
                continue;
            }
        }
        return $reservations_found;
    }

    private function _get_day_slots($current_day)
    {
        // Create slots
        $slots = array();
        $slots_added = 0;
        if ($this->start_hour > 0)
        {
            $slots[$slots_added] = 'before';
        }

        $slot_start = mktime($this->start_hour, 0, 0, date('m',$current_day), date('d',$current_day), date('Y',$current_day));
        $slot_end = mktime($this->end_hour, 59, 0, date('m',$current_day), date('d',$current_day), date('Y',$current_day));

        $current_time = $slot_start;
        while ($current_time <= $slot_end)
        {
            $slots_added++;
            $slots[$slots_added] = $current_time;
            $current_time = $current_time + $this->calendar_slot_length;
        }

        if ($this->end_hour < 24)
        {
            $slots[$slots_added + 1] = 'after';
        }
        return $slots;
    }

    /**
     * Show the selected calendar. Outputs XHTML.
     */
    function show()
    {
        echo '<div id="org_openpsa_calendarwidget">';
        switch ($this->type)
        {
            case ORG_OPENPSA_CALENDARWIDGET_MONTH:
                $this->_show_month($this->get_month_start(), $this->get_month_end());
                break;
            case ORG_OPENPSA_CALENDARWIDGET_WEEK:
                $this->_show_week_verbose($this->get_week_start(), $this->get_week_end());
                break;
            case ORG_OPENPSA_CALENDARWIDGET_DAY:
                $this->_show_day_verbose_horizontal($this->get_day_start(), $this->get_day_end());
                break;
        }
        echo '</div>';
    }

    private function _show_month($start, $end)
    {
        $first_weekday = $this->get_week_start($start);
        $last_weekday = $this->get_week_end($end);
        $current_day = $first_weekday;

        echo '<div class="month">';
        // Loop through the given time range
        while ($current_day <= $last_weekday)
        {
            $next_day = mktime(0, 0, 0, date('m',$current_day), date('d',$current_day) + 1, date('Y',$current_day));
            if (    $current_day < $start
                 || $current_day > $end)
            {
                if($current_day == $first_weekday)
                {
                    echo "<div class=\"week\" style=\"clear: left\">";
                }

                echo "<div class=\"day\" style=\"width: {$this->column_width}px; height: {$this->cell_height}px;\"></div>";

                if($next_day == ($last_weekday + 1))
                {
                    echo "</div>";
                }
            }
            else
            {
                $this->_show_day($current_day, $next_day);
            }
            $current_day = $next_day;
        }
        echo '</div>';
    }

    private function _show_week_verbose($start, $end)
    {
        $current_day = $start;
        $i = 20;
        // Loop through the given time range
        while ($current_day <= $end && $i)
        {
            $next_day = mktime(0, 0, 0, date('m',$current_day), date('d',$current_day) + 1, date('Y',$current_day));
            $this->_show_day_verbose_horizontal($current_day, $next_day);
            $current_day = $next_day;
            $i--;
        }
    }

    private function _show_day($start, $end, $clean_weeks = true)
    {
        if ($clean_weeks)
        {
            if (strftime('%u', $start) == 1)
            {
                echo "<div class=\"week\" style=\"clear: left\">";
            }
        }

        $event_shown_today = array();

        $day_class = "day";
        if ($end < time())
        {
            $day_class .= " past";
        }
        echo "\n\n";
        echo "<div class=\"{$day_class}\" style=\"width: {$this->column_width}px; height: {$this->cell_height}px;\">";
        echo '<h2>' . strftime("%a", $start) . ' <span class="metadata">' . strftime("%x", $start) . '</span></h2>';

        // Show reservations as list
        foreach ($this->_resources as $guid => $resource)
        {
            $resource['guid'] = $guid;
            $reservations = $this->_get_reservations_between($resource, $start, $end);
            if (count($reservations) > 0)
            {
                $css_class= "reservations";
                if (array_key_exists('css_class', $resource))
                {
                    $css_class .= " {$resource['css_class']}";
                }
                echo "<ul class=\"{$css_class}\">\n";
                foreach ($reservations as $guid => $reservation)
                {
                    if (array_key_exists($guid, $event_shown_today))
                    {
                        // We already showed this
                        if ($reservation['end'] < $end)
                        {
                            $this->_reservations_shown["{$resource['guid']}_{$reservation['guid']}"] = true;
                        }
                        continue;
                    }

                    $start_time = date('H:i', $reservation['start']);
                    $end_time = date('H:i', $reservation['end']);

                    $additional_attributes = "";
                    if (count($this->reservation_div_options) > 0)
                    {
                        foreach ($this->reservation_div_options as $attribute => $value)
                        {
                            // Do replacements
                            $value = str_replace('__GUID__', $reservation['guid'], $value);

                            $additional_attributes .= " {$attribute}=\"{$value}\"";
                        }
                    }

                    echo "<li title=\"{$start_time}-{$end_time}: {$reservation['name']}\">{$start_time}-{$end_time} <span class=\"reservation\"{$additional_attributes}\">{$reservation['name']}</span></li>\n";
                    if ($reservation['end'] < $end)
                    {
                        // This reservation ends here
                        $this->_reservations_shown["{$resource['guid']}_{$reservation['guid']}"] = true;
                        $event_shown_today[$guid] = true;
                    }
                }
                echo '</ul>';
            }
        }
        echo '</div>';

        if ($clean_weeks)
        {
            if (strftime('%u', $start) == 7)
            {
                echo "</div>";
            }
        }
    }

    private function _hcalendar_from_reservations($resource, $reservations, $start, $after_start, $end, $before_end, $resources_shown = false, $slots)
    {
        $previous_start = 0;
        $previous_end = 0;
        $previous_top = 0;

        foreach ($reservations as $reservation)
        {
            // Calculate event width and position
            $event_left = 0;
            $event_width = 5;

            if (!$resources_shown)
            {
                $label_width = 100;
            }
            else
            {
                $label_width = 0;
            }

            if (   $reservation['start'] < $before_end
                && $reservation['end'] > $before_end
                && $reservation['end'] < $after_start)
            {
                // This event starts in the "before" block or earlier and ends in "normal" slot
                $event_left = $label_width;
                $event_width = ((($reservation['end'] - $before_end) / $this->calendar_slot_length) * $this->column_width) + ($this->column_width / 2);
            }
            else if (   $reservation['start'] < $before_end
                && $reservation['end'] > $before_end)
            {
                // This event starts in the "before" block and ends after the "after" slot
                $event_left = $label_width;
                $event_width = ((($after_start - $before_end) / $this->calendar_slot_length) * $this->column_width) + $this->column_width;
            }
            else if (   $reservation['start'] < $after_start
                && $reservation['end'] > $after_start)
            {
                // This event starts in "normal" block and ends in the "after" slot or some next day
                $event_left = $label_width + ($this->column_width / 2) + (($reservation['start'] - $before_end) / $this->calendar_slot_length) * $this->column_width;
                $event_width = ((($after_start - $reservation['start']) / $this->calendar_slot_length) * $this->column_width) + ($this->column_width / 2);
            }
            else if (   $reservation['start'] < $before_end
                    && $reservation['end'] <= $before_end)
            {
                // This event is completely in the "before" block
                $event_left = $label_width;
                $event_width = $this->column_width / 2;
            }
            else if (   $reservation['start'] > $after_start)
            {
                // This event is completely in the "after" block
                $event_left = $label_width + ($this->column_width / 2) + ((count($slots) - 2) * $this->column_width);
                $event_width = $this->column_width / 2;
            }
            else
            {
                // This event starts and ends somewhere in "normal" slots
                $event_left = round($label_width + ($this->column_width / 2) + (($reservation['start'] - $before_end) / $this->calendar_slot_length) * ($this->column_width));
                $event_width = round((($reservation['end'] - $reservation['start']) / $this->calendar_slot_length) * $this->column_width);
            }

            // Calculate top margin for overlap handling
            $event_top = 0;
            if ($reservation['start'] < $previous_end)
            {
                // We're in the overlapping zone
                $event_top = $previous_top + 4;
                $previous_top = $event_top;
            }
            else
            {
                $previous_top = 0;
            }

            if ($reservation['end'] > $previous_end)
            {
                // Expand the overlapping zone from end
                $previous_end = $reservation['end'];
            }
            if ($reservation['start'] < $previous_start)
            {
                // Expand the overlapping zone from start
                $previous_start = $reservation['start'];
            }

            // Reduce paddings
            $event_width = $event_width - 4;
            if ($event_width < 5)
            {
                $event_width = 5;
            }

            // Do more styling based on status
            if (array_key_exists('css_class', $reservation))
            {
                $additional_event_class = $reservation['css_class'];
            }
            else
            {
                $additional_event_class = '';
            }

            if ($reservation['end'] > $end)
            {
                // This event ends after the day
                $additional_event_class .= ' ending_after';
            }
            if ($reservation['start'] < $start)
            {
                // This event starts before the day
                $additional_event_class .= ' starting_before';
            }

            $additional_attributes = "";
            if (count($this->reservation_div_options) > 0)
            {
                foreach ($this->reservation_div_options as $attribute => $value)
                {
                    // Do replacements
                    $value = str_replace('__GUID__', $reservation['guid'], $value);
                    $additional_attributes .= " {$attribute}=\"{$value}\"";
                }
            }

            $start_time = date('H:i', $reservation['start']);
            $end_time = date('H:i', $reservation['end']);

            echo "          <div class=\"vevent{$additional_event_class}\"  title=\"{$start_time}-{$end_time}: {$reservation['name']}\" style=\"width: {$event_width}px; left: {$event_left}px; top: {$event_top}px;\"{$additional_attributes}>\n";
            echo "            <span class=\"time\">\n";
            echo "              <abbr class=\"dtstart\" title=\"" . gmdate('Y-m-d\TH:i:s\Z', $reservation['start']) . "\">{$start_time}</abbr>\n";
            echo "              <abbr class=\"dtend\" title=\"" . gmdate('Y-m-d\TH:i:s\Z', $reservation['end']) . "\">{$end_time}</abbr>\n";
            echo "            </span>\n";

            echo "            <span class=\"summary\">{$reservation['name']}</span>\n";
            echo "          </div>\n";

            if ($reservation['end'] < $end)
            {
                // This reservation ends here
                $this->_reservations_shown["{$resource['guid']}_{$reservation['guid']}"] = true;
            }
        }
    }

    private function _show_day_verbose_horizontal($start, $end)
    {
        // Get timestamps of the day's slots
        $slots = $this->_get_day_slots($start);

        static $resources_shown = false;

        $before_end = null;
        $after_start = null;

        echo "\n\n";
        echo "<table class=\"calendarwidget\">\n";

        if ($this->type != ORG_OPENPSA_CALENDARWIDGET_DAY)
        {
            echo "  <caption>" . strftime("%A %e. %B", $start) . "</caption>\n";
        }
        echo "  <thead>\n";
        echo "    <tr>\n";
        if (!$resources_shown)
        {
            echo "      <th class=\"label\">" . $this->_l10n->get('time') . "</th>\n";
        }

        foreach ($slots as $slot_id => $slot_time)
        {
            if ($slot_time == "before")
            {
                $width = ($this->column_width / 2) - 1;
                echo "      <th class=\"start\" width=\"{$width}\" style=\"width: {$width}px;\">&nbsp;</th>\n";
            }
            else if ($slot_time == "after")
            {
                $width = ($this->column_width / 2) - 1;
                echo "      <th class=\"end\" width=\"{$width}\" style=\"width: {$width}px;\">&nbsp;</th>\n";
            }
            else
            {
                if (   is_null($before_end)
                    || $slot_time < $before_end)
                {
                    // This is the first real slot
                    $before_end = $slot_time;
                }

                if (   is_null($after_start)
                    || $slot_time + $this->calendar_slot_length > $after_start)
                {
                    // This is the first real slot
                    $after_start = $slot_time + $this->calendar_slot_length;
                }

                $slot_label = strftime("%H:%M", $slot_time);
                $css_class = "";
                if (   $this->calendar_slot_length == 3600
                    || $this->calendar_slot_length == 7200)
                {
                    // Slots are even hours, no minutes needed
                    $slot_label = strftime('%H', $slot_time);
                }
                if ($slot_time < time())
                {
                    $css_class .= "past";
                }
                $slot_width = $this->column_width - 1;
                echo "      <th class=\"{$css_class}\" width=\"{$width}\" style=\"width: {$slot_width}px;\">$slot_label</th>\n";
            }
        }
        echo "    </tr>\n";
        echo "  </thead>\n";

        echo "  <tbody>\n";
        // Show reservations as list
        foreach ($this->_resources as $guid => $resource)
        {
            $resource['guid'] = $guid;

            $css_class = '';
            if (array_key_exists('css_class', $resource))
            {
                $css_class = " class=\"{$resource['css_class']}\"";
            }

            echo "    <tr{$css_class}>\n";

            if (!$resources_shown)
            {
                echo "      <th>\n";
                echo "        <div class=\"eventlist\">\n";
                echo "          <span class=\"assignee\">{$resource['name']}</span>\n";
                $reservations = $this->_get_reservations_between($resource, $start, $end);
                if (count($reservations) > 0)
                {
                    $this->_hcalendar_from_reservations($resource, $reservations, $start, $after_start, $end, $before_end, $resources_shown, $slots);
                }
                echo "          </div>\n";
                echo "      </th>\n";
            }

            foreach ($slots as $slot_id => $slot_time)
            {
                $additional_free_attributes = "";
                if (count($this->free_div_options) > 0)
                {
                    foreach ($this->free_div_options as $attribute => $value)
                    {
                        // Do replacements
                        $start_time = $slot_time;
                        if ($slot_time == "before")
                        {
                            $start_time = $before_end - $this->calendar_slot_length;
                        }
                        else if ($slot_time == "after")
                        {
                            $start_time = $after_start;
                        }
                        $value = str_replace('__START__', $start_time, $value);
                        $value = str_replace('__RESOURCE__', $guid, $value);

                        $additional_free_attributes .= " {$attribute}=\"{$value}\"";
                    }
                }

                if ($slot_time == "before")
                {
                    $width = ($this->column_width / 2) - 1;
                    if ($resources_shown)
                    {
                        echo "      <td class=\"start\" style=\"width: {$width}px;\"{$additional_free_attributes}>\n";
                        echo "        <div class=\"eventlist\">\n";
                        echo "          <span>&nbsp;</span>\n";
                        $reservations = $this->_get_reservations_between($resource, $start, $end);
                        if (count($reservations) > 0)
                        {
                            $this->_hcalendar_from_reservations($resource, $reservations, $start, $after_start, $end, $before_end, $resources_shown, $slots);
                        }
                        echo "          </div>\n";
                        echo "        </td>\n";
                    }
                    else
                    {
                        echo "      <td class=\"start\" style=\"width: {$width}px;\"{$additional_free_attributes}>&nbsp;</td>\n";
                    }
                }
                else if ($slot_time == "after")
                {
                    $width = ($this->column_width / 2) - 1;

                    echo "      <td class=\"end\" style=\"width: {$width}px;\"{$additional_free_attributes}>&nbsp;</td>\n";
                }
                else
                {
                    $css_class = '';
                    if ($slot_time < time())
                    {
                        $css_class = " class=\"past\"";
                    }
                    $slot_width = $this->column_width - 1;
                    echo "      <td{$css_class} width=\"{$slot_width}\" style=\"width: {$slot_width}px;\"{$additional_free_attributes}>&nbsp;</td>\n";
                }
            }

            echo "    </tr>\n";
        }
        echo "  </tbody>\n";
        echo "</table>\n";
    }
}
?>