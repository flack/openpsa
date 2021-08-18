<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\core\nullcomponent\handler;

use openpsa_testcase;
use midcom;
use midcom_db_topic;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class wizardTest extends openpsa_testcase
{
    public function testHandler_index()
    {
        midcom::get()->auth->request_sudo('midcom.core');
        $topic = new midcom_db_topic;
        $topic->component = 'midcom.core.nullcomponent';
        $data = $this->run_handler($topic, ['wizard']);
        $this->assertEquals('wizard', $data['handler_id']);
        midcom::get()->auth->drop_sudo();
    }
}
