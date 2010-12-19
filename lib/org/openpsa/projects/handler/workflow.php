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
    private function _load_task($identifier)
    {
        $task = new org_openpsa_projects_task_dba($identifier);

        if (!is_object($task))
        {
            return false;
        }

        return $task;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_action($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        if (!isset($this->_request_data['action']))
        {
            $this->_request_data['action'] = $args[1];
        }
        if (!isset($this->_request_data['reply_mode']))
        {
            $this->_request_data['reply_mode'] = 'ajax';
        }
        $this->_request_data['task'] = $this->_load_task($args[0]);
        if (!$this->_request_data['task'])
        {
            $this->errstr = "Could not fetch task";
            $this->errcode = MIDCOM_ERRCRIT;
            return false;
        }

        if (method_exists($this, '_handle_' . $data['action']))
        {
            return call_user_func(array($this, '_handle_' . $data['action']));
        }
        else
        {
            switch($this->_request_data['reply_mode'])
            {
                case 'ajax':
                    //TODO: return ajax error
                    break;
                default:
                case 'redirect':
                    $this->errstr = "Method not implemented";
                    $this->errcode = MIDCOM_ERRCRIT;
                    break;
            }
        }

        //We should not fall this far trough
        return false;
    }

    private function _handle_accept()
    {
        $stat = org_openpsa_projects_workflow::accept($this->_request_data['task']);
        $errstr = midcom_connection::get_error_string();
        switch($this->_request_data['reply_mode'])
        {
            case 'ajax':
                //TODO: return ajax status
                break;
            default:
            case 'redirect':
                if (!$stat)
                {
                    $this->errstr = "Error {$errstr} when saving";
                    $this->errcode = MIDCOM_ERRCRIT;
                    return false;
                }
                $this->_redirect();
                //This will exit
        }
    }

    private function _handle_decline()
    {
        $stat = org_openpsa_projects_workflow::decline($this->_request_data['task']);
        $errstr = midcom_connection::get_error_string();
        switch($this->_request_data['reply_mode'])
        {
            case 'ajax':
                //TODO: return ajax status
                break;
            default:
            case 'redirect':
                if (!$stat)
                {
                    $this->errstr = "Error {$errstr} when saving";
                    $this->errcode = MIDCOM_ERRCRIT;
                    return false;
                }
                $this->_redirect();
                //This will exit
        }
    }

    private function _handle_complete()
    {
        $stat = org_openpsa_projects_workflow::complete($this->_request_data['task']);
        $errstr = midcom_connection::get_error_string();
        switch($this->_request_data['reply_mode'])
        {
            case 'ajax':
                //TODO: return ajax status
                break;
            default:
            case 'redirect':
                if (!$stat)
                {
                    $this->errstr = "Error {$errstr} when saving";
                    $this->errcode = MIDCOM_ERRCRIT;
                    return false;
                }
                $this->_redirect();
                //This will exit
        }
    }

    private function _handle_remove_complete()
    {
        $stat = org_openpsa_projects_workflow::remove_complete($this->_request_data['task']);;
        $errstr = midcom_connection::get_error_string();
        switch($this->_request_data['reply_mode'])
        {
            case 'ajax':
                //TODO: return ajax status
                break;
            default:
            case 'redirect':
                if (!$stat)
                {
                    $this->errstr = "Error {$errstr} when saving";
                    $this->errcode = MIDCOM_ERRCRIT;
                    return false;
                }
                $this->_redirect();
                //This will exit
        }
    }

    private function _handle_approve()
    {
        $stat = org_openpsa_projects_workflow::approve($this->_request_data['task']);
        $errstr = midcom_connection::get_error_string();
        switch($this->_request_data['reply_mode'])
        {
            case 'ajax':
                //TODO: return ajax status
                break;
            default:
            case 'redirect':
                if (!$stat)
                {
                    $this->errstr = "Error {$errstr} when saving";
                    $this->errcode = MIDCOM_ERRCRIT;
                    return false;
                }
                $this->_redirect();
                //This will exit
        }
    }

    private function _handle_remove_approve()
    {
        $stat = org_openpsa_projects_workflow::remove_approve($this->_request_data['task']);
        $errstr = midcom_connection::get_error_string();
        switch($this->_request_data['reply_mode'])
        {
            case 'ajax':
                //TODO: return ajax status
                break;
            default:
            case 'redirect':
                if (!$stat)
                {
                    $this->errstr = "Error {$errstr} when saving";
                    $this->errcode = MIDCOM_ERRCRIT;
                    return false;
                }
                $this->_redirect();
                //This will exit
        }
    }

    private function _handle_reject()
    {
        $stat = org_openpsa_projects_workflow::reject($this->_request_data['task']);
        $errstr = midcom_connection::get_error_string();
        switch($this->_request_data['reply_mode'])
        {
            case 'ajax':
                //TODO: return ajax status
                break;
            default:
            case 'redirect':
                if (!$stat)
                {
                    $this->errstr = "Error {$errstr} when saving";
                    $this->errcode = MIDCOM_ERRCRIT;
                    return false;
                }
                $this->_redirect();
                //This will exit
        }
    }

    private function _handle_close()
    {
        $stat = org_openpsa_projects_workflow::close($this->_request_data['task']);
        $errstr = midcom_connection::get_error_string();
        switch($this->_request_data['reply_mode'])
        {
            case 'ajax':
                //TODO: return ajax status
                break;
            default:
            case 'redirect':
                if (!$stat)
                {
                    $this->errstr = "Error {$errstr} when saving";
                    $this->errcode = MIDCOM_ERRCRIT;
                    return false;
                }
                $this->_redirect();
                //This will exit
        }
    }

    private function _handle_reopen()
    {
        $stat = org_openpsa_projects_workflow::reopen($this->_request_data['task']);
        $errstr = midcom_connection::get_error_string();
        switch($this->_request_data['reply_mode'])
        {
            case 'ajax':
                //TODO: return ajax status
                break;
            default:
            case 'redirect':
                if (!$stat)
                {
                    $this->errstr = "Error {$errstr} when saving";
                    $this->errcode = MIDCOM_ERRCRIT;
                    return false;
                }
                $this->_redirect();
                //This will exit
        }
    }

    private function _redirect()
    {
        if (   !isset($this->_request_data['redirect_to'])
            || empty($this->_request_data['redirect_to']))
        {
            //Cannot redirect, throw error
        }
        $_MIDCOM->relocate($this->_request_data['redirect_to']);
        //This will exit
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_action($handler_id, &$data)
    {
        //We actually should not ever get this far
        return;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_post($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        //Look for action among POST variables, then load main handler...
        if (   !isset($_POST['org_openpsa_projects_workflow_action'])
            || !is_array($_POST['org_openpsa_projects_workflow_action'])
            || count($_POST['org_openpsa_projects_workflow_action']) == 0)
        {
            //We do not have proper POST available, abort
            return false;
        }

        //Go trough the array, in theory it should have only one element and in any case only the last of them will be processed
        foreach ($_POST['org_openpsa_projects_workflow_action'] as $action => $val)
        {
            $this->_request_data['action'] = $action;
        }

        $this->_request_data['reply_mode'] = 'redirect';
        if (!isset($_POST['org_openpsa_projects_workflow_action_redirect']))
        {
            //NOTE: This might header not be trustworthy...
            $this->_request_data['redirect_to'] = $_SERVER['HTTP_REFERER'];
        }
        else
        {
            $this->_request_data['redirect_to'] = $_POST['org_openpsa_projects_workflow_action_redirect'];
        }
        return $this->_handler_action($handler_id, $args, $data);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_post($handler_id, &$data)
    {
        //We actually should not ever get this far
        return;
    }
}
?>