<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\grid\provider;

/**
 * Project tasks handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_list_project extends org_openpsa_projects_handler_task_list
{
    protected $show_status_controls = true;

    protected $is_single_project = true;

    protected $show_customer = false;

    /**
     * @param array $args The argument list.
     * @param array $data The local request data.
     */
    public function _handler_list(array $args, array &$data)
    {
        $this->prepare_request_data('project_tasks');
        $this->prepare_toolbar();

        $data['project'] = new org_openpsa_projects_project($args[0]);

        $this->qb = org_openpsa_projects_task_dba::new_query_builder();
        $this->qb->add_constraint('project', '=', $data['project']->id);
        $this->add_filters('project');
    }

    /**
     * @param array $args The argument list.
     * @param array $data The local request data.
     */
    public function _handler_json(array $args, array &$data)
    {
        $project = new org_openpsa_projects_project($args[0]);
        $this->provider = new provider($this, 'json');

        $this->qb = org_openpsa_projects_task_dba::new_query_builder();
        $this->qb->add_constraint('project', '=', $project->id);
        $this->qb->add_constraint('status', '<', org_openpsa_projects_task_status_dba::COMPLETED);
        $this->qb->add_order('status');
        $this->qb->add_order('end', 'DESC');
        $this->qb->add_order('start');

        midcom::get()->skip_page_style = true;
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');
        $data['provider'] = $this->provider;

        return $this->show('show-json-tasks');
    }
}
