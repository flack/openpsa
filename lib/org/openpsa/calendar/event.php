<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapper for org_openpsa_event with various helper functions
 * refactored from OpenPSA 1.x calendar
 *
 * @todo Figure out a good way to always use UTC for internal time storage
 * @property integer $start
 * @property integer $end
 * @property string $title
 * @property string $description
 * @property integer $type
 * @property string $extra
 * @property boolean $busy
 * @property integer $up
 * @property string $location
 * @property boolean $tentative
 * @property string $externalGuid
 * @property string $vCalSerialized
 * @property integer $orgOpenpsaAccesstype
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_event_dba extends midcom_core_dbaobject
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'org_openpsa_event';

    /**
     * list of participants
     *
     * (stored as eventmembers, referenced here for easier access)
     */
    public array $participants = [];

    /**
     * like $participants but for resources.
     */
    public array $resources = [];

    /**
     * vCalendar (or similar external source) GUID for this event
     *
     * (for vCalendar imports)
     */
    private string $old_externalGuid = '';

    /**
     * Send notifications to participants of the event
     */
    var bool $send_notify = true;

    /**
     * Send notification also to current user
     */
    public bool $send_notify_me = false;

    /**
     * Used to work around DM creation features to get correct notification type out
     */
    var bool $notify_force_add = false;

    public bool $search_relatedtos = true;
    public bool $ignorebusy_em = false;
    public bool $rob_tentative = false;

    public function get_label() : string
    {
        if ($this->start == 0) {
            return $this->title;
        }
        $formatter = midcom::get()->i18n->get_l10n()->get_formatter();
        return $formatter->date($this->start) . " {$this->title}";
    }

    public function _on_loaded()
    {
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.calendar');

        // Preserve vCal GUIDs once set
        $this->old_externalGuid = $this->externalGuid;

        // Hide details if we're not allowed to see them
        if (!$this->can_do('org.openpsa.calendar:read')) {
            $keep = ['metadata', 'id', 'guid', 'start', 'end', 'orgOpenpsaAccesstype'];
            $hide = array_diff($this->get_properties(), $keep);
            foreach ($hide as $key) {
                $this->$key = null;
            }
            $this->title = $l10n->get('private event');
        }
        // Check for empty title
        if (!$this->title) {
            $this->title = $l10n->get('untitled');
        }

        // Populate resources and participants list
        $this->_get_em();
    }

    /**
     * Preparations related to all save operations (=create/update)
     */
    private function _prepare_save() : bool
    {
        // Make sure we have accessType
        if (!$this->orgOpenpsaAccesstype) {
            $this->orgOpenpsaAccesstype = org_openpsa_core_acl::ACCESS_PUBLIC;
        }

        // Make sure we can actually reserve the resources we need
        $resources = array_keys(array_filter($this->resources));
        $checker = new org_openpsa_calendar_event_resource_dba;
        foreach ($resources as $id) {
            $checker->resource = $id;
            if (!$checker->verify_can_reserve()) {
                debug_add("Cannot reserve resource #{$id}, returning false", MIDCOM_LOG_ERROR);
                midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
                return false;
            }
        }

        //Check up
        if (   !$this->up
            && $this->title != '__org_openpsa_calendar') {
            $root_event = org_openpsa_calendar_interface::find_root_event();
            $this->up = $root_event->id;
        }

        //check for busy participants/resources
        if (!$this->ignorebusy_em) {
            $conflictmanager = new org_openpsa_calendar_conflictmanager($this);
            if (!$conflictmanager->run($this->rob_tentative)) {
                debug_add("Unresolved resource conflicts, aborting", MIDCOM_LOG_WARN);
                return false;
            }
        }

        //Preserve vCal GUIDs once set
        if ($this->old_externalGuid) {
            $this->externalGuid = $this->old_externalGuid;
        }

        return $this->_check_timerange();
    }

    private function _check_timerange() : bool
    {
        if (   !$this->start
            || !$this->end) {
            debug_add('Event must have start and end timestamps');
            midcom_connection::set_error(MGD_ERR_RANGE);
            return false;
        }

        /*
         * Force start and end seconds to 1 and 0 respectively
         * (to avoid stupid one second overlaps)
         */
        $this->start -= ($this->start % 60) - 1;
        $this->end -= $this->end % 60;

        if ($this->end < $this->start) {
            debug_add('Event cannot end before it starts, aborting');
            midcom_connection::set_error(MGD_ERR_RANGE);
            return false;
        }

        return true;
    }

    public function _on_creating() : bool
    {
        return $this->_prepare_save();
    }

    public function _on_created()
    {
        //TODO: handle the repeats somehow (if set)

        if ($this->search_relatedtos) {
            //TODO: add check for failed additions
            (new org_openpsa_relatedto_finder_event($this))->process();
        }
    }

    public function _on_updating() : bool
    {
        //TODO: Handle repeats
        return $this->_prepare_save();
    }

    public function _on_updated()
    {
        $this->_get_em();
        if ($this->send_notify) {
            $message_type = 'update';
            if ($this->notify_force_add) {
                $message_type = 'add';
            }

            foreach ($this->_get_participants() as $res_object) {
                debug_add("Notifying participant #{$res_object->id}");
                $res_object->notify($message_type, $this);
            }

            foreach ($this->_get_resources() as $res_object) {
                debug_add("Notifying resource #{$res_object->id}");
                $res_object->notify($message_type, $this);
            }
        }

        // Handle ACL accordingly
        foreach (array_keys($this->participants) as $person_id) {
            $user = midcom::get()->auth->get_user($person_id);

            // All participants can read and update
            $this->set_privilege('org.openpsa.calendar:read', $user->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:read', $user->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:update', $user->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:delete', $user->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:create', $user->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:privileges', $user->id, MIDCOM_PRIVILEGE_ALLOW);
        }

        if ($this->orgOpenpsaAccesstype == org_openpsa_core_acl::ACCESS_PRIVATE) {
            $this->set_privilege('org.openpsa.calendar:read', 'EVERYONE', MIDCOM_PRIVILEGE_DENY);
        } else {
            $this->set_privilege('org.openpsa.calendar:read', 'EVERYONE', MIDCOM_PRIVILEGE_ALLOW);
        }

        if ($this->search_relatedtos) {
            (new org_openpsa_relatedto_finder_event($this))->process();
        }
    }

    /**
     * @return org_openpsa_calendar_event_member_dba[]
     */
    private function _get_participants() : array
    {
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->add_constraint('eid', '=', $this->id);
        return $qb->execute_unchecked();
    }

    /**
     * @return org_openpsa_calendar_event_resource_dba[]
     */
    private function _get_resources() : array
    {
        $qb = org_openpsa_calendar_event_resource_dba::new_query_builder();
        $qb->add_constraint('event', '=', $this->id);
        return $qb->execute_unchecked();
    }

    public function _on_deleting() : bool
    {
        //Remove participants
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        foreach ($this->_get_participants() as $obj) {
            if ($this->send_notify) {
                $obj->notify('cancel', $this);
            }
            $obj->notify_person = false;
            $obj->delete();
        }

        //Remove resources
        foreach ($this->_get_resources() as $obj) {
            if ($this->send_notify) {
                $obj->notify('cancel', $this);
            }
            $obj->delete();
        }

        //Remove event parameters
        midcom::get()->auth->drop_sudo();

        return parent::_on_deleting();
    }

    /**
     * Fills $this->participants and $this->resources
     */
    private function _get_em()
    {
        if (!$this->id) {
            return;
        }

        // Participants
        $mc = org_openpsa_calendar_event_member_dba::new_collector('eid', $this->id);
        $this->participants = array_fill_keys($mc->get_values('uid'), true);
        // Resources
        $mc2 = org_openpsa_calendar_event_resource_dba::new_collector('event', $this->id);
        $this->resources = array_fill_keys($mc2->get_values('resource'), true);
    }

    /**
     * Returns a string describing the event and its participants
     */
    public function details_text(string $nl) : string
    {
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.calendar');
        $str = $l10n->get('location') . ': ' . $this->location . $nl;
        $str .= $l10n->get('time') . ': ' . $l10n->get_formatter()->timeframe($this->start, $this->end) . $nl;
        $str .= $l10n->get('participants') . ': ' . $this->implode_members($this->participants) . $nl;
        $str .= $l10n->get('resources') . ': ' . $this->implode_members($this->resources) . $nl;
        //TODO: Tentative, overlaps, public
        $str .= $l10n->get('description') . ': ' . $this->description . $nl;
        return $str;
    }

    /**
     * Returns a comma separated list of persons from array
     */
    private function implode_members(array $array) : string
    {
        $output = [];
        foreach (array_keys($array) as $pid) {
            $person = midcom_db_person::get_cached($pid);
            $output[] = $person->name;
        }
        return implode(', ', $output);
    }
}
