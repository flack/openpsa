<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Project list handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_project_list extends midcom_baseclasses_components_handler
{
    private function _load_project($identifier)
    {
        $project = new org_openpsa_projects_project($identifier);

        if (!is_object($project))
        {
            return false;
        }
        $project->get_members();

        return $project;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_list($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        // QB queries of projects by status
        $this->_request_data['view'] = 'all';
        $this->_request_data['project_list_results'] = array();
        if (count($args) == 1)
        {
            $this->_request_data['view'] = $args[0];
        }

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => '',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get("back to index"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
            )
        );

        if ($_MIDCOM->auth->can_user_do('midgard:create', null, 'org_openpsa_projects_project'))
        {
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'project/new/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create project"),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
                )
            );
        }
        if (   $this->_request_data['config']->get('list_projects_by_status')
            || $this->_request_data['view'] != 'all')
        {
            // Projects that haven't been started yet
            if (   $this->_request_data['view'] == 'not_started'
                || $this->_request_data['view'] == 'all')
            {
                $this->_list_not_started_projects();
            }

            // Currently going projects
            if (   $this->_request_data['view'] == 'ongoing'
                || $this->_request_data['view'] == 'all')
            {
                $this->_list_ongoing_projects();
            }

            // Projects that are over time
            if (   $this->_request_data['view'] == 'overtime'
                || $this->_request_data['view'] == 'all')
            {
                $this->_list_overtime_projects();
            }

            // Projects that have been completed
            if (   $this->_request_data['view'] == 'completed'
                || $this->_request_data['view'] == 'all')
            {
                $this->_list_completed_projects();
            }
        }
        else
        {
            $this->_list_all_projects();
        }
    }

    private function _list_not_started_projects()
    {
        $this->_request_data['project_list_results']['not_started'] = array();

        $qb = org_openpsa_projects_project::new_query_builder();
        $qb->add_constraint('status', '<', ORG_OPENPSA_TASKSTATUS_STARTED);
        $qb->add_constraint('status', '<>', ORG_OPENPSA_TASKSTATUS_ONHOLD);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECT);

        // Workgroup filtering
        if (   isset($GLOBALS['org_openpsa_core_workgroup_filter'])
            && !is_null($GLOBALS['org_openpsa_core_workgroup_filter'])
            && $GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $qb->add_constraint('orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }

        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $project)
            {
                $this->_request_data['project_list_results']['not_started'][$project->guid] = $project;
            }
        }
    }

    private function _list_ongoing_projects()
    {
        $this->_request_data['project_list_results']['ongoing'] = array();

        $qb = org_openpsa_projects_project::new_query_builder();
        $qb->add_constraint('start', '<', time());
        $qb->add_constraint('status', '>=', ORG_OPENPSA_TASKSTATUS_ACCEPTED);
        $qb->add_constraint('status', '<>', ORG_OPENPSA_TASKSTATUS_ACCEPTED);
        $qb->add_constraint('status', '<>', ORG_OPENPSA_TASKSTATUS_ONHOLD);
        $qb->add_constraint('status', '<', ORG_OPENPSA_TASKSTATUS_COMPLETED);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECT);

        // Workgroup filtering
        if (   isset($GLOBALS['org_openpsa_core_workgroup_filter'])
            && !is_null($GLOBALS['org_openpsa_core_workgroup_filter'])
            && $GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $qb->add_constraint('orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }

        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $project)
            {
                $this->_request_data['project_list_results']['ongoing'][$project->guid] = $project;
            }
        }
    }

    private function _list_overtime_projects()
    {
        $this->_request_data['project_list_results']['overtime'] = array();

        $qb = org_openpsa_projects_project::new_query_builder();
        $qb->add_constraint('end', '<', time());
        $qb->add_constraint('status', '<', ORG_OPENPSA_TASKSTATUS_COMPLETED);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECT);

        // Workgroup filtering
        if (   isset($GLOBALS['org_openpsa_core_workgroup_filter'])
            && !is_null($GLOBALS['org_openpsa_core_workgroup_filter'])
            && $GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $qb->add_constraint('orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }

        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $project)
            {
                $this->_request_data['project_list_results']['overtime'][$project->guid] = $project;
            }
        }
    }

    private function _list_completed_projects()
    {
        $this->_request_data['project_list_results']['completed'] = array();

        $qb = org_openpsa_projects_project::new_query_builder();
        $qb->add_constraint('status', '=', ORG_OPENPSA_TASKSTATUS_CLOSED);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECT);

        // Workgroup filtering
        if (   isset($GLOBALS['org_openpsa_core_workgroup_filter'])
            && !is_null($GLOBALS['org_openpsa_core_workgroup_filter'])
            && $GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $qb->add_constraint('orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }

        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $project)
            {
                $this->_request_data['project_list_results']['completed'][$project->guid] = $project;
            }
        }
    }

    private function _list_all_projects()
    {
        // List *all* projects
        $this->_request_data['project_list_results']['all'] = array();

        $qb = org_openpsa_projects_project::new_query_builder();
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECT);

        // Workgroup filtering
        if (   isset($GLOBALS['org_openpsa_core_workgroup_filter'])
            && !is_null($GLOBALS['org_openpsa_core_workgroup_filter'])
            && $GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $qb->add_constraint('orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }

        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $project)
            {
                $this->_request_data['project_list_results']['all'][$project->guid] = $project;
            }
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_list($handler_id, &$data)
    {
        // Locate Contacts node for linking
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');

        if ($this->_request_data['view'] == 'all')
        {
            // The main listing view, list summary of each status
            foreach ($this->_request_data['project_list_results'] as $status => $results)
            {
                $this->_request_data['project_list_status'] = $status;
                $this->_request_data['project_list_items'] = $results;

                if (!$this->_request_data['config']->get('list_projects_by_status'))
                {
                    midcom_show_style("show-project-list-status-header");
                    foreach ($this->_request_data['project_list_results'][$this->_request_data['view']] as $guid => $project)
                    {
                        $this->_request_data['project'] = $this->_load_project($guid);
                        midcom_show_style("show-project-list-status-item");
                    }
                    midcom_show_style("show-project-list-status-footer");
                }
                else
                {
                    midcom_show_style("show-project-list-status-summary");
                }
            }
        }
        else
        {
            // Listing of one status, show verbose output
            midcom_show_style("show-project-list-status-header");
            foreach ($this->_request_data['project_list_results'][$this->_request_data['view']] as $guid => $project)
            {
                $this->_request_data['project'] = $this->_load_project($guid);
                midcom_show_style("show-project-list-status-item");
            }
            midcom_show_style("show-project-list-status-footer");
        }
    }
}
?>