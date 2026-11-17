<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\calendar;

use openpsa_testcase;
use midcom;
use midcom_db_person;
use midcom_core_privilege;
use org_openpsa_calendar_event_dba;
use org_openpsa_calendar_event_member_dba;
use org_openpsa_calendar_event_resource_dba;
use org_openpsa_calendar_resource_dba;
use org_openpsa_calendar_recurrence;
use org_openpsa_calendar_cron_recurrence;
use org_openpsa_calendar_cron_reporthours;
use org_openpsa_core_acl;
use org_openpsa_mail_backend_unittest;
use org_openpsa_projects_project;
use org_openpsa_projects_task_dba;
use org_openpsa_projects_task_resource_dba;
use org_openpsa_relatedto_dba;
use org_openpsa_expenses_hour_report_dba;
use org_openpsa_event;
use DateTimeImmutable;
use DateTimeZone;
use ReflectionProperty;
use Symfony\Component\Console\Output\NullOutput;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class recurrenceTest extends openpsa_testcase
{
    private const TZ = 'Europe/Berlin';

    private function timestamp(string $time) : int
    {
        return (new DateTimeImmutable($time, new DateTimeZone(self::TZ)))->getTimestamp();
    }

    /**
     * Rounds like the event's save routine
     */
    private function rounded(int $start) : int
    {
        return $start - ($start % 60) + 1;
    }

    private function prepare_master(int $start, string $recurrence, int $duration = 3600) : org_openpsa_calendar_event_dba
    {
        $event = new org_openpsa_calendar_event_dba;
        $event->start = $this->rounded($start);
        $event->end = $start + $duration;
        $event->recurrence = $recurrence;
        $event->timezone = self::TZ;
        $event->recurrenceId = $event->start;
        return $event;
    }

    private function create_master(int $start, string $recurrence, array $participants = [], array $attributes = []) : org_openpsa_calendar_event_dba
    {
        $master = $this->create_object(org_openpsa_calendar_event_dba::class, $attributes + [
            'start' => $start,
            'end' => $start + 3600,
            'title' => 'Series ' . uniqid(),
            'recurrence' => $recurrence,
            'timezone' => self::TZ
        ]);
        foreach ($participants as $person) {
            $this->add_member($master, $person);
        }
        return $this->reload($master);
    }

    private function add_member(object $event, midcom_db_person $person) : org_openpsa_calendar_event_member_dba
    {
        return $this->create_object(org_openpsa_calendar_event_member_dba::class, [
            'eid' => $event->id,
            'uid' => $person->id
        ]);
    }

    private function create_event(int $start, array $participants, array $attributes = []) : org_openpsa_calendar_event_dba
    {
        $event = $this->create_object(org_openpsa_calendar_event_dba::class, $attributes + [
            'start' => $start,
            'end' => $start + 3600,
            'title' => 'Other ' . uniqid()
        ]);
        foreach ($participants as $person) {
            $this->add_member($event, $person);
        }
        return $event;
    }

    private function reload(org_openpsa_calendar_event_dba $event) : org_openpsa_calendar_event_dba
    {
        return $this->sudo(fn() => new org_openpsa_calendar_event_dba($event->id));
    }

    /**
     * @return org_openpsa_calendar_event_dba[]
     */
    private function get_occurrences(org_openpsa_calendar_event_dba $master) : array
    {
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('up', '=', $master->id);
        $qb->add_order('start');
        return $this->sudo($qb->execute(...));
    }

    private function get_member_ids(org_openpsa_calendar_event_dba $event) : array
    {
        $mc = org_openpsa_calendar_event_member_dba::new_collector('eid', $event->id);
        $ids = $this->sudo(fn() => $mc->get_values('uid'));
        sort($ids);
        return $ids;
    }

    private function create_person_with_email() : midcom_db_person
    {
        $person = $this->create_user();
        $person->email = 'recurrence-' . $person->id . '-' . uniqid() . '@openpsa.test';
        $this->sudo($person->update(...));
        return $person;
    }

    private function run_cron(string $classname)
    {
        $cron = new $classname;
        $cron->initialize(new NullOutput);
        $cron->execute();
    }

    public function test_expand()
    {
        $recurrence = new org_openpsa_calendar_recurrence;

        // Across DST: the local time stays the same
        $master = $this->prepare_master($this->timestamp('2026-10-19 10:00'), 'RRULE:FREQ=WEEKLY;COUNT=3');
        $expected = [
            $this->rounded($this->timestamp('2026-10-19 10:00')),
            $this->rounded($this->timestamp('2026-10-26 10:00')),
            $this->rounded($this->timestamp('2026-11-02 10:00'))
        ];
        $this->assertSame($expected, $recurrence->expand($master, $master->start, $master->start + 365 * 86400));
        $this->assertSame(7 * 86400 + 3600, $expected[1] - $expected[0]);
        $this->assertSame($this->timestamp('2026-11-02 11:00'), $recurrence->get_until($master));
        // Only those in the range (ending after from, starting until to)
        $this->assertSame([$expected[1]], $recurrence->expand($master, $expected[0] + 3600, $expected[1]));

        // UNTIL
        $master = $this->prepare_master($this->timestamp('2026-10-19 10:00'), 'RRULE:FREQ=DAILY;UNTIL=20261021T235959Z');
        $this->assertCount(3, $recurrence->expand($master, $master->start, $master->start + 365 * 86400));

        // Infinite
        $master = $this->prepare_master($this->timestamp('2026-10-19 10:00'), 'RRULE:FREQ=DAILY');
        $this->assertSame(0, $recurrence->get_until($master));
        $this->assertCount(10, $recurrence->expand($master, $master->start, $this->timestamp('2026-10-28 10:00')));

        // EXDATE
        $master = $this->prepare_master(
            $this->timestamp('2026-10-19 10:00'),
            "RRULE:FREQ=DAILY;COUNT=3\nEXDATE;TZID=Europe/Berlin:20261020T100000"
        );
        $this->assertSame([
            $this->rounded($this->timestamp('2026-10-19 10:00')),
            $this->rounded($this->timestamp('2026-10-21 10:00'))
        ], $recurrence->expand($master, $master->start, $master->start + 365 * 86400));

        // All-day events (as stored by the vcal import)
        $master = $this->prepare_master(
            $this->timestamp('2026-10-24 00:00'),
            "RRULE:FREQ=DAILY;COUNT=3\r\nEXDATE;VALUE=DATE:20261025",
            86400 - 60
        );
        $this->assertSame([
            $this->rounded($this->timestamp('2026-10-24 00:00')),
            $this->rounded($this->timestamp('2026-10-26 00:00'))
        ], $recurrence->expand($master, $master->start, $master->start + 365 * 86400));
    }

    public function test_invalid_rule()
    {
        $event = $this->prepare_master(time(), 'RRULE:FREQ=SOMETIMES');
        $this->assertFalse($this->sudo($event->create(...)));
    }

    public function test_horizon_and_cron()
    {
        $this->set_config('org.openpsa.calendar', 'recurrence_horizon', '+5 weeks');
        $user = $this->create_user();
        $start = $this->timestamp('monday next week 10:00');
        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY', [$user]);
        $recurrence = new org_openpsa_calendar_recurrence;

        $this->assertTrue($master->is_master());
        $this->assertSame(0, $master->recurrenceUntil);
        $this->assertSame($master->start, $master->recurrenceId);
        $this->assertSame(self::TZ, $master->timezone);
        $this->assertEqualsWithDelta(strtotime('+5 weeks'), $master->materializedUntil, 10);

        $expected = array_slice($recurrence->expand($master, $master->start, $master->materializedUntil), 1);
        $occurrences = $this->get_occurrences($master);
        $this->assertNotEmpty($occurrences);
        $this->assertSame($expected, array_column($occurrences, 'recurrenceId'));
        foreach ($occurrences as $occurrence) {
            $this->assertTrue($occurrence->is_occurrence());
            $this->assertFalse($occurrence->is_master());
            $this->assertSame($master->title, $occurrence->title);
            $this->assertSame($occurrence->recurrenceId, $occurrence->start);
            $this->assertSame($master->end - $master->start, $occurrence->end - $occurrence->start);
            $this->assertSame([$user->id], $this->get_member_ids($occurrence));
            $this->assertSame($master->guid, $occurrence->get_master()->guid);
        }

        $this->set_config('org.openpsa.calendar', 'recurrence_horizon', '+10 weeks');
        $this->run_cron(org_openpsa_calendar_cron_recurrence::class);

        $master = $this->reload($master);
        $this->assertEqualsWithDelta(strtotime('+10 weeks'), $master->materializedUntil, 10);
        $expected = array_slice($recurrence->expand($master, $master->start, $master->materializedUntil), 1);
        $extended = $this->get_occurrences($master);
        $this->assertSame($expected, array_column($extended, 'recurrenceId'));
        $this->assertGreaterThan(count($occurrences), count($extended));
        // Existing occurrences stay
        $guids = array_column($occurrences, 'guid');
        $this->assertSame($guids, array_slice(array_column($extended, 'guid'), 0, count($guids)));
    }

    public function test_finite_series()
    {
        $start = $this->timestamp('monday next week 10:00');
        $master = $this->create_master($start, 'RRULE:FREQ=DAILY;COUNT=3');
        $this->assertSame($this->timestamp('wednesday next week 11:00'), $master->recurrenceUntil);
        $this->assertCount(2, $this->get_occurrences($master));
    }

    public function test_cron_finite_series()
    {
        $this->set_config('org.openpsa.calendar', 'recurrence_horizon', '+5 weeks');
        $start = $this->timestamp('monday next week 10:00');
        // Continues beyond the horizon
        $long = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=20');
        $this->assertGreaterThan($long->materializedUntil, $long->recurrenceUntil);
        // Ends within the horizon
        $short = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=2');
        $this->assertLessThanOrEqual($short->materializedUntil, $short->recurrenceUntil);
        $count = count($this->get_occurrences($long));

        $this->set_config('org.openpsa.calendar', 'recurrence_horizon', '+10 weeks');
        $this->run_cron(org_openpsa_calendar_cron_recurrence::class);

        $long = $this->reload($long);
        $this->assertEqualsWithDelta(strtotime('+10 weeks'), $long->materializedUntil, 10);
        $this->assertGreaterThan($count, count($this->get_occurrences($long)));
        // Already complete, so the cron doesn't touch it
        $this->assertSame($short->materializedUntil, $this->reload($short)->materializedUntil);
        $this->assertCount(1, $this->get_occurrences($short));
    }

    public function test_master_change()
    {
        $user = $this->create_user();
        $start = $this->timestamp('monday next week 10:00');
        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=4', [$user]);
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(3, $occurrences);

        $detached = $occurrences[1];
        $detached->title = 'Special';
        $detached->recurrenceDetached = true;
        $detached->send_notify = false;
        $this->assertTrue($this->sudo($detached->update(...)));

        $master->title = 'Changed';
        $master->location = 'Somewhere';
        $this->assertTrue($this->sudo($master->update(...)));

        $occurrences = $this->get_occurrences($master);
        $this->assertCount(3, $occurrences);
        $this->assertSame(['Changed', 'Special', 'Changed'], array_column($occurrences, 'title'));
        $this->assertSame('Somewhere', $occurrences[2]->location);

        // New time of day: same occurrences (by day), new times
        $guids = array_column($occurrences, 'guid');
        $master = $this->reload($master);
        $master->start += 3600;
        $master->end += 3600;
        $this->assertTrue($this->sudo($master->update(...)));
        $master = $this->reload($master);
        $this->assertSame($master->start, $master->recurrenceId);

        // The detached occurrence's instance no longer exists, so it is replaced
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(3, $occurrences);
        $this->assertSame($guids[0], $occurrences[0]->guid);
        $this->assertNotSame($guids[1], $occurrences[1]->guid);
        $this->assertSame('Changed', $occurrences[1]->title);
        $this->assertSame($guids[2], $occurrences[2]->guid);
        $this->assertSame($this->rounded($this->timestamp('monday next week 11:00 +3 weeks')), $occurrences[2]->start);

        // A shorter series
        $master->recurrence = 'RRULE:FREQ=WEEKLY;COUNT=2';
        $this->assertTrue($this->sudo($master->update(...)));
        $this->assertCount(1, $this->get_occurrences($master));
    }

    public function test_past_occurrences()
    {
        $start = strtotime('today 10:00') - 3 * 86400;
        $master = $this->create_master($start, 'RRULE:FREQ=DAILY;COUNT=3', [], ['title' => 'Old']);
        // New series get their past occurrences
        $this->assertCount(2, $this->get_occurrences($master));

        // Simulate a later request (occurrences generated in the current one are not history yet)
        (new ReflectionProperty(org_openpsa_calendar_recurrence::class, 'generated'))->setValue(null, []);

        $master->title = 'New';
        $master->recurrence = 'RRULE:FREQ=DAILY;COUNT=2';
        $this->assertTrue($this->sudo($master->update(...)));
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(2, $occurrences);
        $this->assertSame(['Old', 'Old'], array_column($occurrences, 'title'));
    }

    public function test_members()
    {
        $user = $this->create_user();
        $user2 = $this->create_user();
        $start = $this->timestamp('monday next week 10:00');
        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=3', [$user]);

        $this->add_member($master, $user2);
        $expected = [$user->id, $user2->id];
        sort($expected);
        foreach ($this->get_occurrences($master) as $occurrence) {
            $this->assertSame($expected, $this->get_member_ids($occurrence));
        }

        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->add_constraint('eid', '=', $master->id);
        $qb->add_constraint('uid', '=', $user->id);
        $members = $this->sudo($qb->execute(...));
        $this->assertTrue($this->sudo($members[0]->delete(...)));
        foreach ($this->get_occurrences($master) as $occurrence) {
            $this->assertSame([$user2->id], $this->get_member_ids($occurrence));
        }
    }

    public function test_resources()
    {
        $resource = $this->create_object(org_openpsa_calendar_resource_dba::class, [
            'title' => 'Room ' . uniqid()
        ]);
        $start = $this->timestamp('monday next week 10:00');
        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=3', [$this->create_user()]);
        $this->create_object(org_openpsa_calendar_event_resource_dba::class, [
            'event' => $master->id,
            'resource' => $resource->id
        ]);

        foreach ($this->get_occurrences($master) as $occurrence) {
            $this->assertSame([$resource->id => true], $occurrence->resources);
        }
    }

    public function test_leave()
    {
        $user = $this->create_user();
        $user2 = $this->create_user();
        $start = $this->timestamp('monday next week 10:00');
        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=3', [$user, $user2]);

        (new org_openpsa_calendar_recurrence)->leave($master, $user2->id);
        $this->assertSame([$user->id], $this->get_member_ids($master));
        foreach ($this->get_occurrences($master) as $occurrence) {
            $this->assertSame([$user->id], $this->get_member_ids($occurrence));
        }
    }

    public function test_privileges()
    {
        $user = $this->create_user();
        $other = $this->create_user();
        $start = $this->timestamp('monday next week 10:00');
        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=2', [$user], [
            'orgOpenpsaAccesstype' => org_openpsa_core_acl::ACCESS_PRIVATE
        ]);
        // Participants get their privileges when the event is updated (as the CalDAV backend does)
        $master->send_notify = false;
        $this->assertTrue($this->sudo($master->update(...)));

        $occurrence = $this->get_occurrences($master)[0];
        $auth = midcom::get()->auth;
        // Nothing is set on the occurrence, it inherits from the master
        $assignees = array_column(midcom_core_privilege::get_all_privileges($occurrence->guid), 'assignee');
        $this->assertNotContains($auth->get_user($user->guid)->id, $assignees);
        $this->assertNotContains('EVERYONE', $assignees);
        $this->assertTrue($occurrence->can_do('org.openpsa.calendar:read', $auth->get_user($user->guid)));
        $this->assertTrue($occurrence->can_do('midgard:update', $auth->get_user($user->guid)));
        $this->assertFalse($occurrence->can_do('org.openpsa.calendar:read', $auth->get_user($other->guid)));
    }

    public function test_notifications()
    {
        $user = $this->create_person_with_email();
        org_openpsa_mail_backend_unittest::flush();

        $start = $this->timestamp('monday next week 10:00');
        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=4', [$user]);
        $this->assertCount(3, $this->get_occurrences($master));
        // One "add" for the series
        $this->assertCount(1, org_openpsa_mail_backend_unittest::flush());

        $master->title = 'Changed';
        $this->assertTrue($this->sudo($master->update(...)));
        $this->assertSame('Changed', $this->get_occurrences($master)[0]->title);
        // One "update" for the series
        $this->assertCount(1, org_openpsa_mail_backend_unittest::flush());

        $this->assertTrue($this->sudo($master->delete(...)));
        // One "cancel" for the series
        $this->assertCount(1, org_openpsa_mail_backend_unittest::flush());
    }

    public function test_delete()
    {
        $user = $this->create_user();
        $start = $this->timestamp('monday next week 10:00');
        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=3', [$user]);
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(2, $occurrences);

        $master->_use_rcs = false;
        $this->assertTrue($this->sudo($master->delete(...)));
        $this->assertSame([], $this->get_occurrences($master));
        $mc = org_openpsa_calendar_event_member_dba::new_collector();
        $mc->add_constraint('eid', 'IN', array_column($occurrences, 'id'));
        $this->assertSame([], $this->sudo(fn() => $mc->get_values('uid')));
    }

    public function test_conflict_participant()
    {
        $user = $this->create_user();
        $busy = $this->create_user();
        $start = $this->timestamp('monday next week 10:00');
        $this->create_event($this->timestamp('monday next week 10:30 +1 week'), [$busy], ['busy' => true]);

        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=3', [$user, $busy], ['busy' => true]);
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(2, $occurrences);

        // The busy participant is left out of that occurrence only
        $this->assertSame([$user->id], $this->get_member_ids($occurrences[0]));
        $this->assertTrue($occurrences[0]->recurrenceDetached);
        $expected = [$user->id, $busy->id];
        sort($expected);
        $this->assertSame($expected, $this->get_member_ids($occurrences[1]));
        $this->assertFalse($occurrences[1]->recurrenceDetached);
        $this->assertStringNotContainsString('EXDATE', $this->reload($master)->recurrence);
    }

    public function test_conflict_resource()
    {
        $user = $this->create_user();
        $resource = $this->create_object(org_openpsa_calendar_resource_dba::class, [
            'title' => 'Room ' . uniqid()
        ]);
        $start = $this->timestamp('monday next week 10:00');
        $other = $this->create_event($this->timestamp('monday next week 10:00 +1 week'), [], ['busy' => true]);
        $this->create_object(org_openpsa_calendar_event_resource_dba::class, [
            'event' => $other->id,
            'resource' => $resource->id
        ]);

        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=3', [$user], ['busy' => true]);
        $this->create_object(org_openpsa_calendar_event_resource_dba::class, [
            'event' => $master->id,
            'resource' => $resource->id
        ]);

        // The date is skipped
        $master = $this->reload($master);
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(1, $occurrences);
        $this->assertSame($this->rounded($this->timestamp('monday next week 10:00 +2 weeks')), $occurrences[0]->start);
        $skipped = date('Ymd', $this->timestamp('monday next week 10:00 +1 week'));
        $this->assertStringContainsString('EXDATE;TZID=Europe/Berlin:' . $skipped . 'T100000', $master->recurrence);
        $recurrence = new org_openpsa_calendar_recurrence;
        $this->assertCount(2, $recurrence->expand($master, $master->start, $master->start + 365 * 86400));

        // ... and stays skipped
        $master->title = 'Changed';
        $this->assertTrue($this->sudo($master->update(...)));
        $this->assertCount(1, $this->get_occurrences($master));
    }

    public function test_conflict_no_participant_left()
    {
        $user = $this->create_user();
        $start = $this->timestamp('monday next week 10:00');
        $this->create_event($this->timestamp('monday next week 10:00 +1 week'), [$user], ['busy' => true]);

        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=3', [$user], ['busy' => true]);
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(1, $occurrences);
        $this->assertSame($this->rounded($this->timestamp('monday next week 10:00 +2 weeks')), $occurrences[0]->start);
        $this->assertStringContainsString('EXDATE', $this->reload($master)->recurrence);
    }

    public function test_calendar_constraint()
    {
        $user = $this->create_user();
        $start = $this->timestamp('monday next week 10:00');
        $master = $this->create_master($start, 'RRULE:FREQ=WEEKLY;COUNT=2', [$user]);
        $occurrence = $this->get_occurrences($master)[0];
        $single = $this->create_event($start - 86400, [$user]);
        // An event outside the calendar's tree
        $outside = $this->create_object(org_openpsa_event::class, [
            'title' => 'Outside',
            'start' => $start,
            'end' => $start + 3600
        ]);
        $this->add_member($outside, $user);

        $mc = org_openpsa_calendar_event_member_dba::new_collector('uid', $user->id);
        org_openpsa_calendar_event_dba::add_calendar_constraint($mc, 'eid');
        $ids = $this->sudo(fn() => $mc->get_values('eid'));
        sort($ids);
        $expected = [$master->id, $occurrence->id, $single->id];
        sort($expected);
        $this->assertSame($expected, $ids);

        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('start', '>=', $start - 86400);
        $qb->add_constraint('start', '<=', $start + 7 * 86400 + 60);
        org_openpsa_calendar_event_dba::add_calendar_constraint($qb);
        $ids = array_column($this->sudo($qb->execute(...)), 'id');
        $this->assertContains($occurrence->id, $ids);
        $this->assertContains($master->id, $ids);
        $this->assertNotContains($outside->id, $ids);
    }

    public function test_reporthours()
    {
        $person = $this->create_user();
        $start = strtotime('today 10:00') - 3 * 86400;
        $master = $this->create_master($start, 'RRULE:FREQ=DAILY;COUNT=3', [$person]);
        $this->assertCount(2, $this->get_occurrences($master));

        $project = $this->create_object(org_openpsa_projects_project::class);
        $task = $this->create_object(org_openpsa_projects_task_dba::class, [
            'project' => $project->id,
            'start' => $start - 86400,
            'end' => time() + 86400
        ]);
        $this->create_object(org_openpsa_projects_task_resource_dba::class, [
            'task' => $task->id,
            'person' => $person->id,
            'orgOpenpsaObtype' => org_openpsa_projects_task_resource_dba::RESOURCE
        ]);
        // Only the master is linked to the task
        $this->create_object(org_openpsa_relatedto_dba::class, [
            'fromGuid' => $master->guid,
            'fromComponent' => 'org.openpsa.calendar',
            'fromClass' => org_openpsa_calendar_event_dba::class,
            'toGuid' => $task->guid,
            'toComponent' => 'org.openpsa.projects',
            'toClass' => org_openpsa_projects_task_dba::class,
            'status' => org_openpsa_relatedto_dba::CONFIRMED
        ]);

        $this->run_cron(org_openpsa_calendar_cron_reporthours::class);

        $qb = org_openpsa_expenses_hour_report_dba::new_query_builder();
        $qb->add_constraint('task', '=', $task->id);
        $qb->add_constraint('person', '=', $person->id);
        $qb->add_order('date');
        $reports = $this->sudo($qb->execute(...));
        $this->register_objects($reports);
        // One per occurrence
        $this->assertCount(3, $reports);
        $this->assertEquals([1.0, 1.0, 1.0], array_column($reports, 'hours'));
    }
}
