<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\calendar;

use openpsa_testcase;
use org_openpsa_calendar_vcal;
use org_openpsa_calendar_event_dba;
use org_openpsa_calendar_interface;
use org_openpsa_calendar_event_member_dba;
use org_openpsa_core_acl;
use Sabre\VObject\Reader;
use Sabre\VObject\Component\VEvent;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class vcalTest extends openpsa_testcase
{
    /**
     * @param string[] $properties Content lines of the VEVENT (UID is added)
     */
    private function read_vevent(string $uid, array $properties) : VEvent
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:test',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            ...$properties,
            'END:VEVENT',
            'END:VCALENDAR',
        ];
        return Reader::read(implode("\r\n", $lines) . "\r\n")->VEVENT;
    }

    public function test_vevent_to_event_tzid()
    {
        $vevent = $this->read_vevent('test-tzid', [
            'SUMMARY:Title',
            'DTSTART;TZID=Europe/Berlin:20210907T101500',
            'DTEND;TZID=Europe/Berlin:20210907T121500',
            'CLASS:PRIVATE',
        ]);
        $event = new org_openpsa_calendar_event_dba;
        org_openpsa_calendar_vcal::vevent_to_event($vevent, $event);

        $this->assertEquals('Title', $event->title);
        $this->assertEquals(gmmktime(8, 15, 0, 9, 7, 2021), $event->start);
        $this->assertEquals(gmmktime(10, 15, 0, 9, 7, 2021), $event->end);
        $this->assertTrue($event->busy);
        $this->assertEquals(org_openpsa_core_acl::ACCESS_PRIVATE, $event->orgOpenpsaAccesstype);
    }

    public function test_vevent_to_event_utc()
    {
        $vevent = $this->read_vevent('test-utc', ['DTSTART:20210907T101500Z', 'DURATION:PT1H', 'TRANSP:TRANSPARENT']);
        $event = new org_openpsa_calendar_event_dba;
        org_openpsa_calendar_vcal::vevent_to_event($vevent, $event);

        $this->assertEquals(gmmktime(10, 15, 0, 9, 7, 2021), $event->start);
        $this->assertEquals(gmmktime(11, 15, 0, 9, 7, 2021), $event->end);
        $this->assertFalse($event->busy);
    }

    public function test_vevent_to_event_all_day()
    {
        $vevent = $this->read_vevent('test-allday', ['DTSTART;VALUE=DATE:20210907', 'DTEND;VALUE=DATE:20210909']);
        $event = new org_openpsa_calendar_event_dba;
        org_openpsa_calendar_vcal::vevent_to_event($vevent, $event);

        $this->assertEquals(mktime(0, 0, 0, 9, 7, 2021), $event->start);
        $this->assertEquals(mktime(23, 59, 0, 9, 8, 2021), $event->end);

        $vevent = $this->read_vevent('test-allday2', ['DTSTART;VALUE=DATE:20210907']);
        org_openpsa_calendar_vcal::vevent_to_event($vevent, $event);
        $this->assertEquals(mktime(0, 0, 0, 9, 7, 2021), $event->start);
        $this->assertEquals(mktime(23, 59, 0, 9, 7, 2021), $event->end);
    }

    public function test_event_to_vevent()
    {
        $user = $this->create_user();
        $event = $this->create_object(org_openpsa_calendar_event_dba::class, [
            'up' => org_openpsa_calendar_interface::find_root_event()->id,
            'start' => gmmktime(8, 0, 0, 1, 10, 2005),
            'end' => gmmktime(9, 0, 0, 1, 10, 2005),
            'title' => 'Export Title',
        ]);
        $this->create_object(org_openpsa_calendar_event_member_dba::class, [
            'uid' => $user->id,
            'eid' => $event->id
        ]);
        $event =new org_openpsa_calendar_event_dba($event->id);

        $encoder = new org_openpsa_calendar_vcal;
        $encoder->add_event($event);
        $content = (string) $encoder;
        $this->assertStringContainsString('METHOD:PUBLISH', $content);
        $this->assertStringContainsString('UID:' . $event->guid . '-midgardGuid', $content);
        $this->assertStringContainsString('DTSTART:20050110T080000Z', $content);
        $this->assertStringContainsString('DTEND:20050110T090000Z', $content);
        $this->assertStringEndsWith('_is_not@openpsa2.org', (string) Reader::read($content)->VEVENT->ATTENDEE);

        // CalDAV mode: no METHOD, no placeholder addresses
        $encoder = new org_openpsa_calendar_vcal(null, false);
        $vevent = $encoder->event_to_vevent($event);
        $this->assertEquals('Export Title', (string) $vevent->SUMMARY);
        $content = (string) $encoder;
        $this->assertStringNotContainsString('METHOD:', $content);
        $this->assertStringNotContainsString('ATTENDEE', $content);
    }
}
