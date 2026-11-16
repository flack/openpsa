<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\calendar\caldav;

use openpsa_testcase;
use midcom;
use midcom_db_person;
use org_openpsa_calendar_event_dba;
use org_openpsa_calendar_event_member_dba;
use org_openpsa_calendar_interface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class handlerTest extends openpsa_testcase
{
    private static midcom_db_person $user;

    private static midcom_db_person $other_user;

    private static org_openpsa_calendar_event_dba $event;

    public static function setUpBeforeClass() : void
    {
        // Users need to be allowed to create events (usually granted by the admin)
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        org_openpsa_calendar_interface::find_root_event()->set_privilege('midgard:create', 'USERS', MIDCOM_PRIVILEGE_ALLOW);
        midcom::get()->auth->drop_sudo();

        self::$user = self::create_user();
        self::$other_user = self::create_user();
        self::$event = self::create_class_object(org_openpsa_calendar_event_dba::class, [
            'up' => org_openpsa_calendar_interface::find_root_event()->id,
            'start' => gmmktime(10, 0, 0, 1, 10, 2005),
            'end' => gmmktime(11, 0, 0, 1, 10, 2005),
            'title' => 'CalDAV Event',
            'busy' => true,
        ]);
        self::create_class_object(org_openpsa_calendar_event_member_dba::class, [
            'uid' => self::$user->id,
            'eid' => self::$event->id
        ]);
    }

    public static function tearDownAfterClass() : void
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        org_openpsa_calendar_interface::find_root_event()->unset_privilege('midgard:create', 'USERS');
        midcom::get()->auth->drop_sudo();
        parent::tearDownAfterClass();
    }

    private function dav(string $method, string $path, ?midcom_db_person $user = null, string $body = '', array $server = []) : Response
    {
        if ($user) {
            $server['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode($user->lastname . ':' . $user->extra);
        }
        if ($body !== '') {
            $server['CONTENT_TYPE'] ??= 'application/xml; charset=utf-8';
        }
        $request = new Request(server: $server, content: $body);
        $request->setMethod($method);

        $data = $this->run_handler('org.openpsa.calendar', 'caldav/' . $path, $request);
        $this->assertEquals('caldav', $data['handler_id']);
        return $data['__openpsa_testcase_response'];
    }

    private function calendar_path(midcom_db_person $user) : string
    {
        return 'calendars/' . $user->lastname . '/default/';
    }

    private function event_path(midcom_db_person $user) : string
    {
        return $this->calendar_path($user) . self::$event->guid . '.ics';
    }

    public function testUnauthenticated()
    {
        $response = $this->dav('PROPFIND', '', server: ['HTTP_DEPTH' => '0']);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringStartsWith('Basic realm=', $response->headers->get('WWW-Authenticate'));

        $response = $this->dav('PROPFIND', '', server: [
            'HTTP_DEPTH' => '0',
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode(self::$user->lastname . ':wrong-password')
        ]);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testBrowserHint()
    {
        $response = $this->dav('GET', '', self::$user);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('caldav/', $response->getContent());

        $response = $this->dav('GET', $this->calendar_path(self::$user), self::$user);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('CalDAV server', $response->getContent());
    }

    public function testDiscovery()
    {
        $body = '<?xml version="1.0"?><d:propfind xmlns:d="DAV:"><d:prop><d:current-user-principal/></d:prop></d:propfind>';
        $response = $this->dav('PROPFIND', '', self::$user, $body, ['HTTP_DEPTH' => '0']);
        $this->assertEquals(Response::HTTP_MULTI_STATUS, $response->getStatusCode());
        $this->assertStringContainsString('caldav/principals/' . self::$user->lastname . '/', $response->getContent());

        $body = '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav"><d:prop><c:calendar-home-set/><d:displayname/></d:prop></d:propfind>';
        $response = $this->dav('PROPFIND', 'principals/' . self::$user->lastname . '/', self::$user, $body, ['HTTP_DEPTH' => '0']);
        $this->assertEquals(Response::HTTP_MULTI_STATUS, $response->getStatusCode());
        $this->assertStringContainsString('caldav/calendars/' . self::$user->lastname . '/', $response->getContent());
    }

    public function testCalendarHome()
    {
        $body = '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:cs="http://calendarserver.org/ns/"><d:prop><d:resourcetype/><d:displayname/><cs:getctag/></d:prop></d:propfind>';
        $response = $this->dav('PROPFIND', 'calendars/' . self::$user->lastname . '/', self::$user, $body, ['HTTP_DEPTH' => '1']);
        $this->assertEquals(Response::HTTP_MULTI_STATUS, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertStringContainsString('calendars/' . self::$user->lastname . '/default/', $content);
        $this->assertStringContainsString('getctag', $content);

        // Other users' calendars are hidden
        $response = $this->dav('PROPFIND', $this->calendar_path(self::$user), self::$other_user, $body, ['HTTP_DEPTH' => '1']);
        $this->assertEquals(Response::HTTP_MULTI_STATUS, $response->getStatusCode());
        $this->assertStringNotContainsString('getctag', $response->getContent());
        $this->assertStringNotContainsString(self::$event->guid, $response->getContent());

        $response = $this->dav('GET', $this->event_path(self::$user), self::$other_user);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testCalendarObjects()
    {
        $body = '<?xml version="1.0"?><d:propfind xmlns:d="DAV:"><d:prop><d:getetag/></d:prop></d:propfind>';
        $response = $this->dav('PROPFIND', $this->calendar_path(self::$user), self::$user, $body, ['HTTP_DEPTH' => '1']);
        $this->assertEquals(Response::HTTP_MULTI_STATUS, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/<d:href>([^<]+' . self::$event->guid . '\.ics)<\/d:href>/', $content);
        preg_match('/<d:href>([^<]+' . self::$event->guid . '\.ics)<\/d:href>/', $content, $matches);
        $href = $matches[1];

        $response = $this->dav('GET', $this->event_path(self::$user), self::$user);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringStartsWith('text/calendar', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('SUMMARY:CalDAV Event', $response->getContent());
        $this->assertNotEmpty($response->headers->get('ETag'));

        $body = '<?xml version="1.0"?><c:calendar-multiget xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav"><d:prop><d:getetag/><c:calendar-data/></d:prop><d:href>' . $href . '</d:href></c:calendar-multiget>';
        $response = $this->dav('REPORT', $this->calendar_path(self::$user), self::$user, $body, ['HTTP_DEPTH' => '1']);
        $this->assertEquals(Response::HTTP_MULTI_STATUS, $response->getStatusCode());
        $this->assertStringContainsString('SUMMARY:CalDAV Event', $response->getContent());

        $query = '<?xml version="1.0"?><c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav"><d:prop><d:getetag/></d:prop><c:filter><c:comp-filter name="VCALENDAR"><c:comp-filter name="VEVENT"><c:time-range start="%s" end="%s"/></c:comp-filter></c:comp-filter></c:filter></c:calendar-query>';
        $response = $this->dav('REPORT', $this->calendar_path(self::$user), self::$user, sprintf($query, '20050101T000000Z', '20050201T000000Z'), ['HTTP_DEPTH' => '1']);
        $this->assertEquals(Response::HTTP_MULTI_STATUS, $response->getStatusCode());
        $this->assertStringContainsString(self::$event->guid . '.ics', $response->getContent());

        $response = $this->dav('REPORT', $this->calendar_path(self::$user), self::$user, sprintf($query, '20060101T000000Z', '20060201T000000Z'), ['HTTP_DEPTH' => '1']);
        $this->assertEquals(Response::HTTP_MULTI_STATUS, $response->getStatusCode());
        $this->assertStringNotContainsString(self::$event->guid . '.ics', $response->getContent());
    }

    public function testExport()
    {
        $response = $this->dav('GET', $this->calendar_path(self::$user), self::$user, server: ['QUERY_STRING' => 'export']);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringStartsWith('text/calendar', $response->headers->get('Content-Type'));
        $this->assertEquals(1, substr_count($response->getContent(), 'BEGIN:VEVENT'));
    }

    public function testWrite()
    {
        $path = $this->calendar_path(self::$user) . 'caldav-handler-' . uniqid() . '.ics';
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Client//EN\r\nBEGIN:VEVENT\r\nUID:%s\r\nDTSTAMP:20050101T000000Z\r\n"
            . "SUMMARY:%s\r\nDTSTART:%s\r\nDTEND:%s\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $headers = ['CONTENT_TYPE' => 'text/calendar; charset=utf-8'];
        $uid = uniqid('caldav-handler-');

        $response = $this->dav('PUT', $path, self::$user, sprintf($ics, $uid, 'New', '20050310T100000Z', '20050310T110000Z'), $headers + ['HTTP_IF_NONE_MATCH' => '*']);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $response = $this->dav('GET', $path, self::$user);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('SUMMARY:New', $response->getContent());
        $etag = $response->headers->get('ETag');

        $response = $this->dav('PUT', $path, self::$user, sprintf($ics, $uid, 'Changed', '20050310T100000Z', '20050310T110000Z'), $headers + ['HTTP_IF_MATCH' => $etag]);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertStringContainsString('SUMMARY:Changed', $this->dav('GET', $path, self::$user)->getContent());

        // The ETag changed with the update
        $response = $this->dav('PUT', $path, self::$user, sprintf($ics, $uid, 'Stale', '20050310T100000Z', '20050310T110000Z'), $headers + ['HTTP_IF_MATCH' => $etag]);
        $this->assertEquals(Response::HTTP_PRECONDITION_FAILED, $response->getStatusCode());

        // Overlaps the (busy) event from setUpBeforeClass
        $response = $this->dav('PUT', $path, self::$user, sprintf($ics, $uid, 'Conflict', '20050110T103000Z', '20050110T113000Z'), $headers);
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());

        $response = $this->dav('DELETE', $path, self::$user);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->dav('GET', $path, self::$user)->getStatusCode());

        // The event from setUpBeforeClass is unchanged
        $this->assertEquals('CalDAV Event', (new org_openpsa_calendar_event_dba(self::$event->id))->title);
    }
}
