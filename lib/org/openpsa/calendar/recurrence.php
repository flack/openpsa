<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;
use Sabre\VObject\Recur\EventIterator;
use Sabre\VObject\Recur\MaxInstancesExceededException;
use Sabre\VObject\Recur\NoInstancesException;

/**
 * Recurring events
 *
 * A series is stored as one event per occurrence: The master holds the rule (iCal RRULE/RDATE/EXDATE
 * lines in $recurrence) and is also the first occurrence, the other occurrences are its children
 * (up = master). They are generated up to a horizon, which is extended by a cron job.
 *
 * Occurrences copy their fields, participants and resources from the master, unless they were
 * edited on their own ($recurrenceDetached). Occurrences that have ended are never changed, since
 * they may already have hour reports linked to them.
 *
 * Each occurrence is checked for conflicts on its own: Busy participants are left out of that
 * occurrence, and if a resource is already booked or no participant is left, the date is skipped
 * (added as EXDATE to the rule).
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_recurrence
{
    /**
     * IDs of the masters currently being synced (to avoid recursion)
     */
    private static array $running = [];

    /**
     * IDs of the occurrences generated in this request. They aren't history yet even if they're
     * in the past (e.g. when a series is created, its members are only added after the master)
     */
    private static array $generated = [];

    /**
     * Dates skipped because of conflicts, as master ID => list of start timestamps
     */
    public array $skipped = [];

    /**
     * Participants left out because they were busy, as master ID => [start timestamp => person IDs]
     */
    public array $left_out = [];

    public static function get_horizon() : int
    {
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.calendar', 'config');
        return strtotime($config->get('recurrence_horizon')) ?: strtotime('+3 months');
    }

    public static function get_timezone(org_openpsa_calendar_event_dba $event) : DateTimeZone
    {
        try {
            return new DateTimeZone($event->timezone ?: date_default_timezone_get());
        } catch (Exception) {
            return new DateTimeZone(date_default_timezone_get());
        }
    }

    /**
     * Normalizes the stored rule to unfolded RRULE, RDATE and EXDATE lines
     *
     * @return list<string>
     */
    public static function get_lines(?string $recurrence) : array
    {
        $recurrence = preg_replace("/\r?\n[ \t]/", '', (string) $recurrence);
        $lines = array_map(trim(...), preg_split("/\r\n|\n|\r/", $recurrence));
        return array_values(array_filter($lines, fn($line) => preg_match('/^(RRULE|RDATE|EXDATE)[;:]/i', $line)));
    }

    /**
     * Start (rounded as in the event's save routine) of the occurrences that end after $from and start until $to
     *
     * @return int[]
     */
    public function expand(org_openpsa_calendar_event_dba $master, int $from, int $to) : array
    {
        try {
            $iterator = $this->get_iterator($master);
        } catch (NoInstancesException) {
            return [];
        }
        $iterator->fastForward(new DateTimeImmutable('@' . $from));
        $result = [];
        try {
            while ($iterator->valid()) {
                $start = $iterator->getDtStart()->getTimestamp();
                if ($start > $to) {
                    break;
                }
                $result[] = $start - ($start % 60) + 1;
                $iterator->next();
            }
        } catch (MaxInstancesExceededException $e) {
            debug_add('Stopping expansion of event #' . $master->id . ': ' . $e->getMessage(), MIDCOM_LOG_WARN);
        }
        return $result;
    }

    /**
     * End of the last occurrence, 0 if the series doesn't end
     *
     * @throws Exception If the rule can't be parsed
     */
    public function get_until(org_openpsa_calendar_event_dba $master) : int
    {
        try {
            $iterator = $this->get_iterator($master);
        } catch (NoInstancesException) {
            return $master->end;
        }
        if ($iterator->isInfinite()) {
            return 0;
        }
        $last = null;
        try {
            while ($iterator->valid()) {
                $last = $iterator->getDtStart()->getTimestamp();
                $iterator->next();
            }
        } catch (MaxInstancesExceededException) {
            return 0;
        }
        if ($last === null) {
            return $master->end;
        }
        return $last - ($last % 60) + 1 + $this->get_duration($master);
    }

    private function get_duration(org_openpsa_calendar_event_dba $master) : int
    {
        return max(0, $master->end - $master->start);
    }

    private function get_iterator(org_openpsa_calendar_event_dba $master) : EventIterator
    {
        $tz = self::get_timezone($master);
        $anchor = $master->recurrenceId ?: $master->start;
        $start = (new DateTimeImmutable('@' . ($anchor - ($anchor % 60))))->setTimezone($tz);
        $end = $start->modify('+' . ($this->get_duration($master) + 1) . ' seconds');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'BEGIN:VEVENT',
            'UID:openpsa-recurrence',
            $this->format_time('DTSTART', $start),
            $this->format_time('DTEND', $end),
            ...self::get_lines($master->recurrence),
            'END:VEVENT',
            'END:VCALENDAR'
        ];
        $vcalendar = Reader::read(implode("\r\n", $lines) . "\r\n");
        if (!$vcalendar instanceof VCalendar) {
            throw new UnexpectedValueException('Could not parse recurrence');
        }
        return new EventIterator($vcalendar->VEVENT, null, $tz);
    }

    private function format_time(string $name, DateTimeImmutable $time) : string
    {
        static $identifiers;
        $identifiers ??= array_flip(DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC));
        $tzid = $time->getTimezone()->getName();
        if ($tzid === 'UTC' || !isset($identifiers[$tzid])) {
            return $name . ':' . $time->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        }
        return $name . ';TZID=' . $tzid . ':' . $time->format('Ymd\THis');
    }

    /**
     * Whether the series consists of all-day events (as stored by vcal: 00:00:01 to 23:59:00 local time)
     */
    private function is_all_day(org_openpsa_calendar_event_dba $master) : bool
    {
        $tz = self::get_timezone($master);
        $start = (new DateTimeImmutable('@' . $master->start))->setTimezone($tz);
        $end = (new DateTimeImmutable('@' . $master->end))->setTimezone($tz);
        return $start->format('H:i') === '00:00' && $end->format('H:i') === '23:59';
    }

    /**
     * Generates occurrences up to $until (if that is further than before)
     */
    public function extend(org_openpsa_calendar_event_dba $master, int $until) : bool
    {
        if (   !$master->is_master()
            || $until <= $master->materializedUntil) {
            return false;
        }
        $master->materializedUntil = $until;
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $stat = $this->save_quietly($master);
        midcom::get()->auth->drop_sudo();
        if ($stat) {
            $this->sync($master);
        }
        return $stat;
    }

    /**
     * Brings the occurrences in line with the master
     */
    public function sync(org_openpsa_calendar_event_dba $master)
    {
        if (   !$master->id
            || !$master->is_master()
            || isset(self::$running[$master->id])) {
            return;
        }
        self::$running[$master->id] = true;
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        try {
            $this->do_sync($master);
        } finally {
            midcom::get()->auth->drop_sudo();
            unset(self::$running[$master->id]);
        }
    }

    private function do_sync(org_openpsa_calendar_event_dba $master)
    {
        $now = time();
        $duration = $this->get_duration($master);
        $tz = self::get_timezone($master);
        $existing = $this->get_occurrences($master);

        $limit = $master->materializedUntil;
        if ($master->recurrenceUntil) {
            $limit = min($limit, $master->recurrenceUntil);
        }
        // Occurrences that have ended are left alone, they may have hour reports already
        $is_frozen = fn(org_openpsa_calendar_event_dba $occurrence) => $occurrence->end <= $now && !isset(self::$generated[$occurrence->id]);
        $first = empty($existing);
        $by_id = $unmatched = [];
        $from = $now;
        foreach ($existing as $occurrence) {
            $by_id[$occurrence->recurrenceId] = $occurrence;
            if (!$is_frozen($occurrence)) {
                $from = min($from, $occurrence->start);
            }
        }
        if ($first) {
            $from = $master->start;
        }
        $instances = array_diff($this->expand($master, $from, $limit), [$master->recurrenceId]);

        $participants = $this->get_member_ids($master->id);
        $resources = $this->get_resource_ids($master->id);
        $exdates = [];
        $keep = [];

        // Instances whose start changed (e.g. a new time of day) take over the old occurrence of the same day
        $missing = array_diff($instances, array_keys($by_id));
        foreach ($existing as $occurrence) {
            if (   !in_array($occurrence->recurrenceId, $instances)
                && !$is_frozen($occurrence)
                && !$occurrence->recurrenceDetached) {
                $unmatched[$this->get_date($occurrence->recurrenceId, $tz)][] = $occurrence;
            }
        }

        foreach ($instances as $recurrence_id) {
            $occurrence = $by_id[$recurrence_id] ?? null;
            if (!$occurrence && in_array($recurrence_id, $missing)) {
                $date = $this->get_date($recurrence_id, $tz);
                if (!empty($unmatched[$date])) {
                    $occurrence = array_shift($unmatched[$date]);
                }
            }
            if ($occurrence) {
                $keep[$occurrence->id] = true;
                if ($is_frozen($occurrence) || $occurrence->recurrenceDetached) {
                    continue;
                }
            } elseif (!$first && $recurrence_id + $duration <= $now) {
                // Only new series get occurrences in the past
                continue;
            }
            if (!$this->write_occurrence($master, $occurrence, $recurrence_id, $duration, $participants, $resources)) {
                $exdates[] = $recurrence_id;
                if ($occurrence) {
                    $this->delete_quietly($occurrence);
                }
            }
        }

        // Occurrences that no longer belong to the series
        foreach ($existing as $occurrence) {
            if (   !isset($keep[$occurrence->id])
                && !$is_frozen($occurrence)
                && (   !$occurrence->recurrenceDetached
                    || !$this->is_instance($master, $occurrence->recurrenceId))) {
                $this->delete_quietly($occurrence);
            }
        }

        if ($exdates) {
            $this->add_exdates($master, $exdates);
        }
    }

    private function get_date(int $timestamp, DateTimeZone $tz) : string
    {
        return (new DateTimeImmutable('@' . $timestamp))->setTimezone($tz)->format('Y-m-d');
    }

    private function is_instance(org_openpsa_calendar_event_dba $master, int $recurrence_id) : bool
    {
        return $recurrence_id === $master->recurrenceId
            || in_array($recurrence_id, $this->expand($master, $recurrence_id - 60, $recurrence_id));
    }

    /**
     * Creates or updates an occurrence
     *
     * @return bool False if the date has to be skipped because of conflicts
     */
    private function write_occurrence(org_openpsa_calendar_event_dba $master, ?org_openpsa_calendar_event_dba $occurrence, int $recurrence_id, int $duration, array $participants, array $resources) : bool
    {
        $new = $occurrence === null;
        $occurrence ??= new org_openpsa_calendar_event_dba;
        $changed = $new;
        $values = [
            'up' => $master->id,
            'recurrenceId' => $recurrence_id,
            'start' => $recurrence_id,
            'end' => $recurrence_id + $duration,
            'title' => $master->title,
            'description' => $master->description,
            'location' => $master->location,
            'type' => $master->type,
            'busy' => $master->busy,
            'tentative' => $master->tentative,
            'orgOpenpsaAccesstype' => $master->orgOpenpsaAccesstype
        ];
        $times_changed = $new || $occurrence->start != $values['start'] || $occurrence->end != $values['end'];
        foreach ($values as $field => $value) {
            if ($occurrence->$field != $value) {
                $occurrence->$field = $value;
                $changed = true;
            }
        }
        $current_participants = $new ? [] : $this->get_member_ids($occurrence->id);
        $current_resources = $new ? [] : $this->get_resource_ids($occurrence->id);
        $members_changed = $current_participants != $participants || $current_resources != $resources;

        if (!$changed && !$members_changed) {
            return true;
        }

        $occurrence->participants = array_fill_keys($participants, true);
        $occurrence->resources = array_fill_keys($resources, true);
        if (   ($times_changed || $members_changed)
            && !$this->resolve_conflicts($master, $occurrence)) {
            return false;
        }

        // Saving reloads the lists from the database
        $target_participants = array_keys($occurrence->participants);
        $target_resources = array_keys($occurrence->resources);
        if ($new) {
            if ($stat = $this->create_quietly($occurrence)) {
                self::$generated[$occurrence->id] = true;
            }
        } else {
            $stat = $this->save_quietly($occurrence);
        }
        if (!$stat) {
            debug_add('Could not save occurrence of event #' . $master->id . ': ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return true;
        }
        $this->sync_members($occurrence, $current_participants, $target_participants);
        $this->sync_resources($occurrence, $current_resources, $target_resources);
        return true;
    }

    /**
     * Leaves busy participants out of the occurrence
     *
     * @return bool False if the date has to be skipped
     */
    private function resolve_conflicts(org_openpsa_calendar_event_dba $master, org_openpsa_calendar_event_dba $occurrence) : bool
    {
        if (!$occurrence->busy) {
            return true;
        }
        $conflictmanager = new org_openpsa_calendar_conflictmanager($occurrence);
        if ($conflictmanager->run($master->rob_tentative)) {
            return true;
        }
        if (!empty($conflictmanager->busy_resources)) {
            debug_add('Skipping occurrence of event #' . $master->id . ' at ' . date('c', $occurrence->start) . ', resources are booked');
            $this->skipped[$master->id][] = $occurrence->start;
            return false;
        }
        $had_participants = !empty($occurrence->participants);
        $busy = array_keys($conflictmanager->busy_members);
        $occurrence->participants = array_diff_key($occurrence->participants, array_flip($busy));
        $occurrence->recurrenceDetached = true;
        if ($had_participants && empty($occurrence->participants)) {
            debug_add('Skipping occurrence of event #' . $master->id . ' at ' . date('c', $occurrence->start) . ', all participants are busy');
            $this->skipped[$master->id][] = $occurrence->start;
            return false;
        }
        $this->left_out[$master->id][$occurrence->start] = $busy;
        // Run again, so that resources can be taken from tentative events
        if (!$conflictmanager->run($master->rob_tentative)) {
            $this->skipped[$master->id][] = $occurrence->start;
            return false;
        }
        return true;
    }

    private function add_exdates(org_openpsa_calendar_event_dba $master, array $dates)
    {
        $tz = self::get_timezone($master);
        $all_day = $this->is_all_day($master);
        $lines = self::get_lines($master->recurrence);
        foreach ($dates as $date) {
            $time = (new DateTimeImmutable('@' . ($date - ($date % 60))))->setTimezone($tz);
            if ($all_day) {
                $lines[] = 'EXDATE;VALUE=DATE:' . $time->format('Ymd');
            } else {
                $lines[] = $this->format_time('EXDATE', $time);
            }
        }
        $master->recurrence = implode("\n", $lines);
        if (!$this->save_quietly($master)) {
            debug_add('Could not add EXDATEs to event #' . $master->id . ': ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
        }
    }

    /**
     * Removes the person from the series: From the master and all occurrences that haven't ended
     */
    public function leave(org_openpsa_calendar_event_dba $master, int $person_id, bool $notify = true)
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->add_constraint('uid', '=', $person_id);
        $qb->begin_group('OR');
            $qb->add_constraint('eid', '=', $master->id);
            $qb->begin_group('AND');
                $qb->add_constraint('eid.up', '=', $master->id);
                $qb->add_constraint('eid.end', '>', time());
            $qb->end_group();
        $qb->end_group();
        foreach ($qb->execute() as $member) {
            $member->notify_person = $notify && $member->eid == $master->id;
            $member->sync_series = false;
            $member->delete();
        }
        midcom::get()->auth->drop_sudo();
    }

    /**
     * Deletes all occurrences (when the master is deleted)
     */
    public function delete_occurrences(org_openpsa_calendar_event_dba $master) : bool
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $stat = true;
        foreach ($this->get_occurrences($master) as $occurrence) {
            $stat = $this->delete_quietly($occurrence) && $stat;
        }
        midcom::get()->auth->drop_sudo();
        return $stat;
    }

    /**
     * @return org_openpsa_calendar_event_dba[]
     */
    private function get_occurrences(org_openpsa_calendar_event_dba $master) : array
    {
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('up', '=', $master->id);
        $qb->add_order('start');
        return $qb->execute();
    }

    /**
     * @return int[] sorted
     */
    private function get_member_ids(int $event_id) : array
    {
        $mc = org_openpsa_calendar_event_member_dba::new_collector('eid', $event_id);
        $ids = array_unique(array_map(intval(...), $mc->get_values('uid')));
        sort($ids);
        return $ids;
    }

    /**
     * @return int[] sorted
     */
    private function get_resource_ids(int $event_id) : array
    {
        $mc = org_openpsa_calendar_event_resource_dba::new_collector('event', $event_id);
        $ids = array_unique(array_map(intval(...), $mc->get_values('resource')));
        sort($ids);
        return $ids;
    }

    private function sync_members(org_openpsa_calendar_event_dba $occurrence, array $current, array $target)
    {
        if ($remove = array_diff($current, $target)) {
            $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
            $qb->add_constraint('eid', '=', $occurrence->id);
            $qb->add_constraint('uid', 'IN', $remove);
            foreach ($qb->execute() as $member) {
                $member->notify_person = false;
                $member->sync_series = false;
                $member->delete();
            }
        }
        foreach (array_diff($target, $current) as $uid) {
            $member = new org_openpsa_calendar_event_member_dba;
            $member->eid = $occurrence->id;
            $member->uid = $uid;
            $member->notify_person = false;
            $member->sync_series = false;
            if (!$member->create()) {
                debug_add("Could not add person #{$uid} to occurrence #{$occurrence->id}: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }
    }

    private function sync_resources(org_openpsa_calendar_event_dba $occurrence, array $current, array $target)
    {
        if ($remove = array_diff($current, $target)) {
            $qb = org_openpsa_calendar_event_resource_dba::new_query_builder();
            $qb->add_constraint('event', '=', $occurrence->id);
            $qb->add_constraint('resource', 'IN', $remove);
            foreach ($qb->execute() as $resource) {
                $resource->sync_series = false;
                $resource->delete();
            }
        }
        foreach (array_diff($target, $current) as $id) {
            $resource = new org_openpsa_calendar_event_resource_dba;
            $resource->event = $occurrence->id;
            $resource->resource = $id;
            $resource->sync_series = false;
            if (!$resource->create()) {
                debug_add("Could not add resource #{$id} to occurrence #{$occurrence->id}: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }
    }

    private function create_quietly(org_openpsa_calendar_event_dba $event) : bool
    {
        return $this->quietly($event, $event->create(...));
    }

    private function save_quietly(org_openpsa_calendar_event_dba $event) : bool
    {
        return $this->quietly($event, $event->update(...));
    }

    private function delete_quietly(org_openpsa_calendar_event_dba $event) : bool
    {
        return $this->quietly($event, $event->delete(...));
    }

    /**
     * Saves without notifications, relatedto search, conflict check (already done) or another sync
     */
    private function quietly(org_openpsa_calendar_event_dba $event, callable $operation) : bool
    {
        $flags = [
            'send_notify' => false,
            'search_relatedtos' => false,
            'ignorebusy_em' => true,
            'sync_recurrence' => false
        ];
        if ($event->is_occurrence() || !$event->id) {
            $flags['_use_rcs'] = false;
        }
        $previous = [];
        foreach ($flags as $flag => $value) {
            $previous[$flag] = $event->$flag;
            $event->$flag = $value;
        }
        try {
            return $operation();
        } finally {
            foreach ($previous as $flag => $value) {
                $event->$flag = $value;
            }
        }
    }
}
