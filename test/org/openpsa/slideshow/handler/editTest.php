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
class org_openpsa_slideshow_handler_editTest extends openpsa_testcase
{
    public static function setupBeforeClass()
    {
        self::create_user(true);
        parent::setupBeforeClass();
    }

    public function test_handler_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.slideshow');

        $data = $this->run_handler('org.openpsa.slideshow', array('edit'));
        $this->assertEquals('edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_edit_ajax()
    {
        midcom::get()->auth->request_sudo('org.openpsa.slideshow');

        $topic = $this->get_component_node('org.openpsa.slideshow');
        $image = $this->create_object('org_openpsa_slideshow_image_dba', array('topic' => $topic->id));

        $_POST = array(
            'operation' => 'delete',
            'guids' => $image->guid
        );

        $data = $this->run_handler('org.openpsa.slideshow', array('edit', 'ajax'));
        $this->assertEquals('edit_ajax', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
