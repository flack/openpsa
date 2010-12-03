<?php
/**
 * @package org.openpsa.calendarwidget
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * ActiveCalendar-like month display widget
 *
 * # Usage
 *
 * 1. Initialize the calendar
 * 2. Set the calendar time
 * 3. Add the events
 * 4. Draw the calendar
 *
 * Example:
 *
 * <code>
 * // Initialize the calendar
 * $calendar = new org_openpsa_calendarwidget_month();
 *
 * // Set the date. Skip any of these if you are showing the current year, month or day
 * $calendar->set_year(2007);
 * $calendar->set_month(12);
 * $calendar->set_day(3);
 *
 * // Add the events
 * $event = new midgard_event(123);
 * $calendar->add_event($event);
 *
 * // Show the calendar
 * $calendar->draw();
 * </code>
 *
 *
 * ## Properties
 *
 * There are numerous properties that can be used to tweak the calendar
 *
 * ### Navigation
 *
 * @property string $previous  For displaying the link to the previous month
 * @property string $next      For displaying the link to the next month
 *
 * ### Accessibility
 *
 * @property boolean $week_numbers         Switch for determining if the weeknumbers should be shown
 * @property string $week_text             Name for the table header for week numbers column, if set to empty value localized from 'week'
 * @property boolean $link_to_pages        Switch for the links. This will determine whether the links should open in a remote page.
 * @property string $link_to_pages_uri     Location of the remote page
 * @property string $suffix                Extra query suffix
 * @property boolean $month_navigation     Switch to determine whether month navigation is enabled.
 * @property boolean $details_box          Switch to determine if details box should appear
 * @property boolean $use_javascript       Switch to define if JavaScript should be used to display the details box
 * @property boolean $days_outside_month   Switch to determine if the days outside the month scope but inside the viewed weeks should be shown.
 * @property boolean $short_day_names      Switch to determine whether we should use short or long day names for the table headers
 * @property boolean $path_mode            Switch to determine whether month navigation should be handled with changing the path instead of GET parameters
 * @property string $path                  Root URL for the calendar widget
 *
 * ## Setting the date
 *
 * Set the date with corresponding methods
 *
 * @method void set_year($year, [$recalculate]);
 * @method void set_month($month, [$recalculate]);
 * @method void set_day($day, [$recalculate]);
 *
 * ## Adding events
 *
 * Add midgard_events or midcom_db_events with them with
 *
 *     <?php
 *     $calendar->add_event($event);
 *     ?>
 *
 * @package org.openpsa.calendarwidget
 *
 */
class org_openpsa_calendarwidget_month
{
    /**
     * Currently viewed year
     *
     * @access protected
     * @var integer
     */
    private $_year = null;

    /**
     * Currently viewed month
     *
     * @access protected
     * @var integer
     */
    private $_month = null;

    /**
     * Currently viewed day
     *
     * @access protected
     * @var integer
     */
    private $_day = null;

    /**
     * Link text for selecting the previous month
     *
     * @var string
     */
    public $previous = '&lt;&lt;';

    /**
     * Link text for selecting the next month
     *
     * @var string
     */
    public $next = '&gt;&gt;';

    /**
     * An array containing all the events.
     */
    private $_events = array ();

    /**
     * Switch to tell the methods whether it is supposed to draw the week number column.
     *
     * @var boolean
     */
    public $week_numbers = true;

    /**
     * Name for the table header for week numbers column, if set to empty value localized from 'week'
     *
     * @var string
     */
    public $week_text = '';

    /**
     * Switch for the links. This will determine whether the links should open in a remote page.
     *
     * @var boolean
     */
    public $link_to_pages = false;

    /**
     * Location of the remote page
     *
     * @var string
     */
    public $link_to_pages_uri = '';

    /**
     * Extra query string
     *
     * @var string
     */
    public $suffix = '';

    /**
     * Switch to determine whether month navigation is enabled.
     *
     * @var boolean
     */
    public $month_navigation = true;

