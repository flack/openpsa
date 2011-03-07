<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_projects_projectTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');

        $project = new org_openpsa_projects_project();
        $stat = $project->create();
        $this->assertTrue($stat);
        $this->register_object($project);

        $project->refresh();
        $this->assertEquals('Task #' . $project->id, $project->title);
        $project->title = 'Test Project';
        $stat = $project->update();
        $this->assertTrue($stat);
        $this->assertEquals('Test Project', $project->title);

        $stat = $project->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
     }
}
?>