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
     *
     * @var string
     */
    private $action;

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_action($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        if (empty($this->action)) {
            $this->action = $args[1];
        }
        $task = new org_openpsa_projects_task_dba($args[0]);

        if (!org_openpsa_projects_workflow::run($this->action, $task)) {
            throw new midcom_error('Error when saving: ' . midcom_connection::get_error_string());
        }
        //TODO: return ajax status
        return new midcom_response_json;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_post($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        //Look for action among POST variables, then load main handler...
        if (   empty($_POST['org_openpsa_projects_workflow_action'])
            || !is_array($_POST['org_openpsa_projects_workflow_action'])) {
            throw new midcom_error('Incomplete request');
        }

        //Go trough the array, in theory it should have only one element and in any case only the last of them will be processed
        foreach (array_keys($_POST['org_openpsa_projects_workflow_action']) as $action) {
            $this->action = $action;
        }

        $this->_handler_action($handler_id, $args, $data);

        if (isset($_POST['org_openpsa_projects_workflow_action_redirect'])) {
            return new midcom_response_relocate($_POST['org_openpsa_projects_workflow_action_redirect']);
        }
        //NOTE: This header might not be trustworthy...
        return new midcom_response_relocate($_SERVER['HTTP_REFERER']);
    }
}
