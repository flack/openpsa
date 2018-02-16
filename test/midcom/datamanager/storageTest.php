<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
*/

namespace midcom\datamanager\test;

use openpsa_testcase;
use midcom;
use midcom\datamanager\storage;
use midcom\datamanager\schema;
use midcom\datamanager\storage\blobs;

class storageTest extends openpsa_testcase
{
    public function test_create_parameter()
    {
        midcom::get()->auth->request_sudo('dm.test');
        $topic = new \midcom_db_topic;

        $config = [
            'fields' => [
                'test' => [
                    'storage' => [
                        'location' => 'parameter',
                        'domain' => 'dm.test',
                        'name' => 'test',
                    ],
                    'type' => 'text',
                    'widget' => 'text'
                ]
            ]
        ];
        $schema = new schema($config);
        $storage = new storage\container\dbacontainer($schema, $topic, []);

        $storage->test = '23';
        $storage->save();
        midcom::get()->auth->drop_sudo();

        $this->register_object($topic);
        $topic->refresh();
        $this->assertSame('23', $topic->get_parameter('dm.test', 'test'));
    }

    public function test_update_parameter()
    {
        midcom::get()->auth->request_sudo('dm.test');
        $topic = $this->create_object(\midcom_db_topic::class);

        $config = [
            'fields' => [
                'test' => [
                    'storage' => [
                        'location' => 'parameter',
                        'domain' => 'dm.test',
                        'name' => 'test',
                    ],
                    'type' => 'text',
                    'widget' => 'text'
                ]
            ]
        ];
        $schema = new schema($config);
        $storage = new storage\container\dbacontainer($schema, $topic, []);

        $storage->test = '23';
        $storage->save();
        midcom::get()->auth->drop_sudo();

        $this->register_object($topic);
        $topic->refresh();
        $this->assertSame('23', $topic->get_parameter('dm.test', 'test'));
    }

    public function test_process_blobs()
    {
        $config = [
            'fields' => [
                'test' => [
                    'title' => 'test',
                    'storage' => null,
                    'type' => 'blobs',
                    'widget' => 'downloads'
                ],
            ]
        ];
        $schema = new schema($config);
        $topic = new \midcom_db_topic;
        $storage = new storage\container\dbacontainer($schema, $topic, []);

        $this->assertArrayHasKey('test', $storage);
        $node = $storage->current();
        $this->assertInstanceOf(blobs::class, $node);
    }
}
