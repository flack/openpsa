<?php
/**
 * @package org.openpsa.interviews
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Phone interview report handler
 *
 * @package org.openpsa.interviews
 */
class org_openpsa_interviews_handler_report extends midcom_baseclasses_components_handler
{
    private function _prepare_toolbar()
    {
        $this->_view_toolbar->add_item(
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/{$this->_request_data['campaign']->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('back'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
            )
        );
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_all($handler_id, array $args, array &$data)
    {
        $this->_request_data['campaign'] = new org_openpsa_directmarketing_campaign_dba($args[0]);
        $this->_prepare_toolbar();

        // List members who have been interviewed
        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb->add_constraint('campaign', '=', $this->_request_data['campaign']->id);
        $qb->begin_group("OR");
            $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_member_dba::INTERVIEWED);
            $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED);
        $qb->end_group();
        $this->_request_data['members_interviewed'] = $qb->execute();

        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));
        $this->_request_data['datamanager'] = new midcom_helper_datamanager2_datamanager($schemadb);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_all($handler_id, array &$data)
    {
        midcom_show_style('show-all-header');

        foreach ($this->_request_data['members_interviewed'] as $member)
        {
            $this->_request_data['person'] = new midcom_db_person($member->person);
            $this->_request_data['datamanager']->autoset_storage($member);
            midcom_show_style('show-all-item');
        }

        midcom_show_style('show-all-footer');
    }
}
?>