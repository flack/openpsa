<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\Request;

/**
 * My Tasks handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_list_user extends org_openpsa_projects_handler_task_list
{
    protected $show_status_controls = true;

    public function _handler_list(Request $request, array $args)
    {
        $this->prepare_request_data('my_tasks');

        $this->qb = org_openpsa_projects_task_dba::new_query_builder();
        $this->qb->get_doctrine()
            ->leftJoin('org_openpsa_task_resource', 'r', Join::WITH, 'c.id = r.task')
            ->where('(r.orgOpenpsaObtype = :rtype AND r.person = :user AND c.status IN(:r_statuses)) OR (c.manager = :user AND c.status IN(:m_statuses))')
            ->setParameters([
                'rtype' => org_openpsa_projects_task_resource_dba::RESOURCE,
                'user' => midcom_connection::get_user(),
                'r_statuses' => [
                    org_openpsa_projects_task_status_dba::PROPOSED,
                    org_openpsa_projects_task_status_dba::ACCEPTED,
                    org_openpsa_projects_task_status_dba::STARTED,
                    org_openpsa_projects_task_status_dba::REOPENED,
                    org_openpsa_projects_task_status_dba::COMPLETED
                ],
                'm_statuses' => [
                    org_openpsa_projects_task_status_dba::PROPOSED,
                    org_openpsa_projects_task_status_dba::DECLINED,
                    org_openpsa_projects_task_status_dba::COMPLETED,
                    org_openpsa_projects_task_status_dba::ONHOLD
                ]
            ]);

        return $this->show('show-task-grid');
    }
}
