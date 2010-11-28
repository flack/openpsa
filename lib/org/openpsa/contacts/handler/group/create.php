<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: create.php 26292 2010-06-08 08:18:31Z gudd $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts group handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_group_create extends midcom_baseclasses_components_handler
{
    /**
     * The Controller of the document used for creating
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
     * The schema to use for the new group.
     *
     * @var string
     */
    private $_schema = 'default';

    /**
     * The defaults to use for the new group.
     *
     * @var Array
     */
    private $_defaults = array();

    /**
     * The group we're working with
     *
     * @var org_openpsa_contacts_group_dba
     */
    private $_group = null;

    /**
     * The parent group, if any
     *
     * @var org_openpsa_contacts_group_dba
     */
    private $_parent_group = null;

    function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");
    }

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     */
    private function _load_controller()
    {
        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = $this->_schema;
        $this->_controller->callback_object =& $this;
        $this->_controller->defaults =& $this->_defaults;

        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 create controller.");
            // This will exit.
        }
    }

    /**
     * This is what Datamanager calls to actually create a group
     */
    function & dm2_create_callback(&$datamanager)
    {
        $group = new org_openpsa_contacts_group_dba();

        $group->owner = 0;
        if ($this->_parent_group)
        {
            $group->owner = (int) $this->_parent_group->id;
        }
        else
        {
            $root_group = org_openpsa_contacts_interface::find_root_group();
            $group->owner = (int) $root_group->id;
        }
        $group->name = time();

        if (! $group->create())
        {
            debug_print_r('We operated on this object:', $group);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to create a new invoice, cannot continue. Error: " . midcom_connection::get_error_string());
            // This will exit.
        }

        $this->_group =& $group;

        return $group;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_create($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $this->_parent_group = false;
        if (count($args) > 0)
        {
            // Get the parent organization
            $this->_parent_group = new org_openpsa_contacts_group_dba($args[0]);

            if (!$this->_parent_group
                || !$this->_parent_group->guid)
            {
                return false;
            }

            $_MIDCOM->auth->require_do('midgard:create', $this->_parent_group);

            // Set the default type to "department"
            $this->_defaults['object_type'] = ORG_OPENPSA_OBTYPE_DEPARTMENT;
        }
        else
        {
            // This is a root level organization, require creation permissions under the component root group
            $_MIDCOM->auth->require_user_do('midgard:create', null, 'org_openpsa_contacts_group_dba');
        }

        $this->_load_controller();
        switch ($this->_controller->process_form())
        {
            case 'save':
                // Index the organization
                $indexer = $_MIDCOM->get_service('indexer');
                org_openpsa_contacts_viewer::index_group($this->_controller->datamanager, $indexer, $this->_content_topic);

                // Relocate to group view
                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $_MIDCOM->relocate($prefix . "group/" . $this->_group->guid . "/");
                // This will exit

            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit
        }
        $this->_request_data['controller'] =& $this->_controller;

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $_MIDCOM->set_pagetitle($this->_l10n->get("create organization"));

        org_openpsa_contacts_viewer::add_breadcrumb_path_for_group($this->_parent_group, $this);
        $this->add_breadcrumb("", sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('organization')));

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_create($handler_id, &$data)
    {
        midcom_show_style("show-group-create");
    }
}
?>