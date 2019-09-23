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
class org_openpsa_projects_handler_frontpageTest extends openpsa_testcase
{
    public function testHandler_frontpage()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects');
        $this->assertEquals('frontpage', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
