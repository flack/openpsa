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
use midcom\datamanager\storage\images;

class imagesTest extends openpsa_testcase
{
    public function test_load()
    {
        midcom::get()->auth->request_sudo('midcom.datamanager');
        $topic = $this->create_object(\midcom_db_topic::class);
        $att = $topic->create_attachment('test', 'test', 'text/plain');
        $topic->set_parameter('midcom.helper.datamanager2.type.blobs', 'guids_testname', 'identifiermain:' . $att->guid);
        $topic->set_parameter('midcom.helper.datamanager2.type.images', "attachment_map_testname", 'identifiermain:identifier:main');
        midcom::get()->auth->drop_sudo();

        $storage = new images($topic, ['name' => 'testname']);
        $result = $storage->load();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('identifier', $result);
        $this->assertCount(1, $result['identifier']);
        $this->assertArrayHasKey('main', $result['identifier']);
        $this->assertInstanceOf(midcom_db_attachment::class, $result['identifier']['main']);
    }

    public function test_save()
    {
        midcom::get()->auth->request_sudo('midcom.datamanager');
        $topic = $this->create_object(\midcom_db_topic::class);
        $att = $topic->create_attachment('test', 'test', 'text/plain');
        $topic->set_parameter('midcom.helper.datamanager2.type.blobs', 'guids_testname', 'identifiermain:' . $att->guid);
        $topic->set_parameter('midcom.helper.datamanager2.type.images', "attachment_map_testname", 'identifiermain:identifier:main');
        $new_att = new \midcom_db_attachment;
        $new_att->name = 'test2';
        $new_att->location = tempnam(midcom::get()->config->get('midcom_tempdir'), 'test');
        copy(dirname(__DIR__) . '/__files/midgard-16x16.png', $new_att->location);

        $storage = new images($topic, ['name' => 'testname', 'type_config' => []]);
        $storage->set_value(['identifier' => ['main' => $att], 0 => ['file' => $new_att]]);
        $storage->save();
        midcom::get()->auth->drop_sudo();

        $result = $storage->load();
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('identifier', $result);
        $this->assertCount(1, $result['identifier']);
        $this->assertArrayHasKey('main', $result['identifier']);
        $this->assertInstanceOf(midcom_db_attachment::class, $result['identifier']['main']);

        foreach (array_keys($result) as $identifier) {
            if ($identifier !== 'identifier') {
                break;
            }
        }
        $expected = 'identifiermain:identifier:main,' . $identifier . 'main:' . $identifier . ':main';

        $this->assertEquals($expected, $topic->get_parameter('midcom.helper.datamanager2.type.images', 'attachment_map_testname'));
    }
}
