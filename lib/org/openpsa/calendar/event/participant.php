<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Wrapping for special case participant
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_event_participant_dba extends org_openpsa_calendar_event_member_dba
{
    var $event;
    var $person;
    var $participant;

    function __construct($identifier = NULL)
    {
        if (parent::__construct($identifier))
        {
            $this->event =& $this->eid;
            $this->participant =& $this->uid;
            $this->person =& $this->uid;
            if (!$this->orgOpenpsaObtype)
            {
                $this->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_EVENTPARTICIPANT;
            }
            return true;
        }
        return false;
    }

    function _on_loaded()
    {
        return true;
    }

    //TODO: Rewrite
    function notify($type = 'update', $event = false, $nl = "\n")
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("called with options: '{$type}', '{$event->guid}', '{$nl}'");
        $l10n = $_MIDCOM->i18n->get_l10n('org.openpsa.calendar');
        $recipient =& $this->get_person_obj();
        if (!$recipient)
        {
            debug_add('recipient could not be gotten, aborting', MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        //In general we should have the event passed to us since we might be notifying about changes that have not been committed yet
        if (!$event)
        {
            $event = $this->get_event_obj();
        }

        if (   ($recipient->id == $_MIDGARD['user'])
            && !$event->send_notify_me)
        {
            //Do not send notification to current user
            debug_add('event->send_notify_me is false and recipient is current user, aborting notify');
            debug_pop();
            return false;
        }

        $message = Array();
        $action = 'org.openpsa.calendar:noevent';

        switch ($type)
        {
            //Event information was updated
            case 'update':
                //PONDER: This in theory should have the old event title
                $action = 'org.openpsa.calendar:event_update';
                $message['title'] = sprintf($l10n->get('event "%s" was updated'), $event->title);
                $message['abstract'] = sprintf($l10n->get('event "%s" (%s) was updated'), $event->title, $event->format_timeframe());
                $message['content'] = sprintf($l10n->get('event "%s" was modified, updated information below.') . "{$nl}{$nl}", $event->title);
                $message['content'] .= $event->details_text(false, $this, $nl);
            break;
            //Participant was added to the event
            case 'add':
                $action = 'org.openpsa.calendar:event_add';
                $message['title'] = sprintf($l10n->get('you have been added to event "%s"'), $event->title);
                $message['abstract'] = sprintf($l10n->get('you have been added to event "%s" (%s)'), $event->title, $event->format_timeframe());
                $message['content'] = sprintf($l10n->get('you have been added to event "%s" participants list, event information below.') . "{$nl}{$nl}", $event->title);
                $message['content'] .= $event->details_text(false, $this, $nl);
            break;
            //Participant was removed from event
            case 'remove':
                $action = 'org.openpsa.calendar:event_remove';
                $message['title'] = sprintf($l10n->get('you have been removed from event "%s"'), $event->title);
                $message['abstract'] = sprintf($l10n->get('you have been removed from event "%s" (%s)'), $event->title, $event->format_timeframe());
                $message['content'] = sprintf($l10n->get('you have been removed from event "%s" (%s) participants list.'), $event->title, $event->format_timeframe());
            break;
            //Event was cancelled (=deleted)
            case 'cancel':
                $action = 'org.openpsa.calendar:event_cancel';
                $message['title'] = sprintf($l10n->get('event "%s" was cancelled'), $event->title);
                $message['abstract'] = sprintf($l10n->get('event "%s" (%s) was cancelled'), $event->title, $event->format_timeframe());
                $message['content'] = sprintf($l10n->get('event "%s" (%s) was cancelled.'), $event->title, $event->format_timeframe());
            break;
            default:
                debug_add("action '{$type}' is invalid, aborting notification", MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
        }

        if (   $type == 'cancel'
            || $type == 'remove')
        {
            // TODO: Create iCal export with correct delete commands
        }
        else
        {
            $vcal_data = $event->vcal_headers();
            $vcal_data .= $event->vcal_export();
            $vcal_data .= $event->vcal_footers();
            $message['attachments'] = array
            (
                array
                (
                    'name' => midcom_generate_urlname_from_string(sprintf('%s on %s', $event->title, date('Ymd_Hi', $event->start))) . '.ics',
                    'mimetype' => 'text/calendar',
                    'content' => $vcal_data,
                ),
            );
        }

        debug_pop();
        return org_openpsa_notifications::notify($action, $recipient->guid, $message);
    }

// *** End class org_openpsa_calendar_event_participant_dba ***
}

?>