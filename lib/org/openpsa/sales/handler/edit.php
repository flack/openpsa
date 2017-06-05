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
    private $_defaults = [];

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
        if (!$this->_controller->initialize()) {
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
        if (!$this->_controller->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 create controller.");
        }
    }

    private function _load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_salesproject'));
        if ($this->_salesproject) {
            $fields =& $schemadb['default']->fields;
            $fields['customer']['type_config']['options'] = org_openpsa_helpers_list::task_groups($this->_salesproject);
        }
        $this->_schemadb = $schemadb;
    }

    private function _load_defaults(array $args)
    {
        $this->_defaults['code'] = org_openpsa_sales_salesproject_dba::generate_salesproject_number();
        $this->_defaults['owner'] = midcom_connection::get_user();

        if (!empty($args[0])) {
            $fields =& $this->_schemadb['default']->fields;
            try {
                $customer = new org_openpsa_contacts_group_dba($args[0]);
                $fields['customer']['type_config']['options'] = [0 => '', $customer->id => $customer->official];

                $this->_defaults['customer'] = $customer->id;
            } catch (midcom_error $e) {
                $customer = new org_openpsa_contacts_person_dba($args[0]);
                $this->_defaults['customerContact'] = $customer->id;
                $fields['customer']['type_config']['options'] = org_openpsa_helpers_list::task_groups(new org_openpsa_sales_salesproject_dba, 'id', [$customer->id => true]);
            }
            $this->add_breadcrumb("list/customer/{$customer->guid}/", sprintf($this->_l10n->get('salesprojects with %s'), $customer->get_label()));
        }
    }

    /**
     * This is what Datamanager calls to actually create a salesproject
     */
    public function & dm2_create_callback(&$datamanager)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba();

        if (!$this->_salesproject->create()) {
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

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('salesproject')));

        $workflow = $this->get_workflow('datamanager2', ['controller' => $this->_controller]);
        return $workflow->run();
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

        midcom::get()->head->set_pagetitle($this->_l10n->get('create salesproject'));

        $workflow = $this->get_workflow('datamanager2', [
            'controller' => $this->_controller,
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        return "salesproject/" . $this->_salesproject->guid . "/";
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($args[0]);
        $workflow = $this->get_workflow('delete', [
            'object' => $this->_salesproject,
            'recursive' => true
        ]);
        return $workflow->run();
    }
}
