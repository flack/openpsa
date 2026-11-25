<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager\test;

use openpsa_testcase;
use midcom_db_topic;
use midcom\datamanager\storage\dbacollection;

class dbacollectionTest extends openpsa_testcase
{
    private function get_storage(midcom_db_topic $parent) : dbacollection
    {
        return new dbacollection($parent, [
            'type_config' => [
                'mapping_class_name' => midcom_db_topic::class,
                'master_fieldname' => 'up',
                'master_is_id' => true,
                'schema' => [
                    'fields' => [
                        'title' => [
                            'storage' => 'title',
                            'type' => 'text',
                            'widget' => 'text'
                        ]
                    ]
                ]
            ],
            'widget_config' => []
        ]);
    }

    private function create_child(midcom_db_topic $master, string $title) : midcom_db_topic
    {
        return $this->create_object(midcom_db_topic::class, [
            'up' => $master->id,
            'name' => uniqid('child'),
            'title' => $title
        ]);
    }

    /**
     * Uses a collector because this always gets the values from the DB, never from already loaded objects
     */
    private function get_children(midcom_db_topic $master) : array
    {
        $mc = midcom_db_topic::new_collector('up', $master->id);
        return $mc->get_rows(['title', 'metadata.revision'], 'id');
    }

    public function test_save_modified()
    {
        $parent = $this->create_object(midcom_db_topic::class);
        $modified = $this->create_child($parent, 'old');
        $unmodified = $this->create_child($parent, 'unmodified');

        $storage = $this->get_storage($parent);
        $storage->get_value()[$modified->id]->title = 'new';
        $this->sudo($storage->save(...));

        $children = $this->get_children($parent);
        $this->assertCount(2, $children);
        $this->assertSame('new', $children[$modified->id]['title']);
        $this->assertEquals(1, $children[$modified->id]['revision']);
        $this->assertSame('unmodified', $children[$unmodified->id]['title']);
        $this->assertEquals(0, $children[$unmodified->id]['revision']);
    }

    public function test_save_create_delete()
    {
        $parent = $this->create_object(midcom_db_topic::class);
        $kept = $this->create_child($parent, 'kept');
        $deleted = $this->create_child($parent, 'deleted');

        $storage = $this->get_storage($parent);
        $value = $storage->get_value();
        unset($value[$deleted->id]);
        $value['new'] = ['title' => 'created'];
        $storage->set_value($value);
        $this->sudo($storage->save(...));

        foreach ($storage->load() as $container) {
            $this->register_object($container->get_value());
        }

        $children = $this->get_children($parent);
        $this->assertCount(2, $children);
        $this->assertArrayHasKey($kept->id, $children);
        $this->assertArrayNotHasKey($deleted->id, $children);
        $this->assertEqualsCanonicalizing(['kept', 'created'], array_column($children, 'title'));
    }
}
