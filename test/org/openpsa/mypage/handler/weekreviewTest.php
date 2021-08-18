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
class weekreviewTest extends openpsa_testcase
{
    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
    }

    public function testHandler_weekreview()
    {
        $data = $this->run_handler('org.openpsa.mypage', ['weekreview', strftime('%Y-%m-%d')]);
        $this->assertEquals('weekreview', $data['handler_id']);
    }

    public function testHandler_weekreview_redirect()
    {
        $url = $this->run_relocate_handler('org.openpsa.mypage', ['weekreview']);
        $this->assertEquals('weekreview/' . strftime('%Y-%m-%d') . '/', $url);
    }
}