    /**
     * Switch to determine if details box should appear
     *
     * @var boolean
     */
    public $details_box = true;

    /**
     * Switch to define if JavaScript should be used to display the details box
     *
     * @var boolean
     */
    public $use_javascript = false;

    /**
     * Switch to determine if the days outside the month scope but inside the viewed weeks
     * should be shown.
     *
     * @var boolean
     */
    public $days_outside_month = false;

    /**
     * Switch to determine whether we should use short or long day names for the table headers
     *
     * @var boolean
     */
    public $short_day_names = true;

    /**
     * Switch to determine whether month navigation should be handled with changing the
     * path instead of GET parameters
     *
     * @var boolean
     */
    public $path_mode = false;

    /**
     * Root URL for the calendar widget
     *
     * @var string
     */
    public $path = '';

    /**
     * Timestamp for the beginning of the calendar view
     *
     * @var integer
     */
    private $_calendar_start = 0;

    /**
     * Timestamp for the end of the calendar view
     *
     * @var integer
     */
    private $_calendar_end = 0;

    /**
     * Timestamp for the beginning of the currently viewed month
     *
     * @var integer
     */
    private $_month_start = 0;

    /**
     * Timestamp for the end of the currently viewed month
     *
     * @var integer
     */
    private $_month_end = 0;

    /**
     * Timestamp for the first Monday in the calendar, regardless of it belonging to the scope or not
     *
     * @var integer
     */
    private $_first_monday = 0;

    /**
     * Timestamp for the last Sunday in the calendar, regardless of it belonging to the scope or not
     *
     * @var integer
     */
    private $_last_sunday = 0;

    /**
     * Timestamp for traversing the month.
     *
     * @var integer
     */
    private $_parser = 0;

    /**
     * CSS class for the calendar itself
     *
     * @var string
     */
    public $css_calendar = 'org_openpsa_calendarwidget_month';

    /**
     * CSS class for the month navigation
     *
     * @var string
     */
    public $css_month_navi = 'month-navi';

    /**
     * CSS class for the next month in month navigation
     *
     * @var string
     */
    public $css_next = 'next';

    /**
     * CSS class for the previous month in month navigation
     *
     * @var string
     */
    public $css_previous = 'previous';

    /**
     * CSS class for the month name
     *
     * @var string
     */
    public $css_month_class = 'month-name';

    /**
     * CSS class for year visible with month name in table headers
     *
     * @var string
     */
    public $css_year = 'year';

    /**
     * CSS class for month name in table headers
     *
     * @var string
     */
    public $css_month_name = 'month-name';

    /**
     * CSS class for the details box
     *
     * @var string
     */
    public $css_details_box = 'details';

    /**
     * CSS class for the details list
     *
     * @var string
     */
    public $css_details_box_list = 'details-list';

    /**
     * CSS class for Monday
     *
     * @var string
     */
    public $css_monday = '';

    /**
     * CSS class for Tuesday
     *
     * @var string
     */
    public $css_tuesday = '';

    /**
     * CSS class for Wednesday
     *
     * @var string
     */
    public $css_wednesday = '';

    /**
     * CSS class for Thursday
     *
     * @var string
     */
    public $css_thurday = '';

    /**
     * CSS class for Friday
     *
     * @var string
     */
    public $css_friday = '';

    /**
     * CSS class for Saturday
     *
     * @var string
     */
    public $css_saturday = 'weekend';

    /**
     * CSS class for Sunday
     *
     * @var string
     */
    public $css_sunday = 'weekend sunday';

    /**
     * CSS class for week numbers
     *
     * @var string
     */
    public $css_week = 'week';

    /**
     * CSS class for today
     *
     * @var string
     */
    public $css_today = 'today';

    /**
     * CSS class for an event
     *
     * @var string
     */
    public $css_event = 'event';

    /**
     * CSS class for events list
     *
     * @var string
     */
    public $css_event_list = 'event-list';

    /**
     * CSS class for date
     *
     * @var string
     */
    public $css_event_date = 'event-date';

