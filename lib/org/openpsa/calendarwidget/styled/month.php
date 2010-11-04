<?php
/**
 * @package org.openpsa.calendarwidget
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: month.php 23015 2009-07-28 08:50:55Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * ActiveCalendar-like month display widget
 * 
 * # Usage
 * 
 * @see org_openpsa_calendarwidget_month
 * 
 * There are two issues one might need to know:
 * 
 * 1. Prepending the style
 * 2. Possibility to use midcom.helper.datamanager2 for the events
 * 
 * It is also possible to use midgard_events or midcom_db_events by adding
 * them with
 * 
 *     <?php
 *     $calendar->add_event($event);
 *     ?>
 * 
 * ## Style elements:
 * 
 * Remember to initialize this before MidCOM is outputting the content or to 
 * prepend the style directory. Otherwise this will not output anything.
 * 
 * Prepending a component styledir:
 * 
 *     <?php
 *     $_MIDCOM->style->prepend_component_styledir('org.openpsa.calendarwidget');
 *     ?>
 * 
 * ## midcom.helper.datamanager2
 * 
 * Set the midcom_helper_datamanager2_schema to the variable $schemadb. Example:
 * 
 *     <?php
 *     $schemadb = midcom_helper_datamanager2_schema::load_database('/path/to/schemadb');
 *     $calendar = new org_openpsa_calendarwidget_styled_month();
 *     $calendar->schemadb = $schemadb;
 *     ?>
 *
 * @package org.openpsa.calendarwidget
 */
class org_openpsa_calendarwidget_styled_month extends org_openpsa_calendarwidget_month
{
    /**
     * Currently viewed year
     *
     * @access protected
     * @var integer
     */
    var $_year = null;

    /**
     * Currently viewed month
     *
     * @access protected
     * @var integer
     */
    var $_month = null;

    /**
     * Currently viewed day
     *
     * @access protected
     * @var integer
     */
    var $_day = null;

    /**
     * An array containing all the events.
     *
     * @access private
     */
    var $_events = array ();

    /**
     * Timestamp for the beginning of the calendar view
     *
     * @access private
     * @var integer
     */
    var $_calendar_start = 0;

    /**
     * Timestamp for the end of the calendar view
     *
     * @access private
     * @var integer
     */
    var $_calendar_end = 0;

    /**
     * Timestamp for the beginning of the currently viewed month
     *
     * @access private
     * @var integer
     */
    var $_month_start = 0;

    /**
     * Timestamp for the end of the currently viewed month
     *
     * @access private
     * @var integer
     */
    var $_month_end = 0;

    /**
     * Timestamp for the first Monday in the calendar, regardless of it belonging to the scope or not
     *
     * @access private
     * @var integer
     */
    var $_first_monday = 0;

    /**
     * Timestamp for the last Sunday in the calendar, regardless of it belonging to the scope or not
     *
     * @access private
     * @var integer
     */
    var $_last_sunday = 0;

    /**
     * Timestamp for traversing the month.
     *
     * @access private
     * @var integer
     */
    var $_parser = 0;
    
    /**
     * Request data for storing output variables
     * 
     * @access public
     * @var Array
     */
    var $_request_data;
    
    /**
     * DM2 schema for the events
     * 
     * @access public
     * @var midcom_helper_datamanager2_schema
     */
    var $schemadb = null;
    
    /**
     * DM2 instance for the events
     * 
     * @access public
     * @var midcom_helper_datamanager2
     */
    var $_datamanager = null;

    /**
     * Simple constructor method. Initializes
     */
    function __construct()
    {
        parent::__construct();
        
        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('org.openpsa.calendarwidget');
    }

    /**
     * Draw the requested objects
     *
     * @access public
     */
    function show()
    {
        // Initialize the DM2 instance if possible
        $this->_load_datamanager();
        
        $this->_request_data['first_year'] = $this->first_year;
        $this->_request_data['last_year'] = $this->last_year;
        
        // Draw a month
        $this->_draw_month();
    }
    
    /**
     * Load the DM2 instance for the events
     * 
     * @access private
     */
    function _load_datamanager()
    {
        if (!$this->schemadb)
        {
            return false;
        }
        
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->schemadb);
        
