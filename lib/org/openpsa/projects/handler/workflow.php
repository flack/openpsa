<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.projects workflow handler and viewer class.
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_workflow extends midcom_baseclasses_components_handler
{
    /**
     * @param array $args The argument list.
     */
    public function _handler_action(array $args)
    {
        midcom::get()->auth->require_valid_user();
        $this->run($args[1], $args[0]);
        //TODO: return ajax status
        return new midcom_response_json;
    }

    /**
     * @param array $args The argument list.
     */
    public function _handler_post(array $args)
    {
        midcom::get()->auth->require_valid_user();
        //Look for action among POST variables, then load main handler...
        if (   empty($_POST['org_openpsa_projects_workflow_action'])
            || !is_array($_POST['org_openpsa_projects_workflow_action'])) {
            throw new midcom_error('Incomplete request');
        }
        $this->run(key($_POST['org_openpsa_projects_workflow_action']), $args[0]);

        if (isset($_POST['org_openpsa_projects_workflow_action_redirect'])) {
            return new midcom_response_relocate($_POST['org_openpsa_projects_workflow_action_redirect']);
        }
        //NOTE: This header might not be trustworthy...
        return new midcom_response_relocate($_SERVER['HTTP_REFERER']);
    }

    private function run($action, $identifier)
    {
        $task = new org_openpsa_projects_task_dba($identifier);
        if (!org_openpsa_projects_workflow::run($action, $task)) {
            throw new midcom_error('Error when saving: ' . midcom_connection::get_error_string());
        }
    }
}
