<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\expenses\handler\hours;

use openpsa_testcase;
use midcom;
use midcom_response_styled;
use midcom\datamanager\controller;
use org_openpsa_projects_task_dba;
use org_openpsa_projects_project;
use org_openpsa_expenses_hour_report_dba;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class batcheditTest extends openpsa_testcase
{
    protected static org_openpsa_projects_task_dba $_task;

    public static function setUpBeforeClass() : void
    {
        $project = self::create_class_object(org_openpsa_projects_project::class);
        self::$_task = self::create_class_object(org_openpsa_projects_task_dba::class, ['project' => $project->id]);
        self::create_user(true);
    }

    private function create_report(array $values) : org_openpsa_expenses_hour_report_dba
    {
        return $this->create_object(org_openpsa_expenses_hour_report_dba::class, $values + [
            'task' => self::$_task->id,
            'date' => mktime(12, 0, 0, 1, 15, 2026),
            'invoiceable' => false
        ]);
    }

    private function get_controller(array $reports, array $post = []) : controller
    {
        $ids = implode(',', array_column($reports, 'id'));
        $request = Request::create('/?entries=' . $ids, empty($post) ? 'GET' : 'POST', $post);
        $data = $this->run_handler('org.openpsa.expenses', ['hours', 'batch', 'edit'], $request);
        $this->assertEquals('hours_batch_edit', $data['handler_id']);
        $response = $data['__openpsa_testcase_response'];
        if (!$response instanceof midcom_response_styled) {
            $this->fail('Unexpected response ' . get_class($response));
        }
        $prop = new ReflectionProperty($response, 'context');
        return $prop->getValue($response)->get_key(MIDCOM_CONTEXT_SHOWCALLBACK)[0];
    }

    private function submit(array $reports, array $formdata) : array
    {
        $this->set_dm_formdata($this->get_controller($reports), $formdata);

        $ids = implode(',', array_column($reports, 'id'));
        $request = Request::create('/?entries=' . $ids, 'POST', $_POST);
        return $this->run_handler('org.openpsa.expenses', ['hours', 'batch', 'edit'], $request);
    }

    public function testPrefill()
    {
        midcom::get()->auth->request_sudo('org.openpsa.expenses');
        $reports = [
            $this->create_report(['hours' => 1, 'description' => 'same']),
            $this->create_report(['hours' => 2, 'description' => 'same', 'date' => mktime(16, 0, 0, 1, 15, 2026)])
        ];

        $controller = $this->get_controller($reports);
        $form = $controller->get_datamanager()->get_form();
        $this->assertEquals('same', $form->get('description')->getData());
        $this->assertEquals(self::$_task->id, $form->get('task')->getData());
        // same day, different time: counts as the same value in a date-only widget
        $this->assertNotNull($form->get('date')->getData());
        $this->assertNull($form->get('hours')->getData());
        $this->assertFalse($form->get('hours')->getConfig()->getOption('required'));
        $this->assertTrue($form->get('task')->getConfig()->getOption('required'));

        ob_start();
        $controller->display_form();
        $html = ob_get_clean();
        $this->assertMatchesRegularExpression('/<input[^>]+placeholder="\(multiple values\)"/', $html);
        midcom::get()->auth->drop_sudo();
    }

    public function testSave()
    {
        midcom::get()->auth->request_sudo('org.openpsa.expenses');
        $reports = [
            $this->create_report(['hours' => 1, 'description' => 'one']),
            $this->create_report(['hours' => 2, 'description' => 'two', 'invoiceable' => true])
        ];

        $data = $this->submit($reports, [
            'description' => 'batch',
            'invoiceable' => '0'
        ]);
        $this->assertEquals('', $this->get_dialog_url($data));

        foreach ($reports as $i => $report) {
            $report->refresh();
            $this->assertEquals('batch', $report->description);
            $this->assertEquals($i + 1, $report->hours);
            $this->assertFalse($report->invoiceable);
            $this->assertEquals(mktime(12, 0, 0, 1, 15, 2026), $report->date);
        }
        midcom::get()->auth->drop_sudo();
    }
}
