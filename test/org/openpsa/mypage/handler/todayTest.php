<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\mypage\handler;

use openpsa_testcase;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class todayTest extends openpsa_testcase
{
    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
    }

    public function testHandler_today()
    {
        $data = $this->run_handler('org.openpsa.mypage');
        $this->assertEquals('today', $data['handler_id']);
    }

    public function testHandler_day()
    {
        $data = $this->run_handler('org.openpsa.mypage', ['day', strftime('%Y-%m-%d')]);
        $this->assertEquals('day', $data['handler_id']);
    }

    public function testHandler_updates()
    {
        $data = $this->run_handler('org.openpsa.mypage', ['updates']);
        $this->assertEquals('updates', $data['handler_id']);
    }
}