    /**
     * CSS class for event title
     *
     * @var string
     */
    public $css_event_title = 'event-title';

    /**
     * CSS class for the checker to close the details box
     *
     * @var string
     */
    public $css_close_checker = 'close';

    /**
     * CSS class for days outside the month scope
     *
     * @var string
     */
    public $css_outside_month = 'outside-month';

    /**
     * Additional link-text for showing detailed events
     *
     * @var string
     */
    public $additional_name_for_links = '';

    /**
     * Last year to show (leave false to allow going forward in time infinitely)
     *
     * @var string
     */
    public $last_year = false;

    /**
     * First year to show (leave false to allow going back in time infinitely)
     *
     * @var string
     */
    public $first_year = false;

    /**
     * Simple constructor method. Initializes
     */
    public function __construct()
    {
        if (empty($this->week_text))
        {
            $this->week_text = $_MIDCOM->i18n->get_string('week', 'org.openpsa.calendarwidget');
        }

        // Set the environmental variables
        $this->_read_environment_variables();
    }

    /**
     * Returns the timestamp of the beginning of the calendar view
     *
     * @return integer Describing the timestamp of the calendar view start
     */
    public function get_start()
    {
        return $this->_calendar_start;
    }

    /**
     * Returns the timestamp of the end of the calendar view
     *
     * @return integer Describing the timestamp of the calendar view end
     */
    public function get_end()
    {
        return $this->_calendar_end;
    }

    /**
     * Draws links to the right location
     */
    private function _draw_link($text, $title = '', $link = false, $class = '')
    {
        if (!$link)
        {
            return $text;
        }

        if ($class)
        {
            $class = ' class="' . $class . '"';
        }

        return "<a{$class} href=\"{$link}\" title=\"{$title}\">{$text}</a>";
    }

    /**
     * Set the viewed year
     */
    public function set_year ($year = null, $recalculate = true)
    {
        if (is_null($year))
        {
            if (array_key_exists('year', $_GET))
            {
                $this->_year = (int) $_GET['year'];
            }
            else if (!$year)
            {
                $this->_year = (int) date('Y');
            }

            return;
        }

        $this->_year = (int) $year;

        if ($recalculate)
        {
            $this->_calculate_timestamps();
        }
    }

    /**
     * Set the viewed month
     */
    public function set_month ($month = null, $recalculate = true)
    {
        if (is_null($month))
        {
            if (array_key_exists('month', $_GET))
            {
                $this->_month = (int) $_GET['month'];
            }
            else if (!$month)
            {
                $this->_month = (int) date('m');
            }

            return;
        }

        $this->_month = (int) $month;

        if ($recalculate)
        {
            $this->_calculate_timestamps();
        }
    }

    /**
     * Set the viewed day
     */
    public function set_day ($day = null, $recalculate = true)
    {
        if (is_null($day))
        {
            if (array_key_exists('day', $_GET))
            {
                $this->_day = (int) $_GET['day'];
            }
            else if (!$day)
            {
                $this->_day = (int) date('d');
            }

            return;
        }

        $this->_day = (int) $day;

        if ($recalculate)
        {
            $this->_calculate_timestamps();
        }
    }

