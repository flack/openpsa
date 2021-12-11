<?php
/**
 * @package org.openpsa.mypage
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.mypage "now working on" handler
 *
 * @package org.openpsa.mypage
 */
class org_openpsa_mypage_workingon
{
    /**
     * Time person started working on the task
     */
    public $start = 0;

    /**
     * Time spent working on the task, in seconds
     */
    protected $time = 0;

    /**
     * Task being worked on
     */
    public $task;

    /**
     * The description for the current hour report
     */
    public $description = '';

    /**
     * Person working on the task
     */
    protected $person;

    /**
     * If hour report is invoiceable
     */
    public $invoiceable = false;

    /**
     * @param midcom_db_person $person Person to handle "now working on" for. By default current user
     */
    public function __construct(midcom_db_person $person = null)
    {
        if ($person === null) {
            midcom::get()->auth->require_valid_user();
            $this->person = midcom::get()->auth->user->get_storage();
        } else {
            $this->person = $person;
        }

        // Figure out what the person is working on
        $this->_get();
    }

    /**
     * Load task and time person is working on
     */
    private function _get()
    {
        $workingon = $this->person->get_parameter('org.openpsa.mypage', 'workingon');

        if (!$workingon) {
            // Person isn't working on anything at the moment
            return;
        }
        $workingon = json_decode($workingon);
        $task_time = strtotime($workingon->start . " GMT");

        // Set the protected vars
        $this->task = new org_openpsa_projects_task_dba($workingon->task);
        $this->time = time() - $task_time;
        $this->start = $task_time;
        $this->description = $workingon->description;
        $this->invoiceable = $workingon->invoiceable;
    }

    /**
     * Set a task the user works on. If user was previously working on something else hours will be reported automatically.
     */
    public function set(string $task_guid = '') : bool
    {
        $description = trim($_POST['description']);
        midcom::get()->auth->request_sudo('org.openpsa.mypage');
        $invoiceable = (isset($_POST['invoiceable']) && $_POST['invoiceable'] == 'true');
        if ($this->task) {
            // We were previously working on another task. Report hours
            // Generate a message
            if ($description == "") {
                $l10n = midcom::get()->i18n->get_l10n('org.openpsa.mypage');
                $formatter = $l10n->get_formatter();
                $description = sprintf($l10n->get('worked from %s to %s'), $formatter->time($this->start), $formatter->time());
            }

            // Do the actual report
            $this->_report_hours($description, $invoiceable);
        }
        if ($task_guid == '') {
            // We won't be working on anything from now on. Delete existing parameter
            $stat = $this->person->delete_parameter('org.openpsa.mypage', 'workingon');
        } else {
            // Mark the new task work session as started
            $workingon = [
                'task' => $task_guid,
                'description' => $description,
                'invoiceable' => $invoiceable,
                'start' => gmdate('Y-m-d H:i:s', time())
            ];
            $stat = $this->person->set_parameter('org.openpsa.mypage', 'workingon', json_encode($workingon));
        }
        midcom::get()->auth->drop_sudo();
        return $stat;
    }

    /**
     * Report hours based on time used
     */
    private function _report_hours(string $description, bool $invoiceable)
    {
        $hour_report = new org_openpsa_expenses_hour_report_dba();
        $hour_report->invoiceable = $invoiceable;
        $hour_report->date = $this->start;
        $hour_report->person = $this->person->id;
        $hour_report->task = $this->task->id;
        $hour_report->description = $description;
        $hour_report->hours = $this->time / 3600;
        //apply minimum_time_slot
        $hour_report->modify_hours_by_time_slot();

        if (!$hour_report->create()) {
            midcom::get()->uimessages->add(midcom::get()->i18n->get_string('org.openpsa.mypage', 'org.openpsa.mypage'), sprintf(midcom::get()->i18n->get_string('reporting %f hours to task %s failed, reason %s', 'org.openpsa.mypage'), $hour_report->hours, $this->task->title, midcom_connection::get_error_string()), 'error');
        } else {
            midcom::get()->uimessages->add(midcom::get()->i18n->get_string('org.openpsa.mypage', 'org.openpsa.mypage'), sprintf(midcom::get()->i18n->get_string('successfully reported %f hours to task %s', 'org.openpsa.mypage'), $hour_report->hours, $this->task->title));
        }
    }
}
