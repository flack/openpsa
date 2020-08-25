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
class org_openpsa_expenses_handler_hours_adminTest extends openpsa_testcase
{
    /**
     * @var org_openpsa_projects_task_dba
     */
    protected static $_task;
    protected static $_report;

    public static function setUpBeforeClass() : void
    {
        $project = self::create_class_object(org_openpsa_projects_project::class);
        self::$_task = self::create_class_object(org_openpsa_projects_task_dba::class, ['project' => $project->id]);
        self::$_report = self::create_class_object(org_openpsa_expenses_hour_report_dba::class, ['task' => self::$_task->id]);
        self::create_user(true);
    }

    public function testHandler_hours_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', ['hours', 'edit', self::$_report->guid]);
        $this->assertEquals('hours_edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_hours_delete()
    {
        midcom::get()->auth->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', ['hours', 'delete', self::$_report->guid]);
        $this->assertEquals('hours_delete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_hours_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', ['hours', 'create', 'hour_report']);
        $this->assertEquals('hours_create', $data['handler_id']);

        $person = $this->create_object(midcom_db_person::class);

        $formdata = [
            'description' => __CLASS__ . '::' . __FUNCTION__,
            'hours' => '2',
            'person' => [
                'selection' => '[' . $person->id . ']'
            ],
            'task' => [
                'selection' => '[' . self::$_task->id . ']'
            ],
        ];

        $this->submit_dm_no_relocate_form('controller', $formdata, 'org.openpsa.expenses', ['hours', 'create', 'hour_report']);
        $qb = org_openpsa_expenses_hour_report_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $qb->add_constraint('description', '=', __CLASS__ . '::' . __FUNCTION__);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(1, $results);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_hours_create_task()
    {
        midcom::get()->auth->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', ['hours', 'create', 'task', 'hour_report', self::$_task->guid]);
        $this->assertEquals('hours_create_task', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_hours_create_invoice()
    {
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class);
        midcom::get()->auth->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', ['hours', 'create', 'invoice', 'hour_report', $invoice->guid]);
        $this->assertEquals('hours_create_invoice', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
