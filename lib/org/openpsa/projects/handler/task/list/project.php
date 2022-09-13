<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\grid\provider;
use Symfony\Component\HttpFoundation\Request;

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

    public function _handler_list(Request $request, array $args)
    {
        $this->prepare_request_data('project_tasks');
        $this->prepare_toolbar();

        $project = new org_openpsa_projects_project($args[0]);

        $this->qb = org_openpsa_projects_task_dba::new_query_builder();
        $this->qb->add_constraint('project', '=', $project->id);
        $this->add_filters('project', $request);
    }

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
        $data['provider'] = $this->provider;
        $data['priority_array'] = $this->priority_array;

        return $this->show('show-json-tasks');
    }
}
