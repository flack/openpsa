<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_message_message extends midcom_baseclasses_components_handler
{
    use org_openpsa_directmarketing_handler;

    /**
     * The message which has been created
     *
     * @var org_openpsa_directmarketing_campaign_message_dba
     */
    private $_message = null;

    /**
     * @var org_openpsa_directmarketing_campaign_dba
     */
    private $_campaign;

    /**
     * @var datamanager
     */
    private $_datamanager;

    /**
     * Looks up an message to display.
     *
     * @param string $guid The object's GUID
     */
    public function _handler_view($guid, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($guid);
        $this->_campaign = $this->load_campaign($this->_message->campaign);

        $this->_datamanager = datamanager::from_schemadb($this->_config->get('schemadb_message'));
        $this->_datamanager->set_storage($this->_message);

        $this->add_breadcrumb($this->router->generate('message_view', ['guid' => $this->_message->guid]), $this->_message->title);

        $data['message'] = $this->_message;
        $data['campaign'] = $this->_campaign;
        $data['datamanager'] = $this->_datamanager;

        $this->_populate_toolbar();

        $this->bind_view_to_object($this->_message, $this->_datamanager->get_schema()->get_name());
        midcom::get()->metadata->set_request_metadata($this->_message->metadata->revised, $this->_message->guid);
        midcom::get()->head->set_pagetitle($this->_message->title);
        $data['view_message'] = $this->_datamanager->get_content_html();

        return $this->show('show-message');
    }

    private function _populate_toolbar()
    {
        $workflow = $this->get_workflow('datamanager');
        $buttons = [];
        if ($this->_message->can_do('midgard:update')) {
            $buttons[] = $workflow->get_button($this->router->generate('message_edit', ['guid' => $this->_message->guid]), [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]);
        }
        if ($this->_message->can_do('midgard:delete')) {
            $delete_workflow = $this->get_workflow('delete', ['object' => $this->_message]);
            $buttons[] = $delete_workflow->get_button($this->router->generate('message_delete', ['guid' => $this->_message->guid]));
        }
        if ($this->_message->can_do('midgard:update')) {
            $buttons[] = $workflow->get_button($this->router->generate('message_copy', ['guid' => $this->_message->guid]), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('copy message'),
                MIDCOM_TOOLBAR_GLYPHICON => 'clone',
            ]);
        }

        $buttons[] = [
            MIDCOM_TOOLBAR_URL => $this->router->generate('compose4person', [
                'guid' => $this->_message->guid,
                'person' => midcom::get()->auth->user->guid
            ]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('preview message'),
            MIDCOM_TOOLBAR_GLYPHICON => 'search',
            MIDCOM_TOOLBAR_ACCESSKEY => 'p',
            MIDCOM_TOOLBAR_OPTIONS => ['target' => '_BLANK'],
        ];
        $buttons[] = [
            MIDCOM_TOOLBAR_URL => $this->router->generate('message_report', ['guid' => $this->_message->guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("message report"),
            MIDCOM_TOOLBAR_ACCESSKEY => 'r',
            MIDCOM_TOOLBAR_GLYPHICON => 'print',
        ];

        $this->_campaign->get_testers();
        $buttons[] = [
            MIDCOM_TOOLBAR_URL => $this->router->generate('test_send_message', ['guid' => $this->_message->guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("send message to testers"),
            MIDCOM_TOOLBAR_GLYPHICON => 'paper-plane-o',
            MIDCOM_TOOLBAR_ENABLED => (count($this->_campaign->testers) > 0),
        ];

        $mc = org_openpsa_campaign_member::new_collector('campaign', $this->_campaign->id);
        $mc->set_key_property('campaign');
        $mc->execute();
        $keys = $mc->list_keys();

        // Show the message send if there are recipients
        $buttons[] = [
            MIDCOM_TOOLBAR_URL => $this->router->generate('send_message', ['guid' => $this->_message->guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("send message to whole campaign"),
            MIDCOM_TOOLBAR_GLYPHICON => 'paper-plane',
            MIDCOM_TOOLBAR_ENABLED => (count($keys) > 0 && $this->_message->can_do('midgard:update')),
            MIDCOM_TOOLBAR_OPTIONS => [
                'onclick' => "return confirm('" . $this->_l10n->get('are you sure you wish to send this to the whole campaign') . "')",
            ]
        ];
        $this->_view_toolbar->add_items($buttons);
    }
}
