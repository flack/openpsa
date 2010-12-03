<?php
/**
 * @package org.openpsa.calendarwidget
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Simple event class for feeding event information to the calendar widgets
 *
 * @package org.openpsa.calendarwidget
 */
class org_openpsa_calendarwidget_event
{
   /**
    * Event CGUID
    *
    * @var string
    */
    public $guid = '';
    
   /**
    * Defines the start of an event - this property is required for calendar to work
    *
    * @var integer
    */
   var $start = 0;

   /**
    * Defines the end of an event
    *
    * @var integer
    */
   var $end = 0;

   /**
    * Link to the event
    *
    * @var string link Link to the event
    */
   var $link = '';

   /**
    * JS onclick for the event
    *
    * @var string link JS onclick handler
    */
   var $onclick = '';

   /**
    * Event title
    *
    * @var string
    */
   var $title = '';

   /**
    * Event description
    *
    * @var string
    */
   public $description = '';

   /**
    * Event location
    *
    * @var string
    */
   public $location = '';

   /**
    * Event CSS class
    *
    * @var string
    */
    public $class = '';

    /**
     * Actual event object
     *
     * @access protected
     */
    var $event = null;

    public function __construct($event = null)
    {
        if (is_object($event))
        {
            $this->event = $event;

            // Read values from event object
            $this->start = $this->event->start;
            $this->end = $this->event->end;
            $this->title = $this->event->title;

            if (isset($this->event->location))
            {
                $this->location = $this->event->location;
            }
        }
    }

    /**
     * Draws links to the right location
     */
    private function _render_link($text, $title = '', $link = false, $class = '')
    {
        if (!$link)
        {
            return $text;
        }

        if ($class)
        {
            $class = ' class="' . $class . '"';
        }

        $onclick = '';
        if ($this->onclick != '')
        {
            $onclick = " onclick=\"{$this->onclick}\"";
        }

        return "<a{$class} href=\"{$link}\"{$onclick} title=\"{$title}\">{$text}</a>";
    }

    /**
     * Renders hEvent compatible and nice time label
     *
     * @todo clean up and define properly the operation in various combinations of reference time and event start/end
     */
    function render_timelabel($reference_time = null, $show_day_name = false)
    {
        $timelabel = '';

        if (   !is_null($reference_time)
            && date('Y-m-d', $this->start) === date('Y-m-d', $reference_time))
        {
            $start_time = strftime('%H:%M - ', $this->start);
        }
        else
        {
            if ($show_day_name)
            {
                $start_time = strftime('%a %x %H:%M - ', $this->start);
            }
            else
            {
                $start_time = strftime('%x %H:%M - ', $this->start);
            }
        }

        if (date('Y-m-d', $this->start) === date('Y-m-d', $this->end))
        {
            $end_time = strftime('%H:%M', $this->end);
        }
        else if (   !is_null($reference_time)
                && date('Y-m-d', $this->end) === date('Y-m-d', $reference_time))
        {
            $end_time = strftime('%H:%M', $this->end);
        }
        else
        {
            $end_time = strftime('%x %H:%M', $this->end);
        }

        $dtstart = strftime('%Y-%m-%dT%H:%M:%S%z', $this->start);
        $dtend = strftime('%Y-%m-%dT%H:%M:%S%z', $this->end);

        $timelabel .= "        <abbr class=\"dtstart\" title=\"{$dtstart}\">{$start_time}</abbr>\n";
        $timelabel .= "        <abbr class=\"dtend\" title=\"{$dtend}\">{$end_time}</abbr>\n";

        return $timelabel;
    }

    /**
     * Draws one single event
     */
    public function render($element = 'div', $h_level = 3)
    {
        $rendered_event  = '';
        $rendered_event .= "<{$element} class=\"vevent\">\n";

        $rendered_event .= $this->render_timelabel();

        if (   $this->link != ''
            || $this->onclick != '')
        {
            $rendered_event .= "    <h{$h_level} class=\"summary\">" . $this->_render_link($this->title, $this->title, $this->link, 'url') . "</h{$h_level}>\n";
        }
        else if ($this->title)
        {
            $rendered_event .= "    <h{$h_level} class=\"summary\">{$this->title}</h{$h_level}>\n";
        }

        if ($this->location != '')
        {
            $rendered_event .= "    <div class=\"location\">{$this->location}</div>\n";
        }

        if (!is_null($this->event))
        {
            $rendered_event .= "    <span class=\"uid\" style=\"display: none;\">{$this->event->guid}</span>\n";
        }
        $rendered_event .= "</{$element}>\n";

        return $rendered_event;
    }
}
?>