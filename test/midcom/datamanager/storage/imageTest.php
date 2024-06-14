<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
*/

namespace midcom\datamanager\test;

use openpsa_testcase;
use midcom_db_topic;
use midcom\datamanager\storage\image;

class imageTest extends openpsa_testcase
{
    public function test_load()
    {
        $storage = new image(new midcom_db_topic, ['name' => 'testname']);
        $result = $storage->load();
        $this->assertCount(0, $result);
    }

    public function test_save()
    {
        $storage = new image(new midcom_db_topic, ['name' => 'testname', 'type_config' => []]);
        $storage->set_value(['title' => null]);
        $this->sudo($storage->save(...));

        $result = $storage->load();
        $this->assertCount(0, $result);
    }
}
