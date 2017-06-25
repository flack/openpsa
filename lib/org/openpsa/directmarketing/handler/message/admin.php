<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.net
 * @copyright The Midgard Project, http://www.midgard-project.net
 * @license http://www.gnu.net/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;

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
     * @var org_openpsa_directmarketing_campaign_message_dba
     */
    private $_message;

    /**
     * Displays a message edit view.
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

        $dm = datamanager::from_schemadb($this->_config->get('schemadb_message'));
        $dm->set_storage($this->_message);

        midcom::get()->head->set_pagetitle($this->_l10n->get('edit message'));

        $workflow = $this->get_workflow('datamanager', ['controller' => $dm->get_controller()]);
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
        $workflow = $this->get_workflow('delete', [
            'object' => $message,
            'success_url' => "campaign/{$campaign->guid}/"
        ]);
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
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $this->_master->load_campaign($this->_message->campaign);

        midcom::get()->head->set_pagetitle($this->_l10n->get('copy message'));

        $dm = datamanager::from_schemadb($this->_config->get('schemadb_message_copy'));
        $workflow = $this->get_workflow('datamanager', [
            'controller' => $dm->get_controller(),
            'save_callback' => [$this, 'copy_callback']
        ]);
        return $workflow->run();
    }

    public function copy_callback(controller $controller)
    {
        $copy = new midcom_helper_reflector_copy();
        $campaigns = unserialize($controller->get_form_values()['campaign']);
        $qb = org_openpsa_directmarketing_campaign_dba::new_query_builder();
        $qb->add_constraint('guid', 'IN', $campaigns);
        $campaigns = $qb->execute();
        $original = $this->_message;
        $copy_data = [];

        foreach ($campaigns as $campaign) {
            $new_object = $copy->copy_object($original, $campaign, ['sendStarted' => 0, 'sendCompleted' => 0]);

            // Store for later use
            $copy_data[] = [
                'message' => $new_object,
                'campaign' => $campaign
            ];
        }

        $message = $this->_l10n->get('message was copied to the following campaigns') . '<br><dl>';
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

        foreach ($copy_data as $cdata) {
            $message .= "<dt><a href=\"{$prefix}campaign/{$cdata['campaign']->guid}/\">{$cdata['campaign']->title}</a></dt>\n";
            $message .= "    <dd><a href=\"{$prefix}message/{$cdata['message']->guid}/\">{$cdata['message']->title}</a></dd>\n";
        }
        $message .= '</dl>';

        midcom::get()->uimessages->add($this->_l10n->get('copy message'), $message, 'ok');

        return "message/{$this->_message->guid}/";
    }
}
