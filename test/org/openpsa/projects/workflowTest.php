<?php
class workflowTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        require_once('rootfile.php');
    }

    public function testWorkflow()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');

        $project = new org_openpsa_projects_project();
        $project->create();

        $task = new org_openpsa_projects_task_dba();
        $task->project = $project->id;
        $task->create();

        $task->delete();
        $project->delete();

        $_MIDCOM->auth->drop_sudo();
     }
}
?>