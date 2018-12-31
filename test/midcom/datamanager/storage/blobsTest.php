<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
*/

namespace midcom\datamanager\test;

use openpsa_testcase;
use midcom;
use midcom_db_attachment;
use midcom\datamanager\storage\blobs;

class blobsTest extends openpsa_testcase
{
    public function test_load()
    {
        midcom::get()->auth->request_sudo('midcom.datamanager');
        $topic = $this->create_object(\midcom_db_topic::class);
        $att = $topic->create_attachment('test', 'test', 'text/plain');
        $topic->set_parameter('midcom.helper.datamanager2.type.blobs', 'guids_testname', 'identifier:' . $att->guid);
        midcom::get()->auth->drop_sudo();

        $storage = new blobs($topic, ['name' => 'testname']);
        $result = $storage->load();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('identifier', $result);
        $this->assertInstanceOf(midcom_db_attachment::class, $result['identifier']);
    }

    public function test_save()
    {
        midcom::get()->auth->request_sudo('midcom.datamanager');
        $topic = $this->create_object(\midcom_db_topic::class);
        $att = $topic->create_attachment('test', 'test', 'text/plain');
        $topic->set_parameter('midcom.helper.datamanager2.type.blobs', 'guids_testname', 'identifier:' . $att->guid);
        $new_att = new \midcom_db_attachment;
        $new_att->name = 'test2';
        $new_att->location = tempnam(midcom::get()->config->get('midcom_tempdir'), 'test');
        file_put_contents($new_att->location, 'test');

        $storage = new blobs($topic, ['name' => 'testname']);
        $storage->set_value(['identifier' => $att, 0 => $new_att]);
        $this->assertTrue($storage->save());
        midcom::get()->auth->drop_sudo();

        $result = $storage->load();
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('identifier', $result);
        $this->assertInstanceOf(midcom_db_attachment::class, $result['identifier']);

        foreach ($result as $identifier => $item) {
            if ($identifier !== 'identifier') {
                break;
            }
        }
        $expected = 'identifier:' . $att->guid . ',' . $identifier . ':' . $item->guid;

        $this->assertEquals($expected, $topic->get_parameter('midcom.helper.datamanager2.type.blobs', 'guids_testname'));
    }
}
