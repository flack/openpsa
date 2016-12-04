<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Campaign list handler
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_list extends midcom_baseclasses_components_handler
implements org_openpsa_widgets_grid_provider_client
{
    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
        org_openpsa_invoices_viewer::add_head_elements_for_invoice_grid();
    }

    public function get_qb($field = null, $direction = 'ASC', array $search = array())
    {
        $qb = org_openpsa_directmarketing_campaign_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_topic->id);
        $qb->add_constraint('archived', '=', 0);
        if (!is_null($field)) {
            $qb->add_order($field, $direction);
        }
        $qb->add_order('metadata.created', $this->_config->get('campaign_list_order'));

        return $qb;
    }

    public function get_row(midcom_core_dbaobject $campaign)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $entry = array();

        $entry['id'] = $campaign->id;
        $entry['index_title'] = $campaign->title;
        $entry['title'] = "<a href='{$prefix}campaign/{$campaign->guid}/'>" . $campaign->title . "</a>";
        $entry['description'] = $campaign->title;
        $entry['smart_campaign'] = $campaign->orgOpenpsaObtype === org_openpsa_directmarketing_campaign_dba::TYPE_SMART;

        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb->add_constraint('campaign', '=', $campaign->id);
        $qb->add_constraint('suspended', '=', false);
        $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::TESTER);
        $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED);

        $entry['subscribers'] = $qb->count_unchecked();

        $qb = org_openpsa_directmarketing_campaign_message_dba::new_query_builder();
        $qb->add_constraint('campaign', '=', $campaign->id);
        $entry['messages'] = $qb->count_unchecked();
        return $entry;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_frontpage($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        if (midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_directmarketing_campaign_dba')) {
            $workflow = $this->get_workflow('datamanager2');

            $schemadb_campaign = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_campaign'));
            foreach (array_keys($schemadb_campaign) as $name) {
                $this->_view_toolbar->add_item($workflow->get_button("campaign/create/{$name}/", array(
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($schemadb_campaign[$name]->description)),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people.png',
                )));
            }
        }

        $provider = new org_openpsa_widgets_grid_provider($this, 'local');
        $data['grid'] = $provider->get_grid('campaign_grid');
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_frontpage($handler_id, array &$data)
    {
        midcom_show_style('show-frontpage');
    }
}
