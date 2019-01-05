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
    use org_openpsa_directmarketing_handler;

    /**
     * The message to operate on
     *
     * @var org_openpsa_directmarketing_campaign_message_dba
     */
    private $_message;

    /**
     * Displays a message edit view.
     *
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit(array $args, array &$data)
    {
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $this->_message->require_do('midgard:update');

        $data['campaign'] = $this->load_campaign($this->_message->campaign);

        $dm = datamanager::from_schemadb($this->_config->get('schemadb_message'));
        $dm->set_storage($this->_message);

        midcom::get()->head->set_pagetitle($this->_l10n->get('edit message'));

        $workflow = $this->get_workflow('datamanager', ['controller' => $dm->get_controller()]);
        return $workflow->run();
    }

    /**
     * @param array $args The argument list.
     */
    public function _handler_delete(array $args)
    {
        $message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $campaign = new org_openpsa_directmarketing_campaign_dba($message->campaign);
        $workflow = $this->get_workflow('delete', [
            'object' => $message,
            'success_url' => $this->router->generate('view_campaign', ['guid' => $campaign->guid])
        ]);
        return $workflow->run();
    }

    /**
     * Handle the message copying interface
     *
     * @param array $args The argument list.
     */
    public function _handler_copy(array $args)
    {
        $this->_topic->require_do('midgard:create');
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $this->load_campaign($this->_message->campaign);

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
        $original = $this->_message;
        $copy_data = [];

        foreach ($qb->execute() as $campaign) {
            $new_object = $copy->copy_object($original, $campaign, ['sendStarted' => 0, 'sendCompleted' => 0]);

            // Store for later use
            $copy_data[] = [
                'message' => $new_object,
                'campaign' => $campaign
            ];
        }

        $message = $this->_l10n->get('message was copied to the following campaigns') . '<br><dl>';

        foreach ($copy_data as $cdata) {
            $link = $this->router->generate('view_campaign', ['guid' => $cdata['campaign']->guid]);
            $message .= "<dt><a href=\"{$link}\">{$cdata['campaign']->title}</a></dt>\n";
            $message .= "    <dd><a href=\"{$link}\">{$cdata['message']->title}</a></dd>\n";
        }
        $message .= '</dl>';

        midcom::get()->uimessages->add($this->_l10n->get('copy message'), $message, 'ok');

        return $this->router->generate('message_view', ['guid' => $this->_message->guid]);
    }
}
