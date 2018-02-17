<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Agreement tasks handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_list_agreement extends org_openpsa_projects_handler_task_list
{
    protected $show_customer = false;

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $this->prepare_request_data('agreement');

        $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached((int) $args[0]);
        $title = sprintf($this->_l10n->get('tasks for agreement %s'), $deliverable->title);
        midcom::get()->head->set_pagetitle($title);
        $this->add_breadcrumb("", $title);

        $this->qb = org_openpsa_projects_task_dba::new_query_builder();
        $this->qb->add_constraint('agreement', '=', $deliverable->id);
        $this->provider->add_order('end', 'DESC');

        $this->add_filters('agreement');
        $data['table-heading'] = 'agreement tasks';

        return $this->show('show-task-grid');
    }
}
