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
    private $_datamanager;

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
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_salesproject, $this->_schema);
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for document {$this->_document->id}.");
        }
    }

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     */
    private function _load_create_controller()
    {
        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = $this->_schema;
        $this->_controller->callback_object =& $this;
        $this->_controller->defaults =& $this->_defaults;
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 create controller.");
        }
    }

    private function _initialize_datamanager($schemadb_snippet)
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');

        // Initialize the datamanager with the schema
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($schemadb_snippet);

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        if (!$this->_datamanager)
        {
            throw new midcom_error("Datamanager could not be instantiated.");
        }
    }

    /**
     * Helper function to alter the schema based on the current operation
     */
    private function _modify_schema()
    {
        $fields =& $this->_schemadb['default']->fields;
        $fields['customer']['type_config']['options'] = org_openpsa_helpers_list::task_groups($this->_salesproject);
    }

    private function _load_salesproject($identifier)
    {
        $salesproject = new org_openpsa_sales_salesproject_dba($identifier);

        $this->_salesproject =& $salesproject;

        if (!isset($this->_datamanager))
        {
            $this->_initialize_datamanager($this->_config->get('schemadb_salesproject'));
        }

        $this->_modify_schema();

        // Load the project to datamanager
        if (!$this->_datamanager->autoset_storage($salesproject))
        {
            return false;
        }

        return $salesproject;
    }

    /**
     * This is what Datamanager calls to actually create a salesproject
     */
    function & dm2_create_callback(&$datamanager)
    {
        $salesproject = new org_openpsa_sales_salesproject_dba();

        if (! $salesproject->create())
        {
            debug_print_r('We operated on this object:', $salesproject);
            throw new midcom_error("Failed to create a new invoice. Error: " . midcom_connection::get_error_string());
        }

        $this->_salesproject =& $salesproject;

        return $salesproject;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        $this->_request_data['salesproject'] = $this->_load_salesproject($args[0]);
        $this->_salesproject->require_do('midgard:update');

        $this->_load_edit_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Fall-through intentional
            case 'cancel':
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                    . "salesproject/" . $this->_salesproject->guid);
        }
        $this->_request_data['controller'] =& $this->_controller;

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $this->_view_toolbar->bind_to($this->_salesproject);

        org_openpsa_sales_viewer::add_breadcrumb_path($data['salesproject'], $this);
        $this->add_breadcrumb("", sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('salesproject')));

        $_MIDCOM->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_salesproject->title));
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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_new($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        midcom::get('auth')->require_user_do('midgard:create', null, 'org_openpsa_sales_salesproject_dba');

        $this->_defaults['code'] = org_openpsa_sales_salesproject_dba::generate_salesproject_number();
        $this->_defaults['owner'] = midcom_connection::get_user();

        if (!isset($this->_datamanager))
        {
            $this->_initialize_datamanager($this->_config->get('schemadb_salesproject'));
        }

        $this->_load_create_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Relocate to main view
                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $_MIDCOM->relocate($prefix . "salesproject/edit/" . $this->_salesproject->guid . "/");
                // This will exit
            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit
        }
        $this->_request_data['controller'] =& $this->_controller;

        $_MIDCOM->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('salesproject')));

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
}
?>