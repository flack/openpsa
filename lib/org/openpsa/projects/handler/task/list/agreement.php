<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Agreement tasks handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_list_agreement extends org_openpsa_projects_handler_task_list
{
    protected $show_customer = false;

    public function _handler_list(Request $request, array $args)
    {
        $this->prepare_request_data('agreement');

        $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached((int) $args[0]);
        $title = sprintf($this->_l10n->get('tasks for agreement %s'), $deliverable->title);
        midcom::get()->head->set_pagetitle($title);
        $this->add_breadcrumb("", $title);

        $this->qb = org_openpsa_projects_task_dba::new_query_builder();
        $this->qb->add_constraint('agreement', '=', $deliverable->id);
        $this->provider->add_order('end', 'DESC');

        $this->add_filters('agreement', $request);

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($sales_url = $siteconfig->get_node_full_url('org.openpsa.sales')) {
            $this->_view_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => "{$sales_url}deliverable/{$deliverable->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('agreement'),
                MIDCOM_TOOLBAR_GLYPHICON => 'money',
            ]);
        }

        return $this->show('show-task-grid');
    }
}