    /**
     * Calculates the timestamps.
     */
    private function _calculate_timestamps()
    {
        // Create the Unix timestamp for the beginning of the selected month
        $this->_month_start = mktime(0, 0, 0, $this->_month, 1, $this->_year);

        // Create the Unix timestamp for the end of the selected month.
        // date('t') gives the amount of days in the requested month
        $this->_month_end = mktime(23, 59, 59, $this->_month, (int) date('t', $this->_month_start), $this->_year);

        // Create the Unix timestamps for the beginning and the end of the full calendar
        // weeks
        if ((int) strftime('%u', $this->_month_start - 1) !== 7)
        {
            $this->_first_monday = $this->_month_start - (strftime('%u', $this->_month_start - 1) * 24 * 3600);
        }
        else
        {
            $this->_first_monday = $this->_month_start;
        }

        if ((int) strftime('%u', $this->_month_start - 1) !== 7)
        {
            $this->_last_sunday = $this->_month_end + ((6 - strftime('%u', $this->_month_end)) * 24 * 3600);
        }
        else
        {
            $this->_last_sunday = $this->_month_end;
        }

        // Create the Unix timestamp for the calendar start
        if ($this->days_outside_month)
        {
            $this->_calendar_start = $this->_first_monday;
        }
        else
        {
            $this->_calendar_start = $this->_month_start;
        }

        // Create the Unix timestamp for the calendar end
        if ($this->days_outside_month)
        {
            $this->_calendar_end = $this->_last_sunday;
        }
        else
        {
            $this->_calendar_end = $this->_month_end;
        }

        // Define the previous month and year for the navigation
        $this->_previous_month = $this->_month - 1;
        $this->_previous_year = $this->_year;

        if ($this->_previous_month === 0)
        {
            $this->_previous_month = 12;
            $this->_previous_year = $this->_year - 1;
        }

        // Define the next month and year for the navigation
        $this->_next_month = $this->_month + 1;
        $this->_next_year = $this->_year;

        if ($this->_next_month === 13)
        {
            $this->_next_month = 1;
            $this->_next_year = $this->_year + 1;
        }
    }

    /**
     * Set the environment variables
     */
    private function _read_environment_variables ()
    {
        // Create the Unix timestamp for today
        $this->today = time();

        $this->set_year(null, false);
        $this->set_month(null, false);
        $this->set_day(null, false);
        $this->_calculate_timestamps();
    }

    /**
     * Draw the requested objects
     */
    public function show()
    {
        // Draw a month
        $this->_draw_month();
    }

    /**
     * Add JavaScript headers
     */
    public function javascript_headers()
    {
        // TODO: Load the JavaScript headers
    }

    /**
     * Draws the month view
     */
    private function _draw_month($month = FALSE, $year = FALSE)
    {
        $this->_draw_month_header();
        $this->_draw_month_body();
        $this->_draw_month_footer();
    }

