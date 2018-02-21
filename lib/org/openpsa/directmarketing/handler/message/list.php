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
class org_openpsa_directmarketing_handler_message_list extends midcom_baseclasses_components_handler
{
    use org_openpsa_directmarketing_handler;

    private $_campaign;

    /**
     * @var datamanager
     */
    private $datamanager;

    /**
     * Looks up an message to display.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        $this->_campaign = $this->load_campaign($args[1]);

        $data['campaign'] = $this->_campaign;
        $this->datamanager = datamanager::from_schemadb($this->_config->get('schemadb_message'));
    }

    /**
     * Shows the loaded message.
     */
    public function _show_list($handler_id, array &$data)
    {
        $qb = new org_openpsa_qbpager(org_openpsa_directmarketing_campaign_message_dba::class, 'campaign_messages');
        $qb->results_per_page = 10;
        $qb->add_order('metadata.created', 'DESC');
        $qb->add_constraint('campaign', '=', $this->_campaign->id);

        $ret = $qb->execute();
        $data['qbpager'] = $qb;
        midcom_show_style('show-message-list-header');

        foreach ($ret as $message) {
            $this->datamanager->set_storage($message);
            $data['message'] = $message;
            $data['message_array'] = $this->datamanager->get_content_html();
            $data['message_class'] = $message->get_css_class();
            midcom_show_style('show-message-list-item');
        }
        midcom_show_style('show-message-list-footer');
    }
}
