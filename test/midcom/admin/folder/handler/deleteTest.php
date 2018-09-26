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
class midcom_admin_folder_handler_deleteTest extends openpsa_testcase
{
    public function testHandler_delete()
    {
        midcom::get()->auth->request_sudo('midcom.admin.folder');
        $parent = self::get_component_node('net.nehmer.static');
        $attributes = [
            'component' => 'net.nehmer.static',
            'parent' => $parent->id
        ];
        $topic = $this->create_object(midcom_db_topic::class, $attributes);

        $data = $this->run_handler($topic, ['__ais', 'folder', 'delete']);
        $this->assertEquals('delete', $data['handler_id']);

        $_POST = ['confirm-delete' => true];

        $this->run_relocate_handler($topic, ['__ais', 'folder', 'delete']);

        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('id', '=', $topic->id);
        $this->assertEquals(0, $qb->count());

        midcom::get()->auth->drop_sudo();
    }
}
