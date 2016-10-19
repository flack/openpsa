<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.net
 * @copyright The Midgard Project, http://www.midgard-project.net
 * @license http://www.gnu.net/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * directmarketing edit/delete message handler
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_message_admin extends midcom_baseclasses_components_handler
{
    /**
     * The message to operate on
     *
     * @var org_openpsa_directmarketing_campaign_message
     */
    private $_message;

    /**
     * The Controller of the message used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var array
     */
    private $_schemadb;

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message'));
    }

    /**
     * Internal helper, loads the controller for the current message. Any error triggers a 500.
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_message);
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for message {$this->_message->id}.");
        }
    }

    /**
     * Displays an message edit view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $this->_message->require_do('midgard:update');

        $data['campaign'] = $this->_master->load_campaign($this->_message->campaign);

        $this->_load_controller();

        midcom::get()->head->set_pagetitle($this->_l10n->get('edit message'));

        $workflow = $this->get_workflow('datamanager2', array('controller' => $this->_controller));
        return $workflow->run();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $campaign = new org_openpsa_directmarketing_campaign_dba($message->campaign);
        $workflow = $this->get_workflow('delete', array
        (
            'object' => $message,
            'success_url' => "campaign/{$campaign->guid}/"
        ));
        return $workflow->run();
    }

    /**
     * Handle the message copying interface
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_copy($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('midgard:create');
        $guid = $args[0];
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($guid);
        $this->_master->load_campaign($this->_message->campaign);

        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message_copy'));
        $this->_controller = midcom_helper_datamanager2_controller::create('nullstorage');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->initialize();

        midcom::get()->head->set_pagetitle($this->_l10n->get('copy message'));

        $workflow = $this->get_workflow('datamanager2', array
        (
            'controller' => $this->_controller,
            'save_callback' => array($this, 'copy_callback')
        ));
        return $workflow->run();
    }

    public function copy_callback(midcom_helper_datamanager2_controller $controller)
    {
        $copy = new midcom_helper_reflector_copy();
        $campaigns = $this->_controller->datamanager->types['campaign']->convert_to_storage();
        $qb = org_openpsa_directmarketing_campaign_dba::new_query_builder();
        $qb->add_constraint('guid', 'IN', $campaigns);
        $campaigns = $qb->execute();
        $original = $this->_message;
        $copy_data = array();

        foreach ($campaigns as $campaign)
        {
            $new_object = $copy->copy_object($original, $campaign, array('sendStarted' => 0, 'sendCompleted' => 0));

            // Store for later use
            $copy_data[] = array
            (
                'message' => $new_object,
                'campaign' => $campaign
            );
        }

        $message = $this->_l10n->get('message was copied to the following campaigns') . '<br><dl>';
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

        foreach ($copy_data as $cdata)
        {
            $message .= "<dt><a href=\"{$prefix}campaign/{$cdata['campaign']->guid}/\">{$cdata['campaign']->title}</a></dt>\n";
            $message .= "    <dd><a href=\"{$prefix}message/{$cdata['message']->guid}/\">{$cdata['message']->title}</a></dd>\n";
        }
        $message .= '</dl>';

        midcom::get()->uimessages->add($this->_l10n->get('copy message'), $message, 'ok');

        return "message/{$this->_message->guid}/";
    }
}
