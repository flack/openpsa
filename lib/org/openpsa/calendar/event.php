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
    var $participants = array();

    /**
     * like $participants but for resources.
     *
     * @var array
     */
    var $resources = array();

    /* Skip repeat handling for now
     * var $repeat_rule;
     * var $repeat_prev;  //GUID, For repeating events, previous event
     * var $repeat_next;  //GUID, For repeating events, next event
     * var $repeat_rule;  //Array, describes the repeat rules:
     */

    /*
     * * = mandatory key
     * * ['type'], string: daily,weekly,monthly_by_dom
     * * ['interval'], int: 1 means every day/week/monthday
     * * ['from'], int: timestamp of date from which repeating starts (1 second after midnight)
     *   ['to'], int: timestamp of date to which the repeating ends (1 second before midnight)
     *   ['num'], int: how many occurrences of repeat fit between from and to (mind the interval!)
     *   ['days'], array: keys are weekday numbers, values true/false
     *
     * It's mandatory to have 'to' or 'num' defined, the other can be calculated from the other,
     * if both are defined 'to' has precedence.
     */

    /**
     * vCalendar (or similar external source) GUID for this event
     *
     * (for vCalendar imports)
     *
     * @var string
     */
    var $externalGuid = '';
    var $old_externalGuid = '';    //as above, for diffs

    /**
     * unserialized from vCalSerialized
     * not used for anything yet
     */
    private $_vCal_store = null;

    /**
     * In case of busy eventmembers this is an array
     *
     * @var mixed
     */
    var $busy_em = false;

    /**
     * In case of busy event resources this is an array
     *
     * @var mixed
     */
    var $busy_er = false;

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
    var $send_notify_me = false;

    /**
     * Used to work around DM creation features to get correct notification type out
     *
     * @var boolean
     */
    var $notify_force_add = false;
    var $search_relatedtos = true;

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    public function get_label()
    {
        if ($this->start == 0)
        {
            return $this->title;
        }
        else
        {
            return strftime('%x', $this->start) . " {$this->title}";
        }
    }

    function get_parent_guid_uncached()
    {
        $root_event = org_openpsa_calendar_interface::find_root_event();
        if ( $root_event
            && $this->id != $root_event->id)
        {
            return $root_event->guid;
        }
        else
        {
            return null;
        }
    }

    public function __get($property)
    {
        if ($property == 'vCal_store')
        {
            /* This in theory can cause confusion if the actual DB status (cache) field is different.
              but it's better to get the correct value late than never, next update will make sure it's correct in DB as well */
            if(is_null($this->_vCal_store))
            {
                $this->_unserialize_vcal();
            }
            return $this->_vCal_store;
        }
        return parent::__get($property);
    }

    public function _on_loaded()
    {
        $l10n = $_MIDCOM->i18n->get_l10n('org.openpsa.calendar');

        // Check for empty title in existing events
        if (   $this->id
            && !$this->title)
        {
            $this->title = $l10n->get('untitled');
        }

        // Preserve vCal GUIDs once set
        if (isset($this->externalGuid))
        {
            $this->old_externalGuid = $this->externalGuid;
        }

        // Populates resources and participants list
        $this->_get_em();

        // Hide details if we're not allowed to see them
        if (!$_MIDCOM->auth->can_do('org.openpsa.calendar:read', $this))
        {
            // Hide almost all properties
            $properties = $this->get_properties();
            foreach ($properties as $key)
            {
                switch ($key)
                {
                    //Internal fields, do nothing
                    case 'metadata':
                    case 'id':
                    case 'guid':
                         break;
                    //These fields we keep unchanged
                    case 'start':
                    case 'end':
                    case 'resources':
                    case 'participants':
                    case 'orgOpenpsaAccesstype':
                        break;
                    case 'title':
                        $this->$key = $l10n->get('private event');
                        break;
                    default:
                        $this->$key = null;
                        break;
                }
            }
        }
    }


    /**
     * Handles updates to repeating events
     */
    function update_repeat($handler='this')
    {
        //TODO: Re-implement
        return false;
    }

    /**
     * Unserializes vCalSerialized to vCal_store
     */
    private function _unserialize_vcal()
    {
        $unserRet = @unserialize($this->vCalSerialized);
        if ($unserRet === false)
        {
            //Unserialize failed (probably newline/encoding issue), try to fix the serialized string and unserialize again
            $unserRet = @unserialize(org_openpsa_helpers::fix_serialization($this->vCalSerialized));
            if ($unserRet === false)
            {
                debug_add('Failed to unserialize vCalSerialized', MIDCOM_LOG_WARN);
                $this->_vCal_store = array();
                return;
            }
        }
        $this->_vCal_store = $unserRet;
    }

    /**
     * Serializes vCal_store to vCalSerialized
     */
    private function _serialize_vcal()
    {
        //TODO: do not store those variables that are regenerated on runtime
/* copied from old, must be refactored
               //Do not store vCal variables that are properties of the event itself
               unset ($this->vCal_variables['DESCRIPTION'], $this->vCal_parameters['DESCRIPTION']);
               unset ($this->vCal_variables['SUMMARY'], $this->vCal_parameters['SUMMARY']);
               unset ($this->vCal_variables['LOCATION'], $this->vCal_parameters['LOCATION']);
               unset ($this->vCal_variables['DTSTART'], $this->vCal_parameters['DTSTART']);
               unset ($this->vCal_variables['DTEND'], $this->vCal_parameters['DTEND']);
               unset ($this->vCal_variables['CLASS'], $this->vCal_parameters['CLASS']);
               unset ($this->vCal_variables['STATUS'], $this->vCal_parameters['STATUS']);
               unset ($this->vCal_variables['TRANSP'], $this->vCal_parameters['TRANSP']);
*/
        $this->vCalSerialized = serialize($this->vCal_store);
    }

    /**
     * Preparations related to all save operations (=create/update)
     */
    private function _prepare_save($ignorebusy_em = false, $rob_tentantive = false, $repeat_handler='this')
    {
        // Make sure we have accessType
        if (!$this->orgOpenpsaAccesstype)
        {
            $this->orgOpenpsaAccesstype = ORG_OPENPSA_ACCESSTYPE_PUBLIC;
        }

        // Make sure we can actually reserve the resources we need
        foreach ($this->resources as $id => $bool)
        {
            if (!$bool)
            {
                continue;
            }
            $checker = new org_openpsa_calendar_event_resource_dba();
            $checker->resource = $id;
            if (!$checker->verify_can_reserve())
            {
                $msg = "Cannot reserve resource #{$id}, returning false";
                $this->errstr = $msg;
                debug_add($msg, MIDCOM_LOG_ERROR);
                midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
                unset ($id, $checker, $msg);
                return false;
            }
            unset ($id, $checker);
        }

        //Check up
        if (!$this->up
            && $this->title != '__org_openpsa_calendar')
        {
            $root_event = org_openpsa_calendar_interface::find_root_event();
            $this->up = $root_event->id;
        }
        //Doublecheck
        if (!$this->up)
        {
            debug_add('Event up not set, aborting');
            $this->errstr = 'Event UP not set';
            return false; //Calendar events must always be under some other event
        }

        //check for busy participants/resources
        if (   $this->busy_em($rob_tentantive)
            && !$ignorebusy_em)
        {
            debug_print_r("Unresolved resource conflicts, aborting, busy_em:", $this->busy_em);
            $this->errstr = 'Resource conflict with busy event';
            return false;
        }
        else
        {
            $this->busy_em = false; //Make sure this is only present for the latest event op
        }

        /*
         * Calendar events always have 'inherited' owner
         * different bit buckets for calendar events might have different owners.
         */
        $this->owner = 0;

        //Preserve vCal GUIDs once set
        if (isset($this->old_externalGuid))
        {
            $this->externalGuid = $this->old_externalGuid;
        }

        $this->_serialize_vcal();

        return true;
    }

    private function _check_timerange()
    {
        //Force types
        $this->start = (int)$this->start;
        $this->end = (int)$this->end;
        if (   !$this->start
            || !$this->end)
        {
            debug_add('Event must have start and end timestamps');
            midcom_connection::set_error(MGD_ERR_RANGE);
            return false;
        }

        /*
         * Force start and end seconds to 1 and 0 respectively
         * (to avoid stupid one second overlaps)
         */
        $this->start = mktime(  date('G', $this->start),
                                date('i', $this->start),
                                1,
                                date('n', $this->start),
                                date('j', $this->start),
                                date('Y', $this->start));
        $this->end = mktime(date('G', $this->end),
                            date('i', $this->end),
                            0,
                            date('n', $this->end),
                            date('j', $this->end),
                            date('Y', $this->end));

        if ($this->end < $this->start)
        {
            debug_add('Event cannot end before it starts, aborting');
            midcom_connection::set_error(MGD_ERR_RANGE);
            return false;
        }

        return true;
    }

    //TODO: Move these options elsewhere
    public function _on_creating($ignorebusy_em = false, $rob_tentantive = false, $repeat_handler='this')
    {
        if (!$this->_prepare_save($ignorebusy_em, $rob_tentantive, $repeat_handler))
        {
            //Some requirement for an update failed, see $this->__errstr;
            debug_add('prepare_save failed, aborting', MIDCOM_LOG_ERROR);
            return false;
        }
        return true;
    }

    public function _on_created()
    {
        //TODO: handle the repeats somehow (if set)
        // When anonymous creation is allowed creating the members can be problematic, this works around that
        $_MIDCOM->auth->request_sudo('org.openpsa.calendar');

        $_MIDCOM->auth->drop_sudo();
        if ($this->search_relatedtos)
        {
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
    private function _suspect_defaults()
    {
        $link_def = new org_openpsa_relatedto_dba();
        $link_def->fromComponent = 'org.openpsa.calendar';
        $link_def->fromGuid = $this->guid;
        $link_def->fromClass = get_class($this);
        $link_def->status = ORG_OPENPSA_RELATEDTO_STATUS_SUSPECTED;
        return $link_def;
    }

    /**
     * Queries org.openpsa.projects for suspected task links and saves them
     */
    function get_suspected_task_links()
    {
        //Safety
        if (!$this->_suspects_classes_present())
        {
            debug_add('required classes not present, aborting', MIDCOM_LOG_WARN);
            return;
        }

        // Do not seek if we have only one participant (gives a ton of results, most of them useless)
        if (count($this->participants) < 2)
        {
            debug_add("we have less than two participants, skipping seek");
            return;
        }

        // Do no seek if we already have confirmed links
        $mc = new org_openpsa_relatedto_collector($this->guid, 'org_openpsa_projects_task_dba', 'outgoing');
        $mc->add_constraint('status', '=', ORG_OPENPSA_RELATEDTO_STATUS_CONFIRMED);

        $links = $mc->get_related_guids();
        if (!empty($links))
        {
            $cnt = count($links);
            debug_add("Found {$cnt} confirmed links already, skipping seek");
            return;
        }

        $link_def = $this->_suspect_defaults();
        $projects_suspect_links = org_openpsa_relatedto_suspect::find_links_object_component($this, 'org.openpsa.projects', $link_def);

        foreach ($projects_suspect_links as $linkdata)
        {
            debug_add("processing task/project #{$linkdata['other_obj']->id}, type: {$linkdata['other_obj']->orgOpenpsaObtype} (class: " . get_class($linkdata['other_obj']) . ")");
            //Only save links to tasks
            if ($linkdata['other_obj']->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_TASK)
            {
                $stat = $linkdata['link']->create();
                if ($stat)
                {
                    debug_add("saved link to task #{$linkdata['other_obj']->id} (link id #{$linkdata['link']->id})", MIDCOM_LOG_INFO);
                }
                else
                {
                    debug_add("could not save link to task #{$linkdata['other_obj']->id}, errstr" . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
                }
            }
        }

        return;
    }

    /**
     * Check if we have necessary classes available to do relatedto suspects
     *
     * @return boolean
     */
    private function _suspects_classes_present()
    {
        if (   !class_exists('org_openpsa_relatedto_dba')
            || !class_exists('org_openpsa_relatedto_suspect'))
        {
            return false;
        }
        return true;
    }

    /**
     * Queries org.openpsa.sales for suspected task links and saves them
     */
    function get_suspected_sales_links()
    {
        debug_add('called');
        //Safety
        if (!$this->_suspects_classes_present())
        {
            debug_add('required classes not present, aborting', MIDCOM_LOG_WARN);
            return;
        }

        // Do no seek if we already have confirmed links
        $mc = new org_openpsa_relatedto_collector($this->guid, array('org_openpsa_salesproject_dba', 'org_openpsa_salesproject_deliverable_dba'));
        $mc->add_constraint('status', '=', ORG_OPENPSA_RELATEDTO_STATUS_CONFIRMED);

        $links = $mc->get_related_guids();
        if (!empty($links))
        {
            $cnt = count($links);
            debug_add("Found {$cnt} confirmed links already, skipping seek");
            return;
        }

        $link_def = $this->_suspect_defaults();
        $sales_suspect_links = org_openpsa_relatedto_suspect::find_links_object_component($this, 'org.openpsa.sales', $link_def);
        foreach ($sales_suspect_links as $linkdata)
        {
            debug_add("processing sales link {$linkdata['other_obj']->guid}, (class: " . get_class($linkdata['other_obj']) . ")");
            $stat = $linkdata['link']->create();
            if ($stat)
            {
                debug_add("saved link to {$linkdata['other_obj']->guid} (link id #{$linkdata['link']->id})", MIDCOM_LOG_INFO);
            }
            else
            {
                debug_add("could not save link to {$linkdata['other_obj']->guid}, errstr" . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        }

        debug_add('done');
        return;
    }

    //TODO: move these options elsewhere
    public function _on_updating($ignorebusy_em = false, $rob_tentantive = false, $repeat_handler='this')
    {
        //TODO: Handle repeats

        if (!$this->_prepare_save($ignorebusy_em, $rob_tentantive, $repeat_handler))
        {
            //Some requirement for an update failed, see $this->__errstr;
            debug_add('prepare_save failed, aborting', MIDCOM_LOG_ERROR);
            return false;
        }

        if (!$this->_check_timerange())
        {
            return false;
        }

        return true;
    }

    public function _on_updated()
    {
        $this->_get_em();

        if ($this->send_notify)
        {
            foreach ($this->participants as $id => $selected)
            {
                $res_object = $this->_get_member_by_personid($id);
                if (!$res_object)
                {
                    continue;
                }
                debug_add("Notifying participant #{$id}");
                if ($this->notify_force_add)
                {
                    $res_object->notify('add', $this);
                }
                else
                {
                    $res_object->notify('update', $this);
                }
            }

            foreach ($this->resources as $id => $selected)
            {
                $res_object =  $this->_get_member_by_personid($id, 'resource');
                if (!$res_object)
                {
                    continue;
                }

                debug_add("Notifying resource #{$res_object->id}");
                if ($this->notify_force_add)
                {
                    $res_object->notify('add', $this);
                }
                else
                {
                    $res_object->notify('update', $this);
                }
            }
        }

        // Handle ACL accordingly
        foreach ($this->participants as $person_id => $selected)
        {
            $user = $_MIDCOM->auth->get_user($person_id);

            // All participants can read and update
            $this->set_privilege('org.openpsa.calendar:read', $user->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:read', $user->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:update', $user->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:delete', $user->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:create', $user->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:privileges', $user->id, MIDCOM_PRIVILEGE_ALLOW);
        }

        if ($this->orgOpenpsaAccesstype == ORG_OPENPSA_ACCESSTYPE_PRIVATE)
        {
            $this->set_privilege('org.openpsa.calendar:read', 'EVERYONE', MIDCOM_PRIVILEGE_DENY);
        }
        else
        {
            $this->set_privilege('org.openpsa.calendar:read', 'EVERYONE', MIDCOM_PRIVILEGE_ALLOW);
        }

        if ($this->search_relatedtos)
        {
            $this->get_suspected_task_links();
            $this->get_suspected_sales_links();
        }
    }

    private function _get_member_by_personid($id, $type='participant')
    {
        if (!$this->id)
        {
            return false;
        }
        $qb = org_openpsa_calendar_event_participant_dba::new_query_builder();
        $qb->add_constraint('eid', '=', $this->id);
        $qb->add_constraint('uid', '=', $id);
        $results = $qb->execute_unchecked();
        if (empty($results))
        {
            return false;
        }
        return $results[0];
    }

    //TODO: move this option elsewhere
    public function _on_deleting($repeat_handler = 'this')
    {
        $this->_get_em();
        //Remove participants
        $_MIDCOM->auth->request_sudo('org.openpsa.calendar');
        reset ($this->participants);
        while (list ($id, $bool) = each ($this->participants))
        {
            $obj =  $this->_get_member_by_personid($id, 'participant');
            if (is_object($obj))
            {
                if (   $repeat_handler=='this'
                    && $this->send_notify)
                {
                    $obj->notify('cancel', $this);
                }
                $obj->delete(false);
            }
        }

        //Remove resources
        reset ($this->resources);
        while (list ($id, $bool) = each ($this->resources))
        {
            $obj =  $this->_get_member_by_personid($id, 'resource');
            if (is_object($obj))
            {
                if (   $repeat_handler=='this'
                    && $this->send_notify)
                {
                    $obj->notify('cancel', $this);
                }
                $obj->delete(false);
            }
        }

        //Remove event parameters
        $_MIDCOM->auth->drop_sudo();

        return true;
    }

    /**
     * Find event with arbitrary GUID either in externalGuid or guid
     */
    function search_vCal_uid($uid)
    {
        //TODO: MidCOM DBAize
        $qb = new midgard_query_builder('org_openpsa_event');
        $qb->begin_group('OR');
            $qb->add_constraint('guid', '=', $uid);
            $qb->add_constraint('externalGuid', '=', $uid);
        $qb->end_group();
        $ret = @$qb->execute();
        if (   $ret
            && count($ret) > 0)
        {
            //It's unlikely to have more than one result and this should return an object (or false)
            return $ret[0];
        }
        else
        {
            return false;
        }
    }

    private function _busy_em_event_constraints(&$qb_ev, $fieldname = 'eid')
    {
        $qb_ev->add_constraint($fieldname . '.busy', '<>', false);
        if ($this->id)
        {
            $qb_ev->add_constraint($fieldname . '.id', '<>', (int)$this->id);
        }
        //Target event starts or ends inside this events window or starts before and ends after
        $qb_ev->begin_group('OR');
            $qb_ev->begin_group('AND');
                $qb_ev->add_constraint($fieldname . '.start', '>=', (int)$this->start);
                $qb_ev->add_constraint($fieldname . '.start', '<=', (int)$this->end);
            $qb_ev->end_group();
            $qb_ev->begin_group('AND');
                $qb_ev->add_constraint($fieldname . '.end', '<=', (int)$this->end);
                $qb_ev->add_constraint($fieldname . '.end', '>=', (int)$this->start);
            $qb_ev->end_group();
            $qb_ev->begin_group('AND');
                $qb_ev->add_constraint($fieldname . '.start', '<=', (int)$this->start);
                $qb_ev->add_constraint($fieldname . '.end', '>=', (int)$this->end);
            $qb_ev->end_group();
        $qb_ev->end_group();
    }

    /**
     * Check for potential busy conflicts to allow more graceful handling of those conditions
     *
     * Also allows normal events to "rob" resources from tentative ones.
     * NOTE: return false for *no* (or resolved automatically) conflicts and true for unresolvable conflicts
     */
    function busy_em($rob_tentative = false)
    {
        //If we're not busy it's not worth checking
        if (!$this->busy)
        {
            debug_add('we allow overlapping, so there is no point in checking others');
            return false;
        }
        //If this event is tentative always disallow robbing resources from other tentative events
        if ($this->tentative)
        {
            $rob_tentative = false;
        }
        //We need sudo to see busys in events we normally don't see and to rob resources from tentative events
        $_MIDCOM->auth->request_sudo('org.openpsa.calendar');

        //Storage for events that have been modified due the course of this method
        $modified_events = array();

        /**
         * Look for duplicate events only if we have participants or resources, otherwise we incorrectly get all events at
         * the same timeframe as duplicates since there are no participant constraints to narrow things down
         */
        if (!empty($this->participants))
        {
            //We attack this "backwards" in the sense that in the end we need the events but this is faster way to filter them
            $qb_ev = org_openpsa_calendar_event_member_dba::new_query_builder();
            $this->_busy_em_event_constraints($qb_ev, 'eid');
            //Shared eventmembers
            reset ($this->participants);
            $qb_ev->begin_group('OR');
                foreach ($this->participants as $uid => $bool)
                {
                    $qb_ev->add_constraint('uid', '=', $uid);
                }
            $qb_ev->end_group();
            $ret_ev = $qb_ev->execute();
            unset($qb_ev);
        }
        else
        {
            $ret_ev = array();
        }

        // Shared resources need a separate check (different member object)
        if (!empty($this->resources))
        {
            $qb_ev2 = org_openpsa_calendar_event_resource_dba::new_query_builder();
            $this->_busy_em_event_constraints($qb_ev2, 'event');
            reset ($this->resources);
            $qb_ev2->begin_group('OR');
                foreach ($this->resources as $resource => $bool)
                {
                    $qb_ev2->add_constraint('resource', '=', $resource);
                }
            $qb_ev2->end_group();
            $ret_ev2 = $qb_ev2->execute();
            unset($qb_ev2);
        }
        else
        {
            $ret_ev2 = array();
        }

        // TODO: Shared tasks need a separate check (different member object)

        // Both QBs returned empty sets
        if (   (   !is_array($ret_ev)
                || count($ret_ev) === 0)
            && (   !is_array($ret_ev2)
                || count($ret_ev2) === 0)
            )
        {
            //No busy events found within the timeframe
            $_MIDCOM->auth->drop_sudo();
            debug_add('no overlaps found');
            return false;
        }

        foreach ($ret_ev as $member)
        {
            $this->_process_participant($member, $modified_events, $rob_tentative);
        }

        foreach ($ret_ev2 as $member)
        {
            $this->_process_resource($member, $modified_events, $rob_tentative);
        }

        if (   is_array($this->busy_em)
            || is_array($this->busy_er))
        {
            //Unresolved conflicts (note return value is for conflicts not lack of them)
            $_MIDCOM->auth->drop_sudo();
            debug_add('unresolvable conflicts found, returning true');
            midcom_connection::set_error(MGD_ERR_ERROR);
            return true;
        }

        foreach($modified_events as $event)
        {
            //These events have been robbed of (some of) their resources
            $creator = new midcom_db_person($event->metadata->creator);
            if ( (count($event->participants) == 0
                  || (count($event->participants) == 1
                      && array_key_exists($creator->id, $event->participants)
                     )
                  )
                &&  count($event->resources) == 0)
            {
                /* If modified event has no-one or only creator as participant and no resources
                   then delete it (as it's unlikely the stub event is useful anymore) */
                debug_add("event {$event->title} (#{$event->id}) has been robbed of all of its resources, calling delete");
                //TODO: take notifications and repeats into account
                $event->delete();
            }
            else
            {
                //Otherwise just commit the changes
                //TODO: take notifications and repeats into account
                debug_add("event {$event->title} (#{$event->id}) has been robbed of some its resources, calling update");
                $event->update();
            }
        }

        $_MIDCOM->auth->drop_sudo();
        //No conflicts found or they could be automatically resolved
        $this->busy_em = false;
        $this->busy_er = false;
        return false;
    }

    private function _process_resource($member, &$modified_events, $rob_tentative)
    {
        //We might get multiple matches for same event/resource
        static $processed_events_resources = array();

        //Check if we have processed this resource/event combination already
        if (   array_key_exists($member->event, $processed_events_resources)
            && array_key_exists($member->resource, $processed_events_resources[$member->event]))
        {
            continue;
        }
        if (   !array_key_exists($member->event, $processed_events_resources)
            || !is_array($processed_events_resources[$member->event]))
        {
            $processed_events_resources[$member->event] = array();
        }
        $processed_events_resources[$member->event][$member->resource] = true;

        if (array_key_exists($member->event, $modified_events))
        {
            $event =& $modified_events[$member->event];
            $set_as_modified = false;
        }
        else
        {
            try
            {
                $event = new org_openpsa_calendar_event_dba($member->event);
                $set_as_modified = true;
            }
            catch (midcom_error $e)
            {
                debug_add("event_resource #{$member->id} links ot bogus event #{$member->event}, skipping and removing", MIDCOM_LOG_WARN);
                $member->delete();
                continue;
            }
        }
        debug_add("overlap found in event {$event->title} (#{$event->id})");

        if (   $event->tentative
            && $rob_tentative)
        {
            debug_add('event is tentative, robbing resources');
            //"rob" resources from tentative event
            $event = new org_openpsa_calendar_event_dba($event->id);

            //resources
            reset($this->resources);
            foreach ($this->resources as $id => $bool)
            {
                if (array_key_exists($id, $event->resources))
                {
                    unset($event->resources[$id]);
                }
            }
            if ($set_as_modified)
            {
                $modified_events[$event->id] = $event;
            }
        }
        else
        {
            debug_add('event is normal, flagging busy');
            //Non tentative event, flag busy resources
            if (!is_array($this->busy_er))
            {
                //this is false under normal circumstances
                $this->busy_er = array();
            }
            if (   !array_key_exists($member->guid, $this->busy_er)
                || !is_array($this->busy_er[$member->resource]))
            {
                //for mapping
                $this->busy_er[$member->resource] = array();
            }
            //PONDER: The display end might have issues with event guid that they cannot see without sudo...
            $this->busy_er[$member->resource][] = $event->guid;
        }
    }

    private function _process_participant($member, &$modified_events, $rob_tentative)
    {
        //We might get multiple matches for same event/person
        static $processed_events_participants = array();

        //Check if we have processed this participant/event combination already
        if (   array_key_exists($member->eid, $processed_events_participants)
            && array_key_exists($member->uid, $processed_events_participants[$member->eid]))
        {
            return;
        }
        if (   !array_key_exists($member->eid, $processed_events_participants)
            || !is_array($processed_events_participants[$member->eid]))
        {
            $processed_events_participants[$member->eid] = array();
        }
        $processed_events_participants[$member->eid][$member->uid] = true;

        try
        {
            $event = new org_openpsa_calendar_event_dba($member->eid);
        }
        catch (midcom_error $e)
        {
            debug_add("eventmember #{$member->id} links to bogus event #{$member->eid}, skipping and removing", MIDCOM_LOG_WARN);
            $member->delete();
            return;
        }
        debug_add("overlap found in event {$event->title} (#{$event->id})");

        if (   $event->tentative
            && $rob_tentative)
        {
            debug_add('event is tentative, robbing resources');
            //"rob" resources from tentative event
            $event = new org_openpsa_calendar_event_dba($event->id);

            //participants
            reset($this->participants);
            foreach ($this->participants as $id => $bool)
            {
                if (array_key_exists($id, $event->participants))
                {
                    unset($event->participants[$id]);
                }
            }
            $modified_events[$event->id] = $event;
        }
        else
        {
            debug_add('event is normal, flagging busy');
            //Non tentative event, flag busy resources
            if (!is_array($this->busy_em))
            {
                //this is false under normal circumstances
                $this->busy_em = array();
            }
            if (   !array_key_exists($member->guid, $this->busy_em)
                || !is_array($this->busy_em[$member->uid]))
            {
                //for mapping
                $this->busy_em[$member->uid] = array();
            }
            //PONDER: The display end might have issues with event guid that they cannot see without sudo...
            $this->busy_em[$member->uid][] = $event->guid;
        }
    }

    /**
     * Fills $this->participants and $this->resources
     */
    private function _get_em()
    {
        if (!$this->id)
        {
            return;
        }

        //Create shorthand references to the arrays wanted
        $part =& $this->participants;
        $res =& $this->resources;

        //Reset to empty arrays
        $res = array();
        $part = array();

        // Participants
        $mc = org_openpsa_calendar_event_member_dba::new_collector('eid', $this->id);
        $mc->add_value_property('uid');
        $mc->execute();
        $ret = $mc->list_keys();

        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $guid =>$member)
            {
                $part[$mc->get_subkey($guid, 'uid')] = true;
            }
        }
        // Resources
        $mc2 = org_openpsa_calendar_event_resource_dba::new_collector('event', $this->id);
        $mc2->add_value_property('resource');
        $mc2->execute();
        $ret = $mc2->list_keys();

        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $guid =>$member)
            {
                $res[$mc2->get_subkey($guid, 'resource')] = true;
            }
        }

        return true;
    }

    /**
     * gets person object from database id
     */
    private static function _pid_to_obj($pid)
    {
        return new midcom_db_person($pid);
    }

    /**
     *
     */
    private static function _pid_to_obj_cached($pid)
    {
        if (   !isset($GLOBALS['org_openpsa_event_pid_cache'])
            || !is_array($GLOBALS['org_openpsa_event_pid_cache']))
        {
            $GLOBALS['org_openpsa_event_pid_cache'] = array();
        }
        if (!isset($GLOBALS['org_openpsa_event_pid_cache'][$pid]))
        {
            $GLOBALS['org_openpsa_event_pid_cache'][$pid] = self::_pid_to_obj($pid);
        }
        return $GLOBALS['org_openpsa_event_pid_cache'][$pid];
    }

    /**
     * Returns a string describing $this->start - $this->end
     */
    function format_timeframe()
    {
        $startday = strftime('%a %x', $this->start);
        $endday = strftime('%a %x', $this->end);
        $starttime = strftime('%H:%m', $this->start);
        $endtime = strftime('%H:%m %Z', $this->end);

        $ret = $startday;
        if ($startday == $endday)
        {
            $ret .= ',';
        }

        $ret .= ' ' . $starttime . ' - ';

        if ($startday != $endday)
        {
            $ret .= $endday . ' ';
        }
        $ret .= $endtime;

        return $ret;
    }

    /**
     * Returns a string describing the event and its participants
     */
    function details_text($display_title = true, $member = false, $nl = "\n")
    {
        $l10n = $_MIDCOM->i18n->get_l10n('org.openpsa.calendar');
        $str = '';
        if ($display_title)
        {
            $str .= sprintf($l10n->get('title: %s') . $nl, $this->title);
        }
        $str .= sprintf($l10n->get('location: %s') . $nl, $this->location);
        $str .= sprintf($l10n->get('time: %s') . $nl, $this->format_timeframe());
        $str .= sprintf($l10n->get('participants: %s') . $nl, $this->implode_members($this->participants));
        //Not supported yet
        //$str .= sprintf($l10n->get('resources: %s') . $nl, $this->implode_members($this->resources));
        //TODO: Tentative, overlaps, public
        $str .= sprintf($l10n->get('description: %s') . $nl, $this->description);
        return $str;
    }

    /**
     * Returns a comma separated list of persons from array
     */
    function implode_members($array)
    {
        if (!is_array($array))
        {
            debug_add('input was not an array, aborting', MIDCOM_LOG_ERROR);
            return false;
        }
        $str = '';
        reset($array);
        $cnt = count($array) - 1;
        $i = 0;
        foreach ($array as $pid => $bool)
        {
            $person = org_openpsa_contacts_person_dba::get_cached($pid);
            debug_add('pid: ' . $pid . ', person->id: ' . $person->id . ', person->firstname: ' . $person->firstname . ', person->lastname: ' . $person->lastname . ', person->name: ' . $person->name . ', person->rname: ' . $person->rname);
            $str .= $person->name;
            if ($i != $cnt)
            {
                $str .= ', ';
            }
            $i++;
        }
        return $str;
    }

    /**
     * Method for exporting event in vCalendar format
     *
     * @param string newline format, defaults to \r\n
     * @param array compatibility options to override
     * @return string vCalendar data
     */
    function vcal_export($nl = "\r\n", $compatibility = array())
    {
        $encoder = new org_openpsa_helpers_vxparser();
        $encoder->merge_compatibility($compatibility);

        // Simple key/value pairs, for multiple occurrences of same key use array as value
        $vcal_keys = array();
        // For extended key data, like charset
        $vcal_key_parameters = array();

        // TODO: handle UID smarter
        $vcal_keys['UID'] = "{$this->guid}-midgardGuid";

        $revised = $this->metadata->revised;
        $created = $this->metadata->created;

        $vcal_keys['LAST-MODIFIED'] = $encoder->vcal_stamp($revised, array('TZID' => 'UTC')) . 'Z';
        $vcal_keys['CREATED'] = $encoder->vcal_stamp($created, array('TZID' => 'UTC')) . 'Z';
        /**
         * The real meaning of the DTSTAMP is fuzzy at best
         * http://www.kanzaki.com/docs/ical/dtstamp.html is less than helpful
         * http://lists.osafoundation.org/pipermail/ietf-calsify/2007-July/001750.html
         * seems to suggest that using the revision would be best
         */
        $vcal_keys['DTSTAMP'] =& $vcal_keys['LAST-MODIFIED'];
        // Type handling
        switch ($this->orgOpenpsaAccesstype)
        {
            case ORG_OPENPSA_ACCESSTYPE_PUBLIC:
                $vcal_keys['CLASS'] = 'PUBLIC';
                break;
            default:
            case ORG_OPENPSA_ACCESSTYPE_PRIVATE:
                $vcal_keys['CLASS'] = 'PRIVATE';
                break;
        }
        // "busy" or "transparency" as vCalendar calls it
        if ($this->busy)
        {
            $vcal_keys['TRANSP'] = 'OPAQUE';
        }
        else
        {
            $vcal_keys['TRANSP'] = 'TRANSPARENT';
        }
        // tentative vs confirmed
        $vcal_keys['STATUS'] = 'CONFIRMED';
        // we don't categorize events, at least yet
        $vcal_keys['CATEGORIES'] = 'MEETING';
        // we don't handle priorities
        $vcal_keys['PRIORITY'] = 1;
        // Basic fields
        $vcal_keys['SUMMARY'] = $encoder->escape_separators($this->title);
        $vcal_keys['DESCRIPTION'] = $encoder->escape_separators($this->description);
        $vcal_keys['LOCATION'] = $encoder->escape_separators($this->location);
        // Start & End in UTC
        $vcal_keys['DTSTART'] = $encoder->vcal_stamp($this->start, array('TZID' => 'UTC')) . 'Z';
        $vcal_keys['DTEND'] = $encoder->vcal_stamp($this->end, array('TZID' => 'UTC')) . 'Z';
        // Participants
        $vcal_keys['ATTENDEE'] = array();
        $vcal_key_parameters['ATTENDEE'] = array();
        // Safety, otherwise the notice will make output invalid
        if (!is_array($this->participants))
        {
            $this->participants = array();
        }
        foreach ($this->participants as $uid => $bool)
        {
            // Just a safety
            if (!$bool)
            {
                continue;
            }
            $person = self::_pid_to_obj_cached($uid);
            if (empty($person->email))
            {
                // Attendee must have email address of valid format, these must also be unique.
                $person->email = preg_replace('/[^0-9_\x61-\x7a]/i', '_', strtolower($person->name)) . '_is_not@openpsa.org';
            }
            $vcal_keys['ATTENDEE'][] = "mailto:{$person->email}";
            $vcal_key_parameters['ATTENDEE'][] = array
            (
                'ROLE' => 'REQ-PARTICIPANT',
                'CUTYPE' => 'INDIVIDUAL',
                'STATUS' => 'ACCEPTED',
                'CN' => $encoder->escape_separators($person->rname, true),
            );
        }
        $ret = "BEGIN:VEVENT{$nl}";
        $ret .= $encoder->export_vx_variables_recursive($vcal_keys, $vcal_key_parameters, false, $nl);
        $ret .= "END:VEVENT{$nl}";
        return $ret;
    }

    /**
     * Method for getting correct vcal file headers
     *
     * @param string method vCalendar method (defaults to "publish")
     * @param string newline format, defaults to \r\n
     * @return string vCalendar data
     */
    function vcal_headers($method="publish", $nl="\r\n")
    {
        $method = strtoupper($method);
        $ret = '';
        $ret .= "BEGIN:VCALENDAR{$nl}";
        $ret .= "VERSION:2.0{$nl}";
        $ret .= "PRODID:-//Nemein/OpenPSA2 Calendar V2.0.0//EN{$nl}";
        $ret .= "METHOD:{$method}{$nl}";
        //TODO: Determine server timezone and output correct header (we still send all times as UTC)
        return $ret;
    }

    /**
     * Method for getting correct vcal file footers
     *
     * @param string newline format, defaults to \r\n
     * @return string vCalendar data
     */
    function vcal_footers($nl="\r\n")
    {
        $ret = '';
        $ret .= "END:VCALENDAR{$nl}";
        return $ret;
    }
}
?>