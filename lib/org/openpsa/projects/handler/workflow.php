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
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_action($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        if (!isset($this->_request_data['action']))
        {
            $this->_request_data['action'] = $args[1];
        }
        if (!isset($this->_request_data['reply_mode']))
        {
            $this->_request_data['reply_mode'] = 'ajax';
        }
        $this->_request_data['task'] = new org_openpsa_projects_task_dba($args[0]);

        if (method_exists('org_openpsa_projects_workflow', $data['action']))
        {
            $stat = org_openpsa_projects_workflow::$data['action']($data['task']);
            switch ($this->_request_data['reply_mode'])
            {
                case 'ajax':
                    //TODO: return ajax status
                    break;
                default:
                case 'redirect':
                    if (!$stat)
                    {
                        throw new midcom_error('Error when saving: ' . midcom_connection::get_error_string());
                    }
                    $this->_redirect();
                    //This will exit
            }
        }
        else
        {
            switch ($this->_request_data['reply_mode'])
            {
                case 'ajax':
                    //TODO: return ajax error
                    break;
                default:
                case 'redirect':
                    throw new midcom_error("Method not implemented");
            }
        }

        //We should not fall this far trough
        throw new midcom_error('Unknown error.');
    }

    private function _redirect()
    {
        if (empty($this->_request_data['redirect_to']))
        {
            //Cannot redirect, throw error
        }
        midcom::get()->relocate($this->_request_data['redirect_to']);
        //This will exit
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_post($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        //Look for action among POST variables, then load main handler...
        if (   !isset($_POST['org_openpsa_projects_workflow_action'])
            || !is_array($_POST['org_openpsa_projects_workflow_action'])
            || count($_POST['org_openpsa_projects_workflow_action']) == 0)
        {
            throw new midcom_error('Incomplete request');
        }

        //Go trough the array, in theory it should have only one element and in any case only the last of them will be processed
        foreach ($_POST['org_openpsa_projects_workflow_action'] as $action => $val)
        {
            $this->_request_data['action'] = $action;
        }

        $this->_request_data['reply_mode'] = 'redirect';
        if (!isset($_POST['org_openpsa_projects_workflow_action_redirect']))
        {
            //NOTE: This header might not be trustworthy...
            $this->_request_data['redirect_to'] = $_SERVER['HTTP_REFERER'];
        }
        else
        {
            $this->_request_data['redirect_to'] = $_POST['org_openpsa_projects_workflow_action_redirect'];
        }
        $this->_handler_action($handler_id, $args, $data);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_post($handler_id, array &$data)
    {
        //We actually should not ever get this far
    }
}
?>