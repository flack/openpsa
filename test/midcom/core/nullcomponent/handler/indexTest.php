<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\core\nullcomponent\handler;

use openpsa_testcase;
use midcom_db_topic;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class indexTest extends openpsa_testcase
{
    public function testHandler_index()
    {
        $topic = new midcom_db_topic;
        $topic->component = 'midcom.core.nullcomponent';
        $data = $this->run_handler($topic);
        $this->assertEquals('index', $data['handler_id']);
    }
}
