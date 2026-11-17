<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\calendar\handler;

use openpsa_testcase;
use midcom;
use midcom_db_person;
use midcom_error;
use org_openpsa_calendar_event_dba;
use org_openpsa_calendar_event_member_dba;
use org_openpsa_calendar_recurrence;

/**
 * Web UI for recurring events: read-only, occurrences generated on request
 *
 * @package openpsa.test
 */
class recurrenceTest extends openpsa_testcase
{
    private static midcom_db_person $user;

    public static function setUpBeforeClass() : void
    {
        self::$user = self::create_user(true);
    }

    private function create_series() : org_openpsa_calendar_event_dba
    {
        $start = strtotime('monday next week 10:00');
        $master = $this->create_object(org_openpsa_calendar_event_dba::class, [
            'start' => $start,
            'end' => $start + 3600,
            'title' => 'Series ' . uniqid(),
            'recurrence' => 'RRULE:FREQ=WEEKLY',
            'timezone' => date_default_timezone_get()
        ]);
        $this->create_object(org_openpsa_calendar_event_member_dba::class, [
            'eid' => $master->id,
            'uid' => self::$user->id
        ]);
        return $master;
    }

    /**
     * @return org_openpsa_calendar_event_dba[]
     */
    private function get_occurrences(org_openpsa_calendar_event_dba $master, int $from, int $to) : array
    {
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('up', '=', $master->id);
        $qb->add_constraint('start', '>=', $from);
        $qb->add_constraint('start', '<', $to);
        return $this->sudo($qb->execute(...));
    }

    public function testHandler_json()
    {
        $master = $this->create_series();
        // A week beyond the horizon
        $from = strtotime('+6 months', $master->start) - 86400;
        $to = $from + 7 * 86400;
        $this->assertLessThan($from, $master->materializedUntil);
        $this->assertCount(0, $this->get_occurrences($master, $from, $to));

        $_GET = [
            'start' => $from,
            'end' => $to
        ];
        $data = $this->run_handler('org.openpsa.calendar', ['json']);
        $this->assertEquals('calendar_view_json', $data['handler_id']);

        // Generated on request, and only up to the end of the range
        $occurrences = $this->get_occurrences($master, $from, $to);
        $this->assertCount(1, $occurrences);
        $master->refresh();
        $this->assertEquals($to, $master->materializedUntil);

        $events = json_decode($data['__openpsa_testcase_response']->getContent(), true);
        $events = array_column($events, null, 'id');
        $this->assertArrayHasKey($occurrences[0]->guid, $events);
        $event = $events[$occurrences[0]->guid];
        $this->assertFalse($event['editable']);
        $this->assertTrue($event['recurring']);
        $this->assertContains('recurring', explode(' ', $event['className']));
    }

    public function testHandler_view()
    {
        $master = $this->create_series();
        $occurrence = $this->get_occurrences($master, $master->start + 1, $master->start + 8 * 86400)[0];

        $data = $this->run_handler('org.openpsa.calendar', ['event', $master->guid]);
        $this->assertEquals('event_view', $data['handler_id']);
        $this->assertEquals(['summary' => 'Every week', 'series_url' => null], $data['recurrence']);

        $data = $this->run_handler('org.openpsa.calendar', ['event', $occurrence->guid]);
        $this->assertEquals('Every week', $data['recurrence']['summary']);
        $this->assertStringContainsString($master->guid, $data['recurrence']['series_url']);
    }

    public function testHandler_admin_refused()
    {
        $master = $this->create_series();
        $occurrence = $this->get_occurrences($master, $master->start + 1, $master->start + 8 * 86400)[0];
        $start = $occurrence->start;

        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        foreach ([$master, $occurrence] as $event) {
            foreach (['edit', 'move', 'delete'] as $action) {
                $this->set_post_data(['start' => $event->start + 3600]);
                try {
                    $this->run_handler('org.openpsa.calendar', ['event', $action, $event->guid]);
                    $this->fail($action . ' should be refused');
                } catch (midcom_error $e) {
                    $this->assertEquals(403, $e->getCode());
                }
            }
        }
        midcom::get()->auth->drop_sudo();

        $occurrence->refresh();
        $this->assertEquals($start, $occurrence->start);
    }

    public function testHandler_weekreview()
    {
        $master = $this->create_series();
        // A Monday beyond the horizon
        $date = strtotime('+26 weeks', $master->start);
        $this->assertCount(0, $this->get_occurrences($master, $date - 86400, $date + 86400));

        $data = $this->run_handler('org.openpsa.mypage', ['weekreview', date('Y-m-d', $date)]);
        $this->assertEquals('weekreview', $data['handler_id']);
        $this->assertCount(1, $this->get_occurrences($master, $date - 86400, $date + 86400));
    }

    public function test_extend_all()
    {
        $master = $this->create_series();
        $until = strtotime('+6 months', $master->start);
        $this->assertSame([], (new org_openpsa_calendar_recurrence)->extend_all($until));
        $master->refresh();
        $this->assertEquals($until, $master->materializedUntil);
    }
}