    /**
     * Draw the month headers
     */
    private function _draw_month_header()
    {
        echo "<table class=\"{$this->css_calendar}\">\n";
        echo "    <thead>\n";
        echo "        <tr class=\"nav-row\">\n";
        echo "            <th class=\"{$this->css_month_navi} {$this->css_previous}\">\n";

        $suffix = '';

        // Draw the month parser to go one month backwards
        if ($this->month_navigation)
        {
            // If path mode is on use URL path to navigate. Otherwise use GET parameters.
            if ($this->path_mode)
            {
                if ($this->suffix)
                {
                    $suffix = "?{$this->suffix}";
                }
                if (   $this->first_year
                    && $this->_previous_year < $this->first_year)
                {
                    echo "                &nbsp;";
                }
                else
                {
                    echo "                " . $this->_draw_link($this->previous, strftime('%B %Y', mktime(0, 0, 0, $this->_previous_month, $this->_day, $this->_previous_year)), "{$this->path}{$this->_previous_year}/{$this->_previous_month}/{$suffix}") ."\n";
                }
            }
            else
            {
                if ($this->suffix)
                {
                    $suffix = "&{$this->suffix}";
                }

                if (   $this->first_year
                    && $this->_previous_year < $this->first_year)
                {
                    echo "                &nbsp;";
                }
                else
                {
                    echo "                " . $this->_draw_link($this->previous, strftime('%B %Y', mktime(0, 0, 0, $this->_previous_month, $this->_day, $this->_previous_year)), "?month={$this->_previous_month}&year={$this->_previous_year}&{$suffix}") ."\n";
                }
            }
        }

        echo "            </th>\n";

        // Colspan for month name depends on whether we show week numbers
        if ($this->week_numbers)
        {
            $colspan = 6;
        }
        else
        {
            $colspan = 5;
        }

        echo "            <th class=\"{$this->css_month_class}\" colspan=\"{$colspan}\">\n";
        echo "                " . $this->_draw_link(strftime("<span class=\"{$this->css_month_name}\">%B</span> <span class=\"{$this->css_year}\">%Y</span>", $this->_month_start), strftime('%B', $this->_month_start), $this->link_to_pages_uri) ."\n";
        echo "            </th>\n";

        echo "            <th class=\"{$this->css_month_navi} {$this->css_next}\"\">\n";

        // Draw the month parser to go one month forwards
        if ($this->month_navigation)
        {
            // If path mode is on use URL path to navigate. Otherwise use GET parameters.
            if ($this->path_mode)
            {
                if ($this->suffix)
                {
                    $suffix = "?{$this->suffix}";
                }
                if (   $this->last_year
                    && $this->_next_year > $this->last_year)
                {
                    echo "                &nbsp;";
                }
                else
                {
                    echo "                " . $this->_draw_link($this->next, strftime('%B %Y', mktime(0, 0, 0, $this->_next_month, $this->_day, $this->_next_year)), "{$this->path}{$this->_next_year}/{$this->_next_month}/{$suffix}") ."\n";
                }
            }
            else
            {
                if ($this->suffix)
                {
                    $suffix = "&{$this->suffix}";
                }

                if (   $this->last_year
                    && $this->_next_year > $this->last_year)
                {
                    echo "                &nbsp;";
                }
                else
                {
                    echo "                " . $this->_draw_link($this->next, strftime('%B %Y', mktime(0, 0, 0, $this->_next_month, $this->_day, $this->_next_year)), "?month={$this->_next_month}&year={$this->_next_year}{$suffix}") ."\n";
                }
            }
        }

        echo "            </th>\n";
        echo "        </tr>\n";

        // Draw day names
        echo "        <tr class=\"weekday-row\">\n";

        if ($this->week_numbers)
        {
            echo "            <th class=\"{$this->css_week} label\">\n";
            echo "                {$this->week_text}\n";
            echo "            </th>\n";
        }

        // Draw the day names to the calendar header
        for ($i = 0; $i <= 6; $i++)
        {
            $header_class = 'weekdayname ';
            switch ($i)
            {
                case '0':
                    $header_class .= $this->css_monday;
                    break;
                case '1':
                    $header_class .= $this->css_tuesday;
                    break;
                case '2':
                    $header_class .= $this->css_wednesday;
                    break;
                case '3':
                    $header_class .= $this->css_thurday;
                    break;
                case '4':
                    $header_class .= $this->css_friday;
                    break;
                case '5':
                    $header_class .= $this->css_saturday;
                    break;
                case '6':
                    $header_class .= $this->css_sunday;
                    break;
            }
            $header_class = trim($header_class);
            echo "            <th class=\"{$header_class}\">\n";

            // Print day names depending on the request: short or long forms
            if ($this->short_day_names)
            {
                echo "                " . strftime('%a', $this->_first_monday + $i * 24 * 3600) . "\n";
            }
            else
            {
                echo "                " . strftime('%A', $this->_first_monday + $i * 24 * 3600) . "\n";
            }
            echo "            </th>\n";
        }

        echo "        </tr>\n";

        // End the header section
        echo "    </thead>\n";
        echo "    <tbody>\n";
    }

    /**
     * Ends the month table
     */
    private function _draw_month_footer()
    {
        echo "    </tbody>\n";
        echo "</table>\n";
    }

    /**
     * Draws the month body, starting from the first Monday and ending to the last Sunday
     * of the calendar view.
     */
    private function _draw_month_body()
    {
        $this->_parser = $this->_first_monday;

        while ($this->_parser < $this->_last_sunday)
        {
            $this->_draw_week();
        }
    }