        if (! $this->_datamanager)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to create a DM2 instance.');
            // This will exit.
        }
        $this->_request_data['datamanager'] =& $this->_datamanager;
    }

    /**
     * Draws the month view
     *
     * @access private
     */
    function _draw_month($month = FALSE, $year = FALSE)
    {
        $this->_request_data['calendar_widget'] =& $this;
        $this->_draw_month_header();
        $this->_draw_month_body();
        $this->_draw_month_footer();
    }

    /**
     * Draw the month headers
     *
     * @access private
     */
    function _draw_month_header()
    {
        // Set the variables
        $this->_request_data['year'] = $this->_year;
        $this->_request_data['month'] = $this->_month;
        $this->_request_data['day'] = $this->_day;
        $this->_request_data['month_start'] = $this->_month_start;
        
        $this->_request_data['previous_year'] = $this->_previous_year;
        $this->_request_data['previous_month'] = $this->_previous_month;
        
        $this->_request_data['next_year'] = $this->_next_year;
        $this->_request_data['next_month'] = $this->_next_month;
        
        midcom_show_style('calendarwidget-month-header');
    }

    /**
     * Ends the month table
     *
     * @access private
     */
    function _draw_month_footer()
    {
        midcom_show_style('calendarwidget-month-footer');
    }

    /**
     * Draws the month body, starting from the first Monday and ending to the last Sunday
     * of the calendar view.
     *
     * @access private
     */
    function _draw_month_body()
    {
        $this->_parser = $this->_first_monday;

        while ($this->_parser < $this->_last_sunday)
        {
            $this->_draw_week();
        }
    }

    /**
     * Draws one week at a time
     *
     * @access private
     */
    function _draw_week()
    {
        $this->_request_data['week_start'] = $this->_parser;
        midcom_show_style('calendarwidget-week-start');
        
        for ($i = 1; $i <= 7; $i++)
        {
            // Draw the day cell
            $this->_draw_day($this->_parser);
            $next_day = mktime(0, 0, 0, date('m',$this->_parser), date('d',$this->_parser) + 1, date('Y',$this->_parser));
            // Add one full day to parser timestamp
            $this->_parser = $next_day;
        }

        midcom_show_style('calendarwidget-week-end');
    }

    /**
     * Draws one day
     *
     * @access private
     */
    function _draw_day($timestamp)
    {
        // Set the daily variables
        $this->_request_data['day'] = $timestamp;
        $this->_request_data['outside_month'] = false;
        $this->_request_data['events'] = array();
        $this->_request_data['today'] = false;
        
        // Show the events of the day
        if (array_key_exists(date('Y-m-d', $timestamp), $this->_events))
        {
            $this->_request_data['events'] = $this->_events[date('Y-m-d', $timestamp)];
        }
        
        // Check if it is today
        if (strftime('%x') === strftime('%x', $timestamp))
        {
            $this->_request_data['today'] = true;
        }
        
        if (   $timestamp <= $this->_month_start
            || $timestamp > $this->_month_end)
        {
            $this->_request_data['outside_month'] = true;
        }
        
        midcom_show_style('calendarwidget-day-header');
        
        // Show the events of the day
        if (array_key_exists(date('Y-m-d', $timestamp), $this->_events))
        {
            $this->_show_events($this->_events[date('Y-m-d', $timestamp)], $timestamp);
        }
        
        midcom_show_style('calendarwidget-day-footer');
    }

    /**
     * Draws the details box
     *
     * @access private
     */
    function _show_events($events, $timestamp)
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
        
        midcom_show_style('calendarwidget-day-events-header');
        
        foreach ($events as $event)
        {
            $this->_request_data['event'] = $event;
            
            if ($this->_datamanager)
            {
                $this->_datamanager->autoset_storage($event);
                $this->_request_data['view_event'] = $this->_datamanager->get_content_html();
            }
            else
            {
                $this->_request_data['view_event'] = array
                (
                    'name' => $event->name,
                    'title' => $event->title,
                    'description' => $event->description,
                    'location' => $event->location,
                    'start' => $event->start,
                    'end' => $event->end,
                );
            }
            
            midcom_show_style('calendarwidget-day-event');
        }
        
        midcom_show_style('calendarwidget-day-events-footer');
    }
}
?>