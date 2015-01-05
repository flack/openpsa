<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Salesproject edit/create handler
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_edit extends midcom_baseclasses_components_handler
{
    /**
     * The Controller of the document used for creating or editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    /**
     * The schema to use for the new salesproject.
     *
     * @var string
     */
    private $_schema = 'default';

    /**
     * The defaults to use for the new salesproject.
     *
     * @var Array
     */
    private $_defaults = array();

    /**
     * The salesproject we're working, if any
     *
     * @param org_openpsa_sales_salesproject_dba
     */
    private $_salesproject = null;

    /**
     * Internal helper, loads the controller for the current salesproject. Any error triggers a 500.
     */
    private function _load_edit_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_salesproject, $this->_schema);
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for salesproject {$this->_salesproject->id}.");
        }
    }

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     */
    private function _load_create_controller(array $args)
    {
        $this->_load_schemadb();
        $this->_load_defaults($args);

        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = $this->_schema;
        $this->_controller->callback_object =& $this;
        $this->_controller->defaults = $this->_defaults;
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 create controller.");
        }
    }

    private function _load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_salesproject'));
        if ($this->_salesproject)
        {
            $fields =& $schemadb['default']->fields;
            $fields['customer']['type_config']['options'] = org_openpsa_helpers_list::task_groups($this->_salesproject);
        }
        $this->_schemadb = $schemadb;
    }

    private function _load_defaults(array $args)
    {
        $this->_defaults['code'] = org_openpsa_sales_salesproject_dba::generate_salesproject_number();
        $this->_defaults['owner'] = midcom_connection::get_user();

        if (!empty($args[0]))
        {
            try
            {
                $customer = new org_openpsa_contacts_group_dba($args[0]);
                $fields =& $this->_schemadb['default']->fields;
                $fields['customer']['type_config']['options'] = array(0 => '', $customer->id => $customer->official);

                $this->_defaults['customer'] = $customer->id;
            }
            catch (midcom_error $e)
            {
                $customer = new org_openpsa_contacts_person_dba($args[0]);
                $this->_defaults['customerContact'] = $customer->id;
            }
            $this->add_breadcrumb("list/customer/{$customer->guid}/", sprintf($this->_l10n->get('salesprojects with %s'), $customer->get_label()));
        }
    }

    /**
     * This is what Datamanager calls to actually create a salesproject
     */
    function & dm2_create_callback(&$datamanager)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba();

        if (! $this->_salesproject->create())
        {
            debug_print_r('We operated on this object:', $this->_salesproject);
            throw new midcom_error("Failed to create a new invoice. Error: " . midcom_connection::get_error_string());
        }

        return $this->_salesproject;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($args[0]);
        $this->_salesproject->require_do('midgard:update');

        $this->_load_edit_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Fall-through intentional
            case 'cancel':
                return new midcom_response_relocate("salesproject/" . $this->_salesproject->guid);
        }
        $this->_request_data['controller'] = $this->_controller;
        $this->_request_data['salesproject'] = $this->_salesproject;

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $this->_view_toolbar->bind_to($this->_salesproject);
        $customer = $this->_salesproject->get_customer();
        if ($customer)
        {
            $this->add_breadcrumb("list/customer/{$customer->guid}/", $customer->get_label());
        }
        org_openpsa_sales_viewer::add_breadcrumb_path($this->_salesproject, $this);
        $this->add_breadcrumb("", sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('salesproject')));

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_salesproject->title));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('show-salesproject-edit');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_new($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('midgard:create', null, 'org_openpsa_sales_salesproject_dba');

        $this->_load_create_controller($args);

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Relocate to main view
                return new midcom_response_relocate("salesproject/edit/" . $this->_salesproject->guid . "/");

            case 'cancel':
                return new midcom_response_relocate('');
        }
        $this->_request_data['controller'] = $this->_controller;

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('salesproject')));

        $this->add_breadcrumb('', $this->_l10n->get('create salesproject'));

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_new($handler_id, array &$data)
    {
        midcom_show_style('show-salesproject-new');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($args[0]);
        $workflow = new midcom\workflow\delete($this->_salesproject);
        $workflow->method = 'delete_tree';
        if ($workflow->run())
        {
            return new midcom_response_relocate("");
        }
        return new midcom_response_relocate("salesproject/" . $this->_salesproject->guid);
    }
}
