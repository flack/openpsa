<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: list.php 23975 2009-11-09 05:44:22Z rambo $
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
    private $_list_type = false;

    /**
     * Internal helper, loads the datamanager for the current message. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

        if (!$this->_datamanager)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance for messages.");
            // This will exit.
        }
    }

    /**
     * Looks up an message to display.
     */
    public function _handler_list ($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        $this->_list_type = $args[0];
        $this->_campaign = new org_openpsa_directmarketing_campaign_dba($args[1]);
        if (   !$this->_campaign
            || $this->_campaign->node != $this->_topic->id)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The campaign {$args[1]} was not found.");
            // This will exit.
        }

        $this->set_active_leaf('campaign_' . $this->_campaign->id);

        if (   !is_object($this->_campaign)
            || !$this->_campaign->id)
        {
            // TODO: error reporting
            return false;
        }

        $_MIDCOM->load_library('org.openpsa.qbpager');

        $data['campaign'] =& $this->_campaign;
        $this->_load_datamanager();

        return true;
    }

    /**
     * Shows the loaded message.
     */
    public function _show_list ($handler_id, &$data)
    {
        $qb = new org_openpsa_qbpager('org_openpsa_directmarketing_campaign_message_dba', 'campaign_messages');
        $qb->results_per_page = 10;
        $qb->add_order('metadata.created', 'DESC');
        $qb->add_constraint('campaign', '=', $this->_campaign->id);

        $ret = $qb->execute();
        $data['qbpager'] =& $qb;
        midcom_show_style("show-message-list-header");
        if (count($ret) > 0)
        {
            foreach ($ret as $message)
            {
                $this->_datamanager->autoset_storage($message);
                $data['message'] =& $message;
                $data['message_array'] = $this->_datamanager->get_content_html();
                $data['message_class'] = org_openpsa_directmarketing_viewer::get_messagetype_css_class($message->orgOpenpsaObtype);
                midcom_show_style('show-message-list-item');
            }
        }
        midcom_show_style("show-message-list-footer");
    }
}
?>