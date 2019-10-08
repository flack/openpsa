<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_core_nullcomponent_handler_indexTest extends openpsa_testcase
{
    public function testHandler_index()
    {
        $topic = new midcom_db_topic;
        $topic->component = 'midcom.core.nullcomponent';
        $data = $this->run_handler($topic);
        $this->assertEquals('index', $data['handler_id']);
    }
}