    /**
     * Draws one week at a time
     */
    private function _draw_week()
    {
        echo "        <tr>\n";

        if ($this->week_numbers)
        {
            echo "            <td class=\"{$this->css_week}\">\n";
            echo "                " . $this->_draw_link(strftime('%V', $this->_parser), $this->link_to_pages_uri) . "\n";
            echo "            </td>\n";
        }

        for ($i = 1; $i <= 7; $i++)
        {
            // Draw the day cell
            $this->_draw_day($this->_parser);
            $next_day = mktime(0, 0, 0, date('m',$this->_parser), date('d',$this->_parser) + 1, date('Y',$this->_parser));
            // Add one full day to parser timestamp
            $this->_parser = $next_day;
        }

        echo "        </tr>\n";
    }

    /**
     * Draws one day
     */
    private function _draw_day($timestamp)
    {
        // Set the table cell classes
        echo "            <td" . $this->_day_class($timestamp) . $this->_javascript_functions($timestamp).">\n";

        if (   $this->days_outside_month
            || (   $this->_parser >= $this->_month_start
                && $this->_parser <= $this->_month_end))
        {
            if (!array_key_exists(date('Y-m-d', $timestamp), $this->_events))
            {
                echo "                " . strftime('%d', $timestamp) ."\n";
                echo "            </td>\n";
                return;
            }

            echo "                " . strftime('%d', $timestamp) ."\n";


            if ($this->details_box)
            {
                $this->_draw_details_box($this->_events[date('Y-m-d', $timestamp)], $timestamp);
            }
        }
        else
        {
            echo "                <span class=\"outside-month-day\">" . strftime('%d', $timestamp) . "</span>\n";
        }
        echo "            </td>\n";
    }

    /**
     * Draw javascript functions to table cell
     */
    private function _javascript_functions($timestamp)
    {
        if (!$this->details_box)
        {
            return;
        }

        if (!array_key_exists(date('Y-m-d', $timestamp), $this->_events))
        {
            return;
        }

        if ($this->use_javascript)
        {
            return " onmouseover=\"org_openpsa_calendarwidget_month_show_box('org_openpsa_calendarwidget_month_{$timestamp}_{$this->additional_name_for_links}');\" onmouseout=\"org_openpsa_calendarwidget_month_hide_box('org_openpsa_calendarwidget_month_{$timestamp}_{$this->additional_name_for_links}');\"";
        }
    }

    /**
     * Draws one single event
     */
    private function _draw_event($event)
    {
        if (!is_object($event))
        {
            return false;
        }

        if (array_key_exists('class', $event))
        {
            $class = " class=\"{$event->class}\"";
        }
        else
        {
            $class = '';
        }

        if (date('Y-m-d', $event->start) === date('Y-m-d', $this->_parser))
        {
            $start_time = strftime('%H:%M - ', $event->start);
        }
        else
        {
            $start_time = strftime('%x %H:%M - ', $event->start);
        }

        if (date('Y-m-d', $event->start) === date('Y-m-d', $event->end))
        {
            $end_time = strftime('%H:%M', $event->end);
        }
        else
        {
            $end_time = strftime('%x %H:%M', $event->end);
        }

        $dtstart = strftime('%Y-%m-%dT%H:%M:%S%z', $event->start);
        $dtend = strftime('%Y-%m-%dT%H:%M:%S%z', $event->end);

        //     function _draw_link($text, $title = '', $link = false, $class = '')

        echo "<li{$class}>\n";
        echo "    <div class=\"vevent\">\n";
        if (   array_key_exists('link', $event)
            && $event->link !== '')
        {
            echo "        <h3 class=\"summary\">" . $this->_draw_link($event->title, $event->title, $event->link, 'url') . "</h3>\n";
        }
        else
        {
            echo "        <h3 class=\"summary\">{$event->title}</h3>\n";
        }

        echo "        <abbr class=\"dtstart\" title=\"{$dtstart}\">{$start_time}</abbr>\n";
        echo "        <abbr class=\"dtend\" title=\"{$dtend}\">{$end_time}</abbr>\n";

        if (   array_key_exists('location', $event)
            && $event->location !== '')
        {
            echo "        <p>\n";
            echo "            <span class=\"location\">{$event->location}</span>\n";
            echo "        </p>\n";
        }

        echo "        <span class=\"{$event->guid}\" style=\"display: none;\">{$event->guid}</span>\n";
        echo "    </div>\n";
        echo "</li>\n";
    }

