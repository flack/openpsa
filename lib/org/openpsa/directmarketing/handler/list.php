<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;
use midcom\grid\provider\client;
use midcom\grid\provider;

/**
 * Campaign list handler
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_list extends midcom_baseclasses_components_handler
implements client
{
    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
    }

    public function get_qb($field = null, $direction = 'ASC', array $search = [])
    {
        $qb = org_openpsa_directmarketing_campaign_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_topic->id);
        $qb->add_constraint('archived', '=', 0);
        if ($field !== null) {
            $qb->add_order($field, $direction);
        }
        $qb->add_order('metadata.created', $this->_config->get('campaign_list_order'));

        return $qb;
    }

    public function get_row(midcom_core_dbaobject $campaign)
    {
        $link = $this->router->generate('view_campaign', ['guid' => $campaign->guid]);
        $entry = [];

        $entry['id'] = $campaign->id;
        $entry['index_title'] = $campaign->title;
        $entry['title'] = "<a href='{$link}'>" . $campaign->title . "</a>";
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
     * @param array $data The local request data.
     */
    public function _handler_frontpage(array &$data)
    {
        midcom::get()->auth->require_valid_user();

        if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_directmarketing_campaign_dba::class)) {
            $workflow = $this->get_workflow('datamanager');
            $schemadb = schemadb::from_path($this->_config->get('schemadb_campaign'));
            foreach ($schemadb->all() as $name => $schema) {
                $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('create_campaign', ['schema' => $name]), [
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($schema->get('description'))),
                    MIDCOM_TOOLBAR_GLYPHICON => 'bullhorn',
                ]));
            }
        }

        $provider = new provider($this, 'local');
        $data['grid'] = $provider->get_grid('campaign_grid');

        return $this->show('show-frontpage');
    }
}
