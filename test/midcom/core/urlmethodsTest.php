<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\core;

use openpsa_testcase;
use midcom_db_topic;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class urlmethodsTest extends openpsa_testcase
{
    public function test_logout()
    {
        $topic = new midcom_db_topic;
        $topic->component = 'midcom.core.nullcomponent';
        $url = $this->run_relocate_handler($topic, 'midcom-logout-https://some-website.com/');
        $this->assertEquals('https://some-website.com/', $url);
    }
}
