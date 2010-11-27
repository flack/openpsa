<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: create.php 26503 2010-07-06 12:00:38Z rambo $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts person handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_person_create extends midcom_baseclasses_components_handler
{
    private $_datamanager;

    /**
     * The Controller of the document used for creating
     *
     * @var midcom_helper_datamanager2_controller_simple
     * @access private
     */
    private $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     * @access private
     */
    private $_schemadb = null;

    /**
     * The schema to use for the new person.
     *
     * @var string
     * @access private
     */
    private $_schema = 'default';

    /**
     * The defaults to use for the new person.
     *
     * @var Array
     * @access private
     */
    private $_defaults = array();

    /**
     * The person we're working on, if any
     *
     * @param org_openpsa_contacts_person_dba
     * @access private
     */
    private $_person = null;

    /**
     * The parent group, if any
     *
     * @var org_openpsa_contacts_group_dba
     */
    private $_group = null;

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     *
     * @access private
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

    private function _initialize_datamanager($schemadb_snippet)
    {
        if ($this->_datamanager)
        {
            //already initialized, we can stop now
            return;
        }
        // Initialize the datamanager with the schema
        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($schemadb_snippet);

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        if (!$this->_datamanager)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Datamanager could not be instantiated.");
            // This will exit.
        }
    }

    /**
     * This is what Datamanager calls to actually create an invoice
     */
    function & dm2_create_callback(&$datamanager)
    {
        $person = new org_openpsa_contacts_person_dba();
        $person->firstname = "";
        $person->lastname = "";

        if (! $person->create())
        {
            debug_print_r('We operated on this object:', $person);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to create a new invoice, cannot continue. Error: " . midcom_connection::get_error_string());
            // This will exit.
        }

        $this->_person =& $person;

        return $person;
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
        $_MIDCOM->auth->require_user_do('midgard:create', null, 'org_openpsa_contacts_person_dba');

        if (count($args) > 0)
        {
            // Get the organization
            $this->_group = $this->_load_group($args[0]);

            if (!$this->_group)
            {
                return false;
            }

            // Check permissions
            $_MIDCOM->auth->require_do('midgard:create', $this->_group);
        }

        $this->_initialize_datamanager($this->_config->get('schemadb_person'));

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':

                // Index the person
                $indexer = $_MIDCOM->get_service('indexer');
                org_openpsa_contacts_viewer::index_person($this->_controller->datamanager, $indexer, $this->_content_topic);

                // Add person to group if requested
                if ($this->_group)
                {
                    $member = new midcom_db_member();
                    $member->uid = $this->_person->id;
                    $member->gid = $this->_group->id;
                    $member->create();

                    if (!$member->id)
                    {
                        // TODO: Cleanup
                        $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                            "Failed adding the person to group #{$this->_group->id}, reason {$member->errstr}");
                        // This will exit
                    }
                }

                // Relocate to group view
                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $_MIDCOM->relocate("{$prefix}person/{$this->_person->guid}/");
                // This will exit

            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit
        }
        $this->_request_data['controller'] =& $this->_controller;

        $_MIDCOM->set_pagetitle($this->_l10n->get("create person"));

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        org_openpsa_contacts_viewer::add_breadcrumb_path_for_group($this->_group, $this);
        $this->add_breadcrumb("", sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('person')));

        return true;
    }

    private function _load_group($identifier)
    {
        $group = new org_openpsa_contacts_group_dba($identifier);

        if (!$group
            || !$group->guid)
        {
            return false;
        }

        $_MIDCOM->set_pagetitle($group->official);

        return $group;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_create($handler_id, &$data)
    {
        $this->_request_data['controller'] =& $this->_controller;
        midcom_show_style("show-person-create");
    }
}
?>