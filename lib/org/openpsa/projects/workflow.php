<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.projects site interface class.
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_workflow
{
    /**
     *
     * @param string $command
     * @param org_openpsa_projects_task_dba $task
     * @throws midcom_error
     */
    public static function run($command, org_openpsa_projects_task_dba $task)
    {
        if (!method_exists(__CLASS__, $command))
        {
            throw new midcom_error("Method not implemented");
        }
        return static::$command($task);
    }

    /**
     * Returns the icon for a given status
     *
     * @param integer $status The status to convert
     * @return string The icon filename
     */
    public static function get_status_type_icon($status)
    {
        $url = 'document-text.png';
        switch($status)
        {
            case 'ongoing':
                $url = 'page-approved-notpublished.png';
                break;
            case 'on_hold':
                $url = 'page-notapproved.png';
                break;
            case 'closed':
                $url = 'page-approved.png';
                break;
        }
        return $url;
    }

    /**
     * Returns the status type of a given status
     *
     * @param integer $status The status to convert
     * @return string The status type
     */
    public static function get_status_type($status)
    {
        $map = array
        (
            org_openpsa_projects_task_status_dba::REJECTED => 'rejected',
            org_openpsa_projects_task_status_dba::PROPOSED => 'not_started',
            org_openpsa_projects_task_status_dba::DECLINED => 'not_started',
            org_openpsa_projects_task_status_dba::ACCEPTED => 'not_started',
            org_openpsa_projects_task_status_dba::STARTED => 'ongoing',
            org_openpsa_projects_task_status_dba::REOPENED => 'ongoing',
            org_openpsa_projects_task_status_dba::COMPLETED => 'closed',
            org_openpsa_projects_task_status_dba::APPROVED => 'closed',
            org_openpsa_projects_task_status_dba::CLOSED => 'closed',
            org_openpsa_projects_task_status_dba::ONHOLD => 'on_hold'
        );
        if (array_key_exists($status, $map))
        {
            return $map[$status];
        }
        return 'on_hold';
    }

    public static function render_status_control($task)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        if ($task->status < org_openpsa_projects_task_status_dba::COMPLETED)
        {
            $action = 'complete';
            $checked = '';
        }
        else
        {
            if ($task->status == org_openpsa_projects_task_status_dba::COMPLETED)
            {
                $action = 'remove_complete';
            }
            else
            {
                $action = 'reopen';
            }
            $checked = ' checked="checked"';

        }
        $html = '<form method="post" action="' . $prefix . 'workflow/' . $task->guid . '/">';
        $html .= '<input type="hidden" name="org_openpsa_projects_workflow_action[' . $action . ']" value="true" />';
        $html .= '<input type="checkbox"' . $checked . ' name="org_openpsa_projects_workflow_dummy" value="true" onchange="this.form.submit()" />';
        $html .= '</form>';
        return $html;
    }

    /**
     * Shortcut for creating status object
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     * @param integer $status_type The status to convert
     * @param integer $target_person The person ID, if any
     * @param string $comment The status comment, if any
     */
    public static function create_status($task, $status_type, $target_person = 0, $comment = '')
    {
        debug_print_function_stack('create_status called from: ');
        $status = new org_openpsa_projects_task_status_dba();
        if ($target_person != 0)
        {
            $status->targetPerson = $target_person;
        }
        $status->task = $task->id;
        $status->type = $status_type;
        $status->comment = $comment;

        $ret = $status->create();

        if (!$ret)
        {
            debug_add('failed to create status object, errstr: ' . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
        }
        return $ret;
    }

    /**
     * Propose task to a resource
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     * @param integer $pid The person ID
     * @param string $comment Status comment, if any
     */
    public static function propose($task, $pid, $comment = '')
    {
        debug_add("saving proposed status for person {$pid}");
        return self::create_status($task, org_openpsa_projects_task_status_dba::PROPOSED, $pid, $comment);
    }

    /**
     * Accept the proposal
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     * @param integer $pid The person ID
     * @param string $comment Status comment, if any
     */
    public static function accept($task, $pid = -1, $comment = '')
    {
        if ($pid < 0)
        {
            $pid = midcom_connection::get_user();
        }
        debug_add("task->accept() called with user #" . $pid);

        return self::create_status($task, org_openpsa_projects_task_status_dba::ACCEPTED, $pid, $comment);
    }

    /**
     * Decline the proposal
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     */
    static function decline($task, $comment = '')
    {
        debug_add("task->decline() called with user #" . midcom_connection::get_user());

        return self::create_status($task, org_openpsa_projects_task_status_dba::DECLINED, midcom_connection::get_user(), $comment);
    }

    /**
     * Mark task as started (in case it's not already done)
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     */
    public static function start($task, $started_by = 0)
    {
        debug_add("task->start() called with user #" . midcom_connection::get_user());
        //PONDER: Check actual status objects for more accurate logic ?
        if (   $task->status >= org_openpsa_projects_task_status_dba::STARTED
            && $task->status <= org_openpsa_projects_task_status_dba::APPROVED)
        {
            //We already have started status
            debug_add('Task has already been started');
            return true;
        }
        return self::create_status($task, org_openpsa_projects_task_status_dba::STARTED, $started_by);
    }

    /**
     * Mark task as completed
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     */
    public static function complete($task, $comment = '')
    {
        debug_add("task->complete() called with user #" . midcom_connection::get_user());
        //TODO: Check deliverables
        if (!self::create_status($task, org_openpsa_projects_task_status_dba::COMPLETED, 0, $comment))
        {
            return false;
        }
        //PONDER: Check ACL instead ?
        if (   $task->manager == 0
            || midcom_connection::get_user() == $task->manager)
        {
            //Manager marking task completed also approves it at the same time
            debug_add('We\'re the manager of this task (or it is orphaned), approving straight away');
            return self::approve($task, $comment);
        }

        return true;
    }

    /**
     * Drops a completed task to started status
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     */
    static function remove_complete($task, $comment = '')
    {
        debug_add("task->remove_complete() called with user #" . midcom_connection::get_user());
        if ($task->status != org_openpsa_projects_task_status_dba::COMPLETED)
        {
            //Status is not completed, we can't remove that status.
            debug_add('status != completed, aborting');
            return false;
        }
        return self::_drop_to_started($task, $comment);
    }

    /**
     * Drops tasks status to started
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     */
    private static function _drop_to_started($task, $comment = '')
    {
        if ($task->status <= org_openpsa_projects_task_status_dba::STARTED)
        {
            debug_add('Task has not been started, aborting');
            return false;
        }
        return self::create_status($task, org_openpsa_projects_task_status_dba::STARTED, 0, $comment);
    }

    /**
     * Mark task as approved
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     */
    static function approve($task, $comment = '')
    {
        debug_add("task->approve() called with user #" . midcom_connection::get_user());
        //TODO: Check deliverables / Require to be completed first
        //PONDER: Check ACL instead ?
        if (   $task->manager != 0
            && midcom_connection::get_user() != $task->manager)
        {
            debug_add("Current user #" . midcom_connection::get_user() . " is not manager of task, thus cannot approve", MIDCOM_LOG_ERROR);
            return false;
        }

        if (!self::create_status($task, org_openpsa_projects_task_status_dba::APPROVED, 0, $comment))
        {
            return false;
        }
        debug_add('approved tasks get closed at the same time, calling this->close()');
        return self::close($task, $comment);
    }

    static function reject($task, $comment = '')
    {
        debug_add("task->reject() called with user #" . midcom_connection::get_user());
        //TODO: Check deliverables / Require to be completed first
        //PONDER: Check ACL instead ?
        if (midcom_connection::get_user() != $task->manager)
        {
            debug_add("Current user #" . midcom_connection::get_user() . " is not manager of task, thus cannot reject", MIDCOM_LOG_ERROR);
            return false;
        }
        return self::create_status($task, org_openpsa_projects_task_status_dba::REJECTED, 0, $comment);
    }

    /**
     * Drops an approved task to started status
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     */
    static function remove_approve($task, $comment = '')
    {
        debug_add("task->remove_approve() called with user #" . midcom_connection::get_user());
        if ($task->status != org_openpsa_projects_task_status_dba::APPROVED)
        {
            debug_add('Task is not approved, aborting');
            return false;
        }
        return self::_drop_to_started($comment);
    }

    /**
     * Mark task as closed
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     */
    public static function close($task, $comment = '')
    {
        debug_add("task->close() called with user #" . midcom_connection::get_user());
        //TODO: Check deliverables / require to be approved first
        //PONDER: Check ACL instead?
        if (   $task->manager != 0
            && midcom_connection::get_user() != $task->manager)
        {
            debug_add("Current user #" . midcom_connection::get_user() . " is not manager of task, thus cannot close", MIDCOM_LOG_ERROR);
            return false;
        }

        if (self::create_status($task, org_openpsa_projects_task_status_dba::CLOSED, 0, $comment))
        {
            midcom::get()->uimessages->add(midcom::get()->i18n->get_string('org.openpsa.projects', 'org.openpsa.projects'), sprintf(midcom::get()->i18n->get_string('marked task "%s" closed', 'org.openpsa.projects'), $task->title));
            if ($task->agreement)
            {
                $agreement = new org_openpsa_sales_salesproject_deliverable_dba($task->agreement);

                // Set agreement delivered if this is the only open task for it
                $task_qb = org_openpsa_projects_task_dba::new_query_builder();
                $task_qb->add_constraint('agreement', '=', $task->agreement);
                $task_qb->add_constraint('status', '<', org_openpsa_projects_task_status_dba::CLOSED);
                $task_qb->add_constraint('id', '<>', $task->id);
                if ($task_qb->count() == 0)
                {
                    // No other open tasks, mark as delivered
                    $agreement->deliver(false);
                }
                else
                {
                    midcom::get()->uimessages->add(midcom::get()->i18n->get_string('org.openpsa.projects', 'org.openpsa.projects'), sprintf(midcom::get()->i18n->get_string('did not mark deliverable "%s" delivered due to other tasks', 'org.openpsa.sales'), $agreement->title), 'info');
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Reopen a closed task
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     */
    public static function reopen($task, $comment = '')
    {
        debug_add("task->reopen() called with user #" . midcom_connection::get_user());
        if ($task->status != org_openpsa_projects_task_status_dba::CLOSED)
        {
            debug_add('Task is not closed, aborting');
            return false;
        }
        return self::create_status($task, org_openpsa_projects_task_status_dba::REOPENED, 0, $comment);
    }

    /**
     * Connect the task hour reports to an invoice
     *
     * @param org_openpsa_projects_task_dba $task The task we're working on
     * @param org_openpsa_invoices_invoice_dba $invoice The invoice we're working on
     */
    public static function mark_invoiced($task, $invoice)
    {
        debug_add("task->mark_invoiced() called with user #" . midcom_connection::get_user());

        // Mark the hour reports invoiced
        $hours_marked = 0;
        $qb = org_openpsa_projects_hour_report_dba::new_query_builder();
        $qb->add_constraint('task', '=', $task->id);
        $qb->add_constraint('invoice', '=', 0);
        $qb->add_constraint('invoiceable', '=', true);

        // Check how the agreement deals with hour reports
        try
        {
            $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($task->agreement);
            if ($deliverable->invoiceApprovedOnly)
            {
                // The agreement allows invoicing only approved hours, therefore don't mark unapproved
                $qb->add_constraint('metadata.isapproved', '=', true);
            }
        }
        catch (midcom_error $e)
        {
            $e->log();
        }

        $reports = $qb->execute();

        foreach ($reports as $report)
        {
            $report->invoice = $invoice->id;
            $report->_skip_parent_refresh = true;
            if ($report->update())
            {
                $hours_marked += $report->hours;
            }
        }

        // Update hour caches to agreement
        if (!$task->update_cache())
        {
            debug_add('Failed to update task hour caches, last Midgard error: ' . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
        }

        // Notify user
        midcom::get()->uimessages->add(midcom::get()->i18n->get_string('org.openpsa.projects', 'org.openpsa.projects'), sprintf(midcom::get()->i18n->get_string('marked %s hours as invoiced in task "%s"', 'org.openpsa.projects'), $hours_marked, $task->title));
        return $hours_marked;
    }
}
