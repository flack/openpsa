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
use midcom_db_topic;
use midcom_response_relocate;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class viewerTest extends openpsa_testcase
{
    protected static $_topic;

    public static function setUpBeforeClass() : void
    {
        $attributes = [
            'component' => 'org.openpsa.calendar',
            'name' => \midcom_helper_misc::urlize(__CLASS__) . time()
        ];
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        self::$_topic = self::create_class_object(midcom_db_topic::class, $attributes);
        self::$_topic->set_parameter('org.openpsa.calendar', 'calendar_root_event', 0);
        midcom::get()->auth->drop_sudo();

        self::create_user(true);
    }

    public function testHandler_frontpage()
    {
        $data = $this->run_handler(self::$_topic);
        $this->assertEquals('frontpage', $data['handler_id']);
        $this->assertInstanceOf(midcom_response_relocate::class, $data['__openpsa_testcase_response']);
    }
}
