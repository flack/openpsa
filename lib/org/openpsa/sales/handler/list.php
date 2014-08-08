<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Sales project list handler
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_list extends midcom_baseclasses_components_handler
{
    /**
     * The list of salesprojects.
     *
     * @var Array
     */
    private $_salesprojects = array();

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        // Locate Contacts node for linking
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');
        $data['reports_url'] = $siteconfig->get_node_full_url('org.openpsa.reports');

        $qb = org_openpsa_sales_salesproject_dba::new_query_builder();

        if ($handler_id == 'list_state')
        {
            $qb = $this->_add_state_constraint($args[0], $qb);
            $data['mode'] = $args[0];
            $data['list_title'] = $this->_l10n->get('salesprojects ' . $args[0]);
        }
        else
        {
            $qb = $this->_add_customer_constraint($args[0], $qb);
            $data['mode'] = 'customer';
            $data['list_title'] = sprintf($this->_l10n->get('salesprojects with %s'), $data['customer']->get_label());

            if ($data['contacts_url'])
            {
                $this->_view_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => $data['contacts_url'] . (is_a($data['customer'], 'org_openpsa_contacts_group_dba') ? 'group' : 'person') . "/{$data['customer']->guid}/",
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to customer'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/jump-to.png',
                    )
                );
            }
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'salesproject/new/' . $data['customer']->guid . '/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create salesproject'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people.png',
                    MIDCOM_TOOLBAR_ENABLED => midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_sales_salesproject_dba'),
                )
            );
        }

        $this->_salesprojects = $qb->execute();

        foreach ($this->_salesprojects as $salesproject)
        {
            // Populate previous/next actions in the project
            $salesproject->get_actions();
        }
        // TODO: Filtering

        $data['grid'] = new org_openpsa_widgets_grid($data['mode'] . '_salesprojects_grid', 'local');
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.core/table2csv.js');

        $this->add_breadcrumb("", $data['list_title']);
    }

    private function _add_state_constraint($state, midcom_core_query $qb)
    {
        $code = 'org_openpsa_sales_salesproject_dba::STATE_' . strtoupper($state);
        if (!defined($code))
        {
            throw new midcom_error('Unknown list type ' . $state);
        }

        $qb->add_constraint('state', '=', constant($code));
        return $qb;
    }

    private function _add_customer_constraint($guid, midcom_core_query $qb)
    {
        try
        {
            $this->_request_data['customer'] = new org_openpsa_contacts_group_dba($guid);
            $qb->add_constraint('customer', '=', $this->_request_data['customer']->id);
        }
        catch (midcom_error $e)
        {
            $this->_request_data['customer'] = new org_openpsa_contacts_person_dba($guid);
            $qb->add_constraint('customerContact', '=', $this->_request_data['customer']->id);
        }
        return $qb;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        $data['salesprojects'] = $this->_salesprojects;

        midcom_show_style('show-salesproject-grid');
    }
}
