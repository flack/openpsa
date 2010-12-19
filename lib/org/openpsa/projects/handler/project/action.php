<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Project action handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_project_action extends midcom_baseclasses_components_handler
{
    private function _load_project($identifier)
    {
        $project = new org_openpsa_projects_project($identifier);

        if (empty($project->guid))
        {
            return false;
        }

        return $project;
    }

    public function _handler_subscribe($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $this->_request_data['project'] = $this->_load_project($args[0]);
        if (!$this->_request_data['project'])
        {
            return false;
        }

        // Check if the action is a valid one
        $this->_request_data['project_action'] = $args[1];

        // If person is already a member just redirect
        $this->_request_data['project']->get_members();
        if (   array_key_exists(midcom_connection::get_user(), $this->_request_data['project']->resources)
            || array_key_exists(midcom_connection::get_user(), $this->_request_data['project']->contacts))
        {
            $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                . "project/{$this->_request_data['project']->guid}/");
            // This will exit
        }

        if (!$_MIDCOM->auth->can_do('midgard:create', $this->_request_data['project']))
        {
            // We usually want to skip ACL here and allow anybody to subscribe
            $_MIDCOM->auth->request_sudo();
        }
        $_MIDCOM->auth->require_do('midgard:create', $this->_request_data['project']);

        // FIXME: Move this to a method in the project class
        $subscriber = new org_openpsa_projects_task_resource_dba();
        $subscriber->person = midcom_connection::get_user();
        $subscriber->task = $this->_request_data['project']->id;
        $subscriber->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_PROJECTCONTACT;

        if ($subscriber->create())
        {
            $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                . "project/{$this->_request_data['project']->guid}/");
            // This will exit
        }
        else
        {
            throw new midcom_error("Failed to subscribe, reason " . midcom_connection::get_error_string());
        }
    }

    public function _handler_unsubscribe($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $this->_request_data['project'] = $this->_load_project($args[0]);
        if (!$this->_request_data['project'])
        {
            return false;
        }

        // Check if the action is a valid one
        $this->_request_data['project_action'] = $args[1];

        // If person is not a subscriber just redirect
        if (!array_key_exists(midcom_connection::get_user(), $this->_request_data['project']->contacts))
        {
            $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                . "project/{$this->_request_data['project']->guid}/");
            // This will exit
        }

        $qb = org_openpsa_projects_task_resource_dba::new_query_builder();
        $qb->add_constraint('person', '=', midcom_connection::get_user());
        $qb->add_constraint('task', '=', $this->_request_data['project']->id);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECTCONTACT);
        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $subscriber)
            {
                $subscriber->delete();
            }
        }
        $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
            . "project/{$this->_request_data['project']->guid}/");
        // This will exit
    }
}
?>