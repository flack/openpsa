<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: viewer.php 25176 2010-02-23 20:10:35Z flack $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.projects site interface class.
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_viewer extends midcom_baseclasses_components_request
{
    /**
     * Constructor.
     */
    function __construct($topic, $config)
    {
        parent::__construct($topic, $config);
    }

    function _on_initialize()
    {

        // Match /project/list/<status>
        $this->_request_switch['project-list-status'] = array
        (
            'handler' => array('org_openpsa_projects_handler_project_list', 'list'),
            'fixed_args' => array('project', 'list'),
            'variable_args' => 1,
        );
        // Match /project/list
        $this->_request_switch['project-list'] = array
        (
            'handler' => array('org_openpsa_projects_handler_project_list', 'list'),
            'fixed_args' => array('project', 'list'),
        );

        // Match /project/edit/<GUID>
        $this->_request_switch['project-edit'] = array
        (
            'handler' => array('org_openpsa_projects_handler_project_crud', 'update'),
            'fixed_args' => array('project', 'edit'),
            'variable_args' => 1,
        );
        // Match /project/<GUID>/<action>
        $this->_request_switch['project-action'] = array
        (
            'handler' => array('org_openpsa_projects_handler_project_action', 'action'),
            'fixed_args' => 'project',
            'variable_args' => 2,
        );
        // Match /project/new
        $this->_request_switch['project-new'] = array
        (
            'handler' => array('org_openpsa_projects_handler_project_crud', 'create'),
            'fixed_args' => array('project', 'new'),
        );
        // Match /project/<GUID>
        $this->_request_switch['project'] = array
        (
            'handler' => array('org_openpsa_projects_handler_project_crud', 'read'),
            'fixed_args' => 'project',
            'variable_args' => 1,
        );

        // Match /task/list/<mode>/<param>/<param2>
        $this->_request_switch['task-list-3'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_list', 'list'),
            'fixed_args' => array('task', 'list'),
            'variable_args' => 3,
        );
        // Match /task/list/<mode>/<param>
        $this->_request_switch['task-list-2'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_list', 'list'),
            'fixed_args' => array('task', 'list'),
            'variable_args' => 2,
        );
        // Match /task/list/<mode>
        $this->_request_switch['task-list-1'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_list', 'list'),
            'fixed_args' => array('task', 'list'),
            'variable_args' => 1,
        );
        // Match /task/list/
        $this->_request_switch['task-list'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_list', 'list'),
            'fixed_args' => array('task', 'list'),
        );

        // Match /task/edit/<GUID>
        $this->_request_switch['task_edit'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_crud', 'update'),
            'fixed_args' => array('task', 'edit'),
            'variable_args' => 1,
        );
        // Match /task/delete/<GUID>
        $this->_request_switch['task_delete'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_crud', 'delete'),
            'fixed_args' => array('task', 'delete'),
            'variable_args' => 1,
        );

        // Match /task/resourcing/<GUID>
        $this->_request_switch['task_resourcing'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_resourcing', 'resourcing'),
            'fixed_args' => array('task', 'resourcing'),
            'variable_args' => 1,
        );
        // Match /task/resourcing/prospects/<GUID>
        $this->_request_switch['task_resourcing_prospects'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_resourcing', 'list_prospects'),
            'fixed_args' => array('task', 'resourcing', 'prospects'),
            'variable_args' => 1,
        );
        // Match /task/resourcing/prospect/<GUID>
        $this->_request_switch['task_resourcing_prospect_slots'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_resourcing', 'prospect_slots'),
            'fixed_args' => array('task', 'resourcing', 'prospect'),
            'variable_args' => 1,
        );

        // Match /task/<GUID>/<action>
        $this->_request_switch['task-action'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_action', 'action'),
            'fixed_args' => 'task',
            'variable_args' => 2,
        );
        // Match /task/new
        $this->_request_switch['task-new'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_crud', 'create'),
            'fixed_args' => array('task', 'new'),
        );
        // Match /task/new/<Target type>/<Target GUID>
        $this->_request_switch['task-new-2'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_crud', 'create'),
            'fixed_args' => array('task', 'new'),
            'variable_args' => 2,
        );
        // Match /task/<GUID>
        $this->_request_switch['task_view'] = array
        (
            'handler' => array('org_openpsa_projects_handler_task_crud', 'read'),
            'fixed_args' => 'task',
            'variable_args' => 1,
        );


        // Match /workflow/<GUID>/<action>
        $this->_request_switch['workflow-action'] = array
        (
            'handler' => array('org_openpsa_projects_handler_workflow', 'action'),
            'fixed_args' => 'workflow',
            'variable_args' => 2,
        );
        // Match /workflow/<GUID>
        $this->_request_switch['workflow'] = array
        (
            'handler' => array('org_openpsa_projects_handler_workflow', 'post'),
            'fixed_args' => 'workflow',
            'variable_args' => 1,
        );

        // Match /csv/<type>
        $this->_request_switch['csv'] = array
        (
            'handler' => array('org_openpsa_projects_handler_csv', 'csv'),
            'fixed_args' => 'csv',
            'variable_args' => 1,
        );

        // Match /
        $this->_request_switch['frontpage'] = array
        (
            'handler' => array('org_openpsa_projects_handler_frontpage', 'frontpage'),
        );

        //If you need any custom switches add them here
    }

    function _on_handle($handler, $args)
    {
        $this->_request_data['view'] = 'default';
        $this->_request_data['config'] = $this->_config;

        // Pass the topic on to handlers
        $this->_request_data['project_topic'] =& $this->_topic;

        // Always run in uncached mode
        $_MIDCOM->cache->content->no_cache();

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . "/org.openpsa.projects/projects.css",
            )
        );

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . "/org.openpsa.core/ui-elements.css",
            )
        );
        
        return parent::_on_handle($handler, $args);
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param org_openpsa_projects_task_dba
     */
    function update_breadcrumb_line($task)
    {
        $tmp = Array();

        while ($task)
        {
            if ($task->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_PROJECT)
            {
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => "project/{$task->guid}/",
                    MIDCOM_NAV_NAME => $task->title,
                );
            }
            else
            {
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => "task/{$task->guid}/",
                    MIDCOM_NAV_NAME => $task->title,
                );
            }
            $task = $task->get_parent();
        }
        $tmp = array_reverse($tmp);
        return $tmp;
    }
}
?>