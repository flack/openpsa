<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: crud.php 26676 2010-10-03 11:52:46Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice create/read/update/delete handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_crud extends midcom_baseclasses_components_handler_crud
{
    public function __construct()
    {
        $this->_dba_class = 'org_openpsa_invoices_invoice_dba';
    }

    public function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));
        if (   $this->_mode == 'create'
            && count($this->_master->_handler['args']) == 1)
        {
            // We're creating invoice for chosen company
            $this->_request_data['customer'] = new org_openpsa_contacts_group_dba($this->_master->_handler['args'][0]);
        }
        $this->_modify_schema();
    }

    /**
     * Helper function to alter the schema based on the current operation
     */
    private function _modify_schema()
    {
        $fields =& $this->_schemadb['default']->fields;
        // Fill VAT select
        $vat_array = explode(',', $this->_config->get('vat_percentages'));
        if (   is_array($vat_array)
            && count($vat_array) > 0)
        {
            $vat_values = array();
            foreach ($vat_array as $vat)
            {
                $vat_values[$vat] = "{$vat}%";
            }
            $fields['vat']['type_config']['options'] = $vat_values;
        }
        if (isset($this->_object->customerContact))
        {
            if ($this->_object->customerContact)
            {
                // List customer contact's groups
                $organizations = array();
                $member_mc = midcom_db_member::new_collector('uid', $this->_object->customerContact);
                $member_mc->add_value_property('gid');
                $member_mc->execute();
                $memberships = $member_mc->list_keys();
                foreach ($memberships as $guid => $member)
                {
                    $organization = org_openpsa_contacts_group_dba::get_cached($member_mc->get_subkey($guid, 'gid'));
                    $organizations[$organization->id] = $organization->official;
                }
                //Fill the customer field to DM
                $fields['customer']['type_config']['options'] = $organizations;
            }
            else if ($this->_object->customer)
            {
                $customer = org_openpsa_contacts_group_dba::get_cached($this->_object->customer);
                $this->_populate_schema_contacts_for_customer($customer);
            }

            if ($this->_object->sent)
            {
                $fields['sent']['hidden'] = false;
            }

            if ($this->_object->paid)
            {
                $fields['paid']['hidden'] = false;
            }
        }
        else
        {
            if (array_key_exists('customer', $this->_request_data))
            {
                $this->_populate_schema_contacts_for_customer($this->_request_data['customer']);
            }
            else if ($this->_object
                     && $this->_object->customer)
            {
                $this->_request_data['customer'] = org_openpsa_contacts_group_dba::get_cached($this->_object->customer);
                $this->_populate_schema_contacts_for_customer($this->_request_data['customer']);
            }
            else
            {
                // We don't know company, present customer contact as contactchooser and hide customer field
                $fields['customer']['hidden'] = true;
            }
        }

        if ($this->_config->get('invoice_pdf_class_file'))
        {
            $fields['pdf_file']['hidden'] = false;
        }
        $fields['date']['default'] = strftime('%Y-%m-%d');
    }

    private function _populate_schema_contacts_for_customer(&$customer)
    {
        $fields =& $this->_schemadb['default']->fields;
        // We know the customer company, present contact as a select widget
        $persons_array = array();
        $member_mc = midcom_db_member::new_collector('gid', $customer->id);
        $member_mc->add_value_property('uid');
        $member_mc->execute();
        $members = $member_mc->list_keys();
        foreach ($members as $guid => $member)
        {
            $person = org_openpsa_contacts_person_dba::get_cached($member_mc->get_subkey($guid, 'uid'));
            $persons_array[$person->id] = $person->rname;
        }
        asort($persons_array);
        $fields['customerContact']['widget'] = 'select';
        $fields['customerContact']['type_config']['options'] = $persons_array;

        // And display the organization too
        $organization_array = Array();
        $organization_array[$customer->id] = $customer->official;

        $fields['customer']['widget'] = 'select';
        $fields['customer']['type_config']['options'] = $organization_array;
    }

    /**
     * This is what Datamanager calls to actually create an invoice
     */
    function & dm2_create_callback(&$datamanager)
    {
        $invoice = new org_openpsa_invoices_invoice_dba();

        if (! $invoice->create())
        {
            debug_print_r('We operated on this object:', $invoice);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to create a new invoice, cannot continue. Error: " . midcom_connection::get_error_string());
            // This will exit.
        }

        $this->_object =& $invoice;

        return $invoice;
    }

    function _load_defaults()
    {
        $this->_defaults['date'] = time();

        // Set default due date
        if (array_key_exists('customer', $this->_request_data))
        {
            $dummy = new org_openpsa_invoices_invoice_dba();
            $dummy->customer = $this->_request_data['customer']->id;
            $this->_defaults['due'] = ($dummy->get_default_due() * 3600 * 24) + time();
            $this->_defaults['vat'] = $dummy->get_default_vat();
        }
        else
        {
            $due_date = ($this->_config->get('default_due_days') * 3600 * 24) + time();
            $this->_defaults['due'] = $due_date;
        }

        // Generate invoice number
        $this->_defaults['number'] = org_openpsa_invoices_invoice_dba::generate_invoice_number();
        $this->_defaults['owner'] = midcom_connection::get_user();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_callback($handler_id, $args, &$data)
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/ui-elements.css");

        if ($this->_mode == 'read')
        {
            $this->_count_invoice_hours();
            org_openpsa_core_ui::enable_jqgrid();

            $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");
        }

        return true;
    }

    private function _count_invoice_hours()
    {
        $qb = org_openpsa_projects_hour_report_dba::new_query_builder();
        $qb->add_constraint('invoice', '=', $this->_object->id);
        $reports = $qb->execute();
        if (!is_array($reports)
            || sizeof($reports) < 1)
        {
            return false;
        }

        $this->_request_data['sorted_reports'] = array
        (
            'reports' => array(),
            'approved' => array
            (
                'hours' => 0,
                'reports' => array(),
            ),
            'not_approved' => array
            (
                'hours' => 0,
                'reports' => array(),
            ),
            // TODO other sorts ?
        );
        foreach ($reports as $report)
        {
            $this->_request_data['sorted_reports']['reports'][$report->guid] = $report;
            if  ($report->is_approved())
            {
                $sort =& $this->_request_data['sorted_reports']['approved'];
            }
            else
            {
                $sort =& $this->_request_data['sorted_reports']['not_approved'];
            }
            $sort['hours'] += $report->hours;

            // PHP5-TODO: Must be copy-by-value
            $sort['reports'][] =& $this->_request_data['sorted_reports']['reports'][$report->guid];
        }
        return true;
    }

    function _populate_toolbar($handler_id)
    {
        if (   $this->_mode == 'update'
            || $this->_mode == 'create')
        {
            // Add toolbar items
            org_openpsa_helpers::dm2_savecancel($this);
        }
        //check if save-pdf should be shown in toolbar & if invoice is unsent
        if($this->_config->get('invoice_pdf_class_file')
            && isset($this->_object)
            && empty($this->_object->sent))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "invoice/pdf/{$this->_object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create pdf for invoice'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/attach.png',
                )
            );
        }

        if ($this->_mode == 'read')
        {
            $this->_populate_read_toolbar($handler_id);
        }
    }

    function _populate_read_toolbar($handler_id)
    {
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "invoice/edit/{$this->_object->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:update', $this->_object),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )
        );

        if (!$this->_object->sent)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "invoice/mark_sent/{$this->_object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('mark sent'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_mail-reply.png',
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:update', $this->_object),
                )
            );
        }
        else if (!$this->_object->paid)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "invoice/mark_paid/{$this->_object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('mark paid'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/ok.png',
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:update', $this->_object),
                )
            );
        }

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "invoice/delete/{$this->_object->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:delete', $this->_object),
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "invoice/itemedit/{$this->_object->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit invoice items'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:update', $this->_object),
            )
        );

        org_openpsa_relatedto_plugin::add_button($this->_view_toolbar, $this->_object->guid);
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param string $handler_id The current handler
     */
    function _update_breadcrumb($handler_id)
    {
        $customer = false;
        if (!empty($this->_object->customer))
        {
            $customer = org_openpsa_contacts_group_dba::get_cached($this->_object->customer);
        }
        else if (array_key_exists('customer', $this->_request_data))
        {
            $customer = $this->_request_data['customer'];
        }
        if (   $customer
            && $customer->guid != "")
        {
            $this->add_breadcrumb("list/customer/all/{$customer->guid}/", $customer->official);
        }

        if ($this->_mode != 'create')
        {
            $this->add_breadcrumb("invoice/" . $this->_object->guid . "/", $this->_l10n->get('invoice') . ' ' . $this->_object->get_label());
        }

        switch ($this->_mode)
        {
            case 'create':
                $this->add_breadcrumb("/", $this->_l10n->get('create invoice'));
                break;
            case 'update':
                $this->add_breadcrumb("", sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('invoice')));
                break;
            case 'delete':
                $this->add_breadcrumb("", sprintf($this->_l10n_midcom->get('delete %s'), $this->_l10n->get('invoice')));
                break;
        }
    }

    /**
     * Method for updating title for current object and handler
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _update_title($handler_id)
    {
        switch ($this->_mode)
        {
            case 'create':
                $view_title = $this->_l10n->get('create invoice');
                break;
            case 'read':
                $view_title = $this->_l10n->get('invoice') . ' ' . $this->_object->get_label();
                break;
            case 'update':
                $view_title = sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('invoice') . ' ' . $this->_object->get_label());
                break;
            case 'delete':
                $view_title = sprintf($this->_l10n_midcom->get('delete %s'), $this->_l10n->get('invoice') . ' ' . $this->_object->get_label());
                break;
        }

        $_MIDCOM->set_pagetitle($view_title);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_pdf($handler_id, $args, &$data)
    {
        $this->_load_pdf_creator();
        try
        {
            $this->_object = new org_openpsa_invoices_invoice_dba($args[0]);
            $this->_request_data['invoice_url'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "/invoice/" . $this->_object->guid . "/";
        }
        catch (Exception $e)
        {
            debug_print_r('Tried to get invoice with following guid :', $args[0]);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Invoice with GUID: " . $args[0] . " does not exist . Error: " . midcom_connection::get_error_string());
            // This will exit.
        }
        //check for manual uploaded pdf-file & if user wants to replace it
        $this->update_attachment = true;
        if (array_key_exists('cancel' , $_POST))
        {
            $_MIDCOM->relocate($this->_request_data['invoice_url']);
        }
        if (!array_key_exists('save' , $_POST))
        {
            //load schema & datamanager to get attachment
            $this->_load_schemadb();
            $this->_load_datamanager();

            if (!empty($this->_datamanager->types['pdf_file']->attachments))
            {
                $this->update_attachment = false;
                foreach ($this->_datamanager->types['pdf_file']->attachments as $attachment)
                {
                    $parameters = $attachment->list_parameters();
                    //check if auto generated parameter is same as md5 in current-file
                    // if not the file was manually uploaded
                    if (   array_key_exists('org.openpsa.invoices' , $parameters)
                        && (array_key_exists('auto_generated' , $parameters['org.openpsa.invoices'])))
                    {
                        $blob = new midgard_blob($attachment->__object);
                        //check if md5 sum equals the one saved in auto_generated
                        if ($parameters['org.openpsa.invoices']['auto_generated'] == md5_file($blob->get_path()))
                        {
                            $this->update_attachment = true;
                        }
                    }
                }
            }
        }
        if ($this->update_attachment)
        {
            $this->_request_data['invoice'] = $this->_object;
            $this->_request_data['customer'] = org_openpsa_contacts_group_dba::get_cached($this->_object->customer);
            $this->_request_data['customer_contact'] = org_openpsa_contacts_person_dba::get_cached($this->_object->customerContact);
            $this->_request_data['billing_data'] = $this->_object->get_billing_data();
            $_MIDCOM->skip_page_style = true;
        }
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_pdf($handler_id, &$data)
    {
        //if attachment was manually uploaded show confirm if file should be replaced
        if($this->update_attachment)
        {
            midcom_show_style('show-pdf');
        }
        else
        {
            midcom_show_style('show-confirm');
        }
    }
    /**
     * helper function to load the class file for pdf creation
     */
    function _load_pdf_creator()
    {
        if ($this->_config->get('invoice_pdf_class_file'))
        {
            try
            {
                require_once($this->_config->get('invoice_pdf_class_file'));
            }
            catch (Exception $e)
            {
                debug_print_r('Tried to require invoice_pdf_class_file :', $this->_config->get('invoice_pdf_class'));
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Could not require pdf class . Error: " . midcom_connection::get_error_string());
                // This will exit.
            }
        }
        else
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "No invoice pdf class was found in config." );
        }
   }

    function _prepare_request_data()
    {
        $this->_request_data['object'] =& $this->_object;
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['controller'] =& $this->_controller;

        if(!empty($this->_object))
        {
            $this->_request_data['invoice_items'] = $this->_object->get_invoice_items();
        }
    }
    /**
     * Method for adding or updating the invoice to the MidCOM indexer service.
     *
     * @param $dm Datamanager2 instance containing the object
     */
    public function _index_object(&$dm)
    {
        $indexer = $_MIDCOM->get_service('indexer');

        $nav = new midcom_helper_nav();
        //get the node to fill the required index-data for topic/component
        $node = $nav->get_node($nav->get_current_node());

        $document = $indexer->new_document($dm);
        $document->topic_guid = $node[MIDCOM_NAV_GUID];
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->storage->object);
        $document->component = $node[MIDCOM_NAV_COMPONENT];

        if($indexer->index($document))
        {
            return true;
        }
        return false;
    }
}
?>