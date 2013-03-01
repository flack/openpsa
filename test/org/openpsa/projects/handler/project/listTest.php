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
class org_openpsa_projects_handler_project_listTest extends openpsa_testcase
{
    public static function setupBeforeClass()
    {
        self::create_user(true);
    }

    public function testHandler_list()
    {
        midcom::get('auth')->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', array('project', 'list'));
        $this->assertEquals('project-list', $data['handler_id']);

        $this->show_handler($data);
        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_list_status()
    {
        midcom::get('auth')->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', array('project', 'list', 'overtime'));
        $this->assertEquals('project-list-status', $data['handler_id']);

        $this->show_handler($data);
        midcom::get('auth')->drop_sudo();
    }
}
?>