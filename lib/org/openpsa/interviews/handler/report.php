<?php
/**
 * @package org.openpsa.interviews
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: report.php,v 1.2 2006/05/10 16:26:10 rambo Exp $
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
    function _prepare_toolbar()
    {
        $this->_view_toolbar->add_item(
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/{$this->_request_data['campaign']->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('back'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_all($handler_id, $args, &$data)
    {
        $this->_request_data['campaign'] = new org_openpsa_directmarketing_campaign_dba($args[0]);
        if (!$this->_request_data['campaign'])
        {
            return false;
        }

        $this->_prepare_toolbar();

        // List members who have been interviewed
        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb->add_constraint('campaign', '=', $this->_request_data['campaign']->id);
        $qb->begin_group("OR");
            $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_INTERVIEWED);
            $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_UNSUBSCRIBED);
        $qb->end_group();
        $this->_request_data['members_interviewed'] = $qb->execute();

        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));
        $this->_request_data['datamanager'] = new midcom_helper_datamanager2_datamanager($schemadb);

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_all($handler_id, &$data)
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