<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: create.php 25319 2010-03-18 12:44:12Z indeyets $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.documents document handler and viewer class.
 *
 * @package org.openpsa.documents
 *
 */
class org_openpsa_documents_handler_directory_create extends midcom_baseclasses_components_handler
{
    /**
     * The Controller of the directory used for creating or editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     * @access private
     */
    var $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     * @access private
     */
    var $_schemadb = null;

    /**
     * The schema to use for the new directory.
     *
     * @var string
     * @access private
     */
    var $_schema = 'default';

    function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
    }

    /**
     * This is what Datamanager calls to actually create a directory
     */
    function & dm2_create_callback(&$datamanager)
    {
        $topic = new org_openpsa_documents_directory();
        $topic->up = $this->_request_data['directory']->id;
        $topic->component = 'org.openpsa.documents';

        // Set the name by default
        $topic->name = midcom_generate_urlname_from_string($_POST['extra']);

        if (! $topic->create())
        {
            debug_print_r('We operated on this object:', $topic);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to create a new topic, cannot continue. Error: " . midcom_connection::get_error_string());
            // This will exit.
        }

        $this->_request_data['directory'] = new org_openpsa_documents_directory($topic->id);

        return $topic;
    }

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     *
     * @access private
     */
    private function _load_create_controller()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_directory'));
        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = $this->_schema;
        $this->_controller->callback_object =& $this;
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 create controller.");
            // This will exit.
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_create($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_do('midgard:create', $this->_request_data['directory']);

        $this->_load_create_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Index the directory
                $indexer = $_MIDCOM->get_service('indexer');
                $indexer->index($this->_controller->datamanager);

                // Relocate to the new directory view
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                    . $this->_request_data["directory"]->name. "/");
                // This will exit
            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit
        }
        $this->_request_data['controller'] = $this->_controller;

        $tmp = array();

        $tmp[] = array
        (
            MIDCOM_NAV_URL => "",
            MIDCOM_NAV_NAME => $this->_l10n->get('new directory'),
        );

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);


        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this); 
        
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_create($handler_id, &$data)
    {
        midcom_show_style("show-directory-create");
    }
    
}
?>