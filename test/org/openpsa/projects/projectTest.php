<?php
require_once('rootfile.php');

class projectTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');

        $project = new org_openpsa_projects_project();
        $stat = $project->create();
        $this->assertTrue($stat);
        $this->assertEquals(ORG_OPENPSA_OBTYPE_PROJECT, $project->orgOpenpsaObtype);

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