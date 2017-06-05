<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.documents document handler and viewer class.
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_handler_directory_create extends midcom_baseclasses_components_handler
{
    /**
     * The Controller of the directory used for creating or editing
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
     * The schema to use for the new directory.
     *
     * @var string
     */
    private $_schema = 'default';

    /**
     * This is what Datamanager calls to actually create a directory
     */
    public function & dm2_create_callback(&$datamanager)
    {
        $topic = new org_openpsa_documents_directory();
        $topic->up = $this->_request_data['directory']->id;
        $topic->component = $this->_component;

        // Set the name by default
        $generator = midcom::get()->serviceloader->load('midcom_core_service_urlgenerator');
        $topic->name = $generator->from_string($_POST['extra']);

        if (!$topic->create()) {
            debug_print_r('We operated on this object:', $topic);
            throw new midcom_error("Failed to create a new topic, cannot continue. Error: " . midcom_connection::get_error_string());
        }

        $this->_request_data['directory'] = $topic;

        return $topic;
    }

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     */
    private function _load_create_controller()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_directory'));
        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = $this->_schema;
        $this->_controller->callback_object =& $this;
        if (!$this->_controller->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 create controller.");
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $data['directory']->require_do('midgard:create');

        $this->_load_create_controller();

        midcom::get()->head->set_pagetitle($this->_l10n->get('new directory'));

        $workflow = $this->get_workflow('datamanager2', [
            'controller' => $this->_controller,
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        $indexer = new org_openpsa_documents_midcom_indexer($this->_topic);
        $indexer->index($controller->datamanager);

        return $this->_request_data["directory"]->name. "/";
    }
}