    /**
     * Draws the details box
     */
    private function _draw_details_box($events, $timestamp)
    {
        // Bulletproofing the variable
        if (!is_array($events))
        {
            return false;
        }

        // End if no events were found
        if (count($events) === 0)
        {
            return true;
        }

        $class = $this->css_details_box;

        // Hide the details box if JavaScript has been set to be on
        if ($this->use_javascript)
        {
            $style = ' style="display: none;"';
            $ticker = "<span class=\"{$this->css_close_checker}\" onclick=\"hide_box('org_openpsa_calendarwidget_month_{$timestamp}_{$this->additional_name_for_links}');\" title=\"Close window\">X</span>";
            $class .= ' hover';
        }
        else
        {
            $style = '';
            $ticker = '';
        }


        // Initialize details box
        echo "                <div id=\"org_openpsa_calendarwidget_month_{$timestamp}_{$this->additional_name_for_links}\"{$style} class=\"{$class}\">\n";
        echo "                    <ul class=\"{$this->css_event_list}\">\n";

        // Loop through events
        foreach ($events as $event)
        {
            $this->_draw_event($event);
        }

        // End the details box
        echo "                    </ul>\n";
        echo "                </div>\n";
    }

    /**
     * Get the class names for the currently checked day
     */
    private function _day_class($timestamp)
    {
        $class = '';

        // Class for the weekday
        switch (strftime('%u', $timestamp))
        {
            case '1':
                $class = $this->css_monday;
                break;

            case '2':
                $class = $this->css_tuesday;
                break;

            case '3':
                $class = $this->css_wednesday;
                break;

            case '4':
                $class = $this->css_thurday;
                break;

            case '5':
                $class = $this->css_friday;
                break;

            case '6':
                $class = $this->css_saturday;
                break;

            case '7':
                $class = $this->css_sunday;
                break;
        }

        // Class for 'today'
        if (strftime('%x', $timestamp) === strftime('%x'))
        {
            $class .= " {$this->css_today}";
        }

        // Check if an event is found
        if (array_key_exists(date('Y-m-d', $timestamp), $this->_events))
        {
            // Event found, check if there is need to display the class
            if (   $this->days_outside_month
                || (   $timestamp >= $this->_month_start
                    && $timestamp <= $this->_month_end))
            {
                $class .= " {$this->css_event}";

                // Get the event specific classes by looping through all the events
                foreach ($this->_events[date('Y-m-d', $timestamp)] as $event)
                {
                    // Continue to next if no class was set
                    if (!array_key_exists('class', $event))
                    {
                        continue;
                    }

                    // Continue to next if class is set but exists already
                    if (strstr($event->class, $class))
                    {
                        continue;
                    }

                    $class .= " {$event->class}";
                }
            }
        }

        if (   $timestamp < $this->_month_start
            || $timestamp > $this->_month_end)
        {
            $class .= " {$this->css_outside_month}";
        }

        // Return an empty string if no classes have been set
        if (trim($class) === '')
        {
            return '';
        }

        // Return class trimmed
        return ' class="' . trim($class) . '"';
    }

    /**
     * Set the events to be displayed on calendar
     */
    public function add_event($object)
    {
        // Bulletproofing
        if (   !is_object($object)
            || !$object->start)
        {
            return FALSE;
        }

        $day = mktime(0, 0, 0, (int) date('m', $object->start), (int) date('d', $object->start), (int) date('Y', $object->start));

        // Do at least one event
        do
        {
            if (!array_key_exists(date('Y-m-d', $object->start), $this->_events))
            {
                $this->_events[date('Y-m-d', $object->start)] = array ();
            }

            // Add the object
            $this->_events[date('Y-m-d', $day)][] = $object;

            // Add one full day
            $day += 24*3600;
        }
        while ($day < $object->end);
    }
}
?>