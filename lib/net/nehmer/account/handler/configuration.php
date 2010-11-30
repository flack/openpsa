<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: configuration.php 3757 2006-07-27 14:32:42Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Component configuration screen.
 *
 * @package net.nehmer.account
 */
class net_nehmer_account_handler_configuration extends midcom_baseclasses_components_handler
{
    /**
     * The Controller of the gallery used for editing
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
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['node'] =& $this->_topic;
        $this->_request_data['controller'] =& $this->_controller;
    }

    /**
     * Internal helper, loads the controller for the current photo. Any error triggers a 500.
     *
     * @access private
     */
    private function _load_controller()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_config'));

        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_topic);
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for photo {$this->_photo->id}.");
            // This will exit.
        }
    }

    /**
     * Displays a config edit view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_configuration($handler_id, $args, &$data)
    {
        $this->_topic->require_do('midgard:update');

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.account'), $this->_l10n->get('configuration saved'));
                $_MIDCOM->relocate('');
                break;

            case 'cancel':
                $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.account'), $this->_l10n->get('cancelled'));
                $_MIDCOM->relocate('');
                // This will exit.
        }

        $this->_prepare_request_data();
        $_MIDCOM->set_26_request_metadata($this->_topic->metadata->revised, $this->_topic->guid);
        $_MIDCOM->set_pagetitle("{$this->_topic->extra}: " . $this->_l10n_midcom->get('component configuration'));
        $this->add_breadcrumb("config/", $this->_l10n_midcom->get('component configuration', 'midcom'));

        return true;
    }

    /**
     * Shows the loaded photo.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_configuration($handler_id, &$data)
    {
        midcom_show_style('admin-config');
    }
}
?>