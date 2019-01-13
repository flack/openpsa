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
class org_openpsa_projects_projectTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $project = new org_openpsa_projects_project();
        $project->_use_rcs = false;

        $stat = $project->create();
        $this->assertTrue($stat);
        $this->register_object($project);

        $project->refresh();
        $this->assertEquals('Project #' . $project->id, $project->title);
        $project->title = 'Test Project';
        $stat = $project->update();
        $this->assertTrue($stat);
        $this->assertEquals('Test Project', $project->title);

        $stat = $project->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
