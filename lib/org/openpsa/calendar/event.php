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
 * @property string $orgOpenpsaOwnerWg
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_event_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_event';

    /**
     * list of participants
     *
     * (stored as eventmembers, referenced here for easier access)
     *
     * @var array
     */
    public $participants = [];

    /**
     * like $participants but for resources.
     *
     * @var array
     */
    public $resources = [];

    /**
     * vCalendar (or similar external source) GUID for this event
     *
     * (for vCalendar imports)
     *
     * @var string
     */
    private $old_externalGuid = '';

    /**
     * Send notifications to participants of the event
     *
     * @var boolean
     */
    var $send_notify = true;

    /**
     * Send notification also to current user
     *
     * @var boolean
     */
    public $send_notify_me = false;

    /**
     * Used to work around DM creation features to get correct notification type out
     *
     * @var boolean
     */
    var $notify_force_add = false;

    public $search_relatedtos = true;
    public $ignorebusy_em = false;
    public $rob_tentative = false;

    public function get_label()
    {
        if ($this->start == 0) {
            return $this->title;
        }
        $formatter = midcom::get()->i18n->get_l10n()->get_formatter();
        return $formatter->date($this->start) . " {$this->title}";
    }

    public function get_parent_guid_uncached()
    {
        if (   $this->up
            || $this->title != '__org_openpsa_calendar') {
            $root_event = org_openpsa_calendar_interface::find_root_event();
            if ($this->id != $root_event->id) {
                return $root_event->guid;
            }
        }
        return null;
    }

    public function _on_loaded()
    {
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.calendar');

        // Preserve vCal GUIDs once set
        if (isset($this->externalGuid)) {
            $this->old_externalGuid = $this->externalGuid;
        }

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
        if (isset($this->old_externalGuid)) {
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
        $this->start = floor($this->start / 60) * 60 + 1;
        $this->end = (floor($this->end / 60) * 60);

        if ($this->end < $this->start) {
            debug_add('Event cannot end before it starts, aborting');
            midcom_connection::set_error(MGD_ERR_RANGE);
            return false;
        }

        return true;
    }

    public function _on_creating()
    {
        return $this->_prepare_save();
    }

    public function _on_created()
    {
        //TODO: handle the repeats somehow (if set)

        if ($this->search_relatedtos) {
            //TODO: add check for failed additions
            $this->get_suspected_task_links();
            $this->get_suspected_sales_links();
        }
    }

    /**
     * Returns a defaults template for relatedto objects
     *
     * @return object org_openpsa_relatedto_dba
     */
    private function _suspect_defaults() : org_openpsa_relatedto_dba
    {
        $link_def = new org_openpsa_relatedto_dba();
        $link_def->fromComponent = 'org.openpsa.calendar';
        $link_def->fromGuid = $this->guid;
        $link_def->fromClass = get_class($this);
        $link_def->status = org_openpsa_relatedto_dba::SUSPECTED;
        return $link_def;
    }

    /**
     * Queries org.openpsa.projects for suspected task links and saves them
     */
    private function get_suspected_task_links()
    {
        // Do not seek if we have only one participant (gives a ton of results, most of them useless)
        if (count($this->participants) < 2) {
            debug_add("we have less than two participants, skipping seek");
            return;
        }

        // Do no seek if we already have confirmed links
        $mc = new org_openpsa_relatedto_collector($this->guid, org_openpsa_projects_task_dba::class, 'outgoing');
        $mc->add_constraint('status', '=', org_openpsa_relatedto_dba::CONFIRMED);

        $links = $mc->get_related_guids();
        if (!empty($links)) {
            $cnt = count($links);
            debug_add("Found {$cnt} confirmed links already, skipping seek");
            return;
        }

        $link_def = $this->_suspect_defaults();
        $projects_suspect_links = org_openpsa_relatedto_suspect::find_links_object_component($this, 'org.openpsa.projects', $link_def);

        foreach ($projects_suspect_links as $linkdata) {
            if ($linkdata['link']->create()) {
                debug_add("saved link to task #{$linkdata['other_obj']->id} (link id #{$linkdata['link']->id})", MIDCOM_LOG_INFO);
            } else {
                debug_add("could not save link to task #{$linkdata['other_obj']->id}, errstr" . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        }
    }

    /**
     * Queries org.openpsa.sales for suspected task links and saves them
     */
    private function get_suspected_sales_links()
    {
        // Do no seek if we already have confirmed links
        $mc = new org_openpsa_relatedto_collector($this->guid, [org_openpsa_sales_salesproject_dba::class, org_openpsa_sales_salesproject_deliverable_dba::class]);
        $mc->add_constraint('status', '=', org_openpsa_relatedto_dba::CONFIRMED);

        $links = $mc->get_related_guids();
        if (!empty($links)) {
            $cnt = count($links);
            debug_add("Found {$cnt} confirmed links already, skipping seek");
            return;
        }

        $link_def = $this->_suspect_defaults();
        $sales_suspect_links = org_openpsa_relatedto_suspect::find_links_object_component($this, 'org.openpsa.sales', $link_def);
        foreach ($sales_suspect_links as $linkdata) {
            if ($linkdata['link']->create()) {
                debug_add("saved sales link to {$linkdata['other_obj']->guid} (link id #{$linkdata['link']->id})", MIDCOM_LOG_INFO);
            } else {
                debug_add("could not save sales link to {$linkdata['other_obj']->guid}, errstr" . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        }
    }

    public function _on_updating()
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
            $this->get_suspected_task_links();
            $this->get_suspected_sales_links();
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

    public function _on_deleting()
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
    public function details_text($nl) : string
    {
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.calendar');
        $str = '';
        $str .= sprintf($l10n->get('location: %s') . $nl, $this->location);
        $str .= sprintf($l10n->get('time: %s') . $nl, $l10n->get_formatter()->timeframe($this->start, $this->end));
        $str .= sprintf($l10n->get('participants: %s') . $nl, $this->implode_members($this->participants));
        $str .= sprintf($l10n->get('resources: %s') . $nl, $this->implode_members($this->resources));
        //TODO: Tentative, overlaps, public
        $str .= sprintf($l10n->get('description: %s') . $nl, $this->description);
        return $str;
    }

    /**
     * Returns a comma separated list of persons from array
     */
    private function implode_members(array $array) : string
    {
        $output = [];
        foreach (array_keys($array) as $pid) {
            $person = org_openpsa_contacts_person_dba::get_cached($pid);
            $output[] = $person->name;
        }
        return implode(', ', $output);
    }
}
