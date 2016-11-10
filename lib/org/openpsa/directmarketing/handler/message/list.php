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
class org_openpsa_directmarketing_handler_message_list extends midcom_baseclasses_components_handler
{
    private $_campaign = false;

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
    public function _handler_list($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        $this->_campaign = $this->_master->load_campaign($args[1]);

        $data['campaign'] = $this->_campaign;
        $this->_load_datamanager();
    }

    /**
     * Shows the loaded message.
     */
    public function _show_list($handler_id, array &$data)
    {
        $qb = new org_openpsa_qbpager('org_openpsa_directmarketing_campaign_message_dba', 'campaign_messages');
        $qb->results_per_page = 10;
        $qb->add_order('metadata.created', 'DESC');
        $qb->add_constraint('campaign', '=', $this->_campaign->id);

        $ret = $qb->execute();
        $data['qbpager'] = $qb;
        midcom_show_style('show-message-list-header');

        foreach ($ret as $message)
        {
            $this->_datamanager->autoset_storage($message);
            $data['message'] = $message;
            $data['message_array'] = $this->_datamanager->get_content_html();
            $data['message_class'] = $message->get_css_class();
            midcom_show_style('show-message-list-item');
        }
        midcom_show_style('show-message-list-footer');
    }
}
