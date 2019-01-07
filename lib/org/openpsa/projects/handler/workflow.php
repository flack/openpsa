<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * org.openpsa.projects workflow handler and viewer class.
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_workflow extends midcom_baseclasses_components_handler
{
    /**
     * @param string $guid The object's GUID
     * @param string $action The requested action
     */
    public function _handler_action($guid, $action)
    {
        midcom::get()->auth->require_valid_user();
        $this->run($action, $guid);
        //TODO: return ajax status
        return new midcom_response_json;
    }

    /**
     * @param Request $request The request object
     * @param string $guid The object's GUID
     */
    public function _handler_post(Request $request, $guid)
    {
        midcom::get()->auth->require_valid_user();
        $action = $request->request->get('org_openpsa_projects_workflow_action');
        if (empty($action)) {
            throw new midcom_error('Incomplete request');
        }
        $this->run(key($action), $guid);

        if ($url = $request->request->get('org_openpsa_projects_workflow_action_redirect')) {
            return new midcom_response_relocate($url);
        }
        //NOTE: This header might not be trustworthy...
        return new midcom_response_relocate($request->server->get('HTTP_REFERER'));
    }

    private function run($action, $identifier)
    {
        $task = new org_openpsa_projects_task_dba($identifier);
        if (!org_openpsa_projects_workflow::run($action, $task)) {
            throw new midcom_error('Error when saving: ' . midcom_connection::get_error_string());
        }
    }
}
