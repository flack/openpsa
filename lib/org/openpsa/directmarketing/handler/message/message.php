<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Discussion forum index
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_message_message extends midcom_baseclasses_components_handler
{
    /**
     * The message which has been created
     *
     * @var org_openpsa_directmarketing_campaign_message
     */
    private $_message = null;

    /**
     * @var org_openpsa_directmarketing_campaign_dba
     */
    private $_campaign;

    /**
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager;

    /**
     * Internal helper, loads the datamanager for the current message. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);
    }

    /**
     * Looks up an message to display.
     */
    public function _handler_view ($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $this->_campaign = $this->_master->load_campaign($this->_message->campaign);

        $this->_load_datamanager();
        $this->_datamanager->autoset_storage($this->_message);

        $this->add_breadcrumb("message/{$this->_message->guid}/", $this->_message->title);

        $data['message'] = $this->_message;
        $data['campaign'] = $this->_campaign;
        $data['datamanager'] = $this->_datamanager;

        $this->_populate_toolbar();

        // Populate calendar events for the message
        $this->bind_view_to_object($this->_message, $this->_datamanager->schema->name);
        midcom::get()->metadata->set_request_metadata($this->_message->metadata->revised, $this->_message->guid);
        midcom::get()->head->set_pagetitle($this->_message->title);
    }

    private function _populate_toolbar()
    {
        $workflow = new midcom\workflow\datamanager2;
        if ($this->_message->can_do('midgard:update'))
        {
            $this->_view_toolbar->add_item($workflow->get_button("message/edit/{$this->_message->guid}/", array
            (
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )));
        }
        if ($this->_message->can_do('midgard:delete'))
        {
            $delete_workflow = new midcom\workflow\delete($this->_message);
            $this->_view_toolbar->add_item($delete_workflow->get_button("message/delete/{$this->_message->guid}/"));
        }
        if ($this->_message->can_do('midgard:update'))
        {
            $this->_view_toolbar->add_item($workflow->get_button("message/copy/{$this->_message->guid}/", array
            (
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('copy message'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/editcopy.png',
            )));
        }

        $preview_url = "message/compose/{$this->_message->guid}/";
        if (!empty(midcom::get()->auth->user->guid))
        {
            $preview_url .= midcom::get()->auth->user->guid . '/';
        }
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => $preview_url,
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('preview message'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/view.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'p',
                MIDCOM_TOOLBAR_OPTIONS => array('target' => '_BLANK'),
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "message/report/{$this->_message->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("message report"),
                MIDCOM_TOOLBAR_ACCESSKEY => 'r',
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
            )
        );

        $this->_campaign->get_testers();
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "message/send_test/{$this->_message->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("send message to testers"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_mail-send.png',
                MIDCOM_TOOLBAR_ENABLED => (count($this->_campaign->testers) > 0),
            )
        );

        $mc = org_openpsa_campaign_member::new_collector('campaign', $this->_campaign->id);
        $mc->set_key_property('campaign');
        $mc->execute();
        $keys = $mc->list_keys();

        // Show the message send if there are recipients
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "message/send/{$this->_message->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("send message to whole campaign"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_mail-send.png',
                MIDCOM_TOOLBAR_ENABLED => (count($keys) > 0 && $this->_message->can_do('midgard:update')),
                MIDCOM_TOOLBAR_OPTIONS => array
                (
                    'onclick' => "return confirm('" . $this->_l10n->get('are you sure you wish to send this to the whole campaign') . "')",
                ),
            )
        );
    }

    /**
     * Shows the loaded message.
     */
    public function _show_view ($handler_id, array &$data)
    {
        $data['view_message'] = $this->_datamanager->get_content_html();
        midcom_show_style('show-message');
    }
}
