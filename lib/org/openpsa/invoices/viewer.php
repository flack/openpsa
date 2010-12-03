<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice interface class.
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_viewer extends midcom_baseclasses_components_request
{
    public function _on_initialize()
    {
        // Match /
        $this->_request_switch['dashboard'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_list', 'dashboard'),
        );

        // Match /projects/
        $this->_request_switch['list_projects_uninvoiced'] = array
        (
            'fixed_args' => array('projects'),
            'handler' => Array('org_openpsa_invoices_handler_projects', 'uninvoiced'),
        );

        // Match /list/customer/all/<company guid>
        $this->_request_switch['list_customer_all'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_list', 'customer'),
            'fixed_args' => array('list', 'customer', 'all'),
            'variable_args' => 1,
        );

        // Match /list/deliverable/<deliverable guid>
        $this->_request_switch['list_deliverable_all'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_list', 'deliverable'),
            'fixed_args' => array('list', 'deliverable'),
            'variable_args' => 1,
        );

        // Match /invoice/new/
        $this->_request_switch['invoice_new_nocustomer'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_crud', 'create'),
            'fixed_args' => array('invoice', 'new'),
        );

        // Match /invoice/new/<company guid>
        $this->_request_switch['invoice_new'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_crud', 'create'),
            'fixed_args' => array('invoice', 'new'),
            'variable_args' => 1,
        );

        // Match /invoice/edit/<guid>
        $this->_request_switch['invoice_edit'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_crud', 'update'),
            'fixed_args' => array('invoice', 'edit'),
            'variable_args' => 1,
        );

        // Match /invoice/delete/<guid>
        $this->_request_switch['invoice_delete'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_crud', 'delete'),
            'fixed_args' => array('invoice', 'delete'),
            'variable_args' => 1,
        );

        // Match /invoice/mark_sent/<guid>
        $this->_request_switch['invoice_mark_sent'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_action', 'mark_sent'),
            'fixed_args' => array('invoice', 'mark_sent'),
            'variable_args' => 1,
        );

        // Match /invoice/mark_paid/<guid>
        $this->_request_switch['invoice_mark_paid'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_action', 'mark_paid'),
            'fixed_args' => array('invoice', 'mark_paid'),
            'variable_args' => 1,
        );

        // Match /invoice/recalculation/<guid>
        $this->_request_switch['recalc_invoice'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_action', 'recalculation'),
            'fixed_args' => array('invoice', 'recalculation'),
            'variable_args' => 1,
        );

        // Match /invoice/itemedit/<guid>
        $this->_request_switch['invoice_item_edit'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_action', 'itemedit'),
            'fixed_args' => array('invoice', 'itemedit'),
            'variable_args' => 1,
        );

        // Match /invoice/pdf/<guid>
        $this->_request_switch['create_pdf'] = array
        (
            'fixed_args' => array('invoice' , 'pdf'),
            'handler' => Array('org_openpsa_invoices_handler_crud', 'pdf'),
            'variable_args' => 1,
        );
        // Match /invoice/<guid>
        $this->_request_switch['invoice'] = array
        (
            'handler' => Array('org_openpsa_invoices_handler_crud', 'read'),
            'fixed_args' => array('invoice'),
            'variable_args' => 1,
        );
        // Match /billingdata/create/<guid>
        $this->_request_switch['billing_data_create'] = array
        (
            'handler' => array('org_openpsa_invoices_handler_billingdata', 'create'),
            'fixed_args' => array('billingdata' , 'create'),
            'variable_args' => 1,
        );
        // Match /billingdata/<guid>
        $this->_request_switch['billing_data'] = array
        (
            'handler' => array('org_openpsa_invoices_handler_billingdata', 'billingdata'),
            'fixed_args' => array('billingdata'),
            'variable_args' => 1,
        );

        // Match /config/
        $this->_request_switch['config'] = array
        (
            'handler' => array ('midcom_core_handler_configdm2', 'config'),
            'fixed_args' => array ('config'),
        );

        // Match /goto
        $this->_request_switch['goto'] = array
        (
            'fixed_args' => array('goto'),
            'handler' => Array('org_openpsa_invoices_handler_goto', 'goto'),
        );
    }

    public function _on_handle($handler, $args)
    {
        $_MIDCOM->load_library('org.openpsa.contactwidget');
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.invoices/invoices.css");

        return parent::_on_handle($handler, $args);
    }
}
?>