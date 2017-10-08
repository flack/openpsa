<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.directmarketing campaign handler and viewer class.
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_subscriber extends midcom_baseclasses_components_handler
{
    public function _on_initialize()
    {
        //This is often called by people who should not see anything pointing to OpenPSA, also allows full styling of the unsubscribe page
        midcom::get()->skip_page_style = true;
    }

    /**
     * Phase for showing the list of campaigns
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        $this->_request_data['person'] = new org_openpsa_contacts_person_dba($args[0]);

        if (array_key_exists('add_to_campaign', $_POST)) {
            // Add person to campaign
            try {
                $campaign = new org_openpsa_directmarketing_campaign_dba($_POST['add_to_campaign']);
            } catch (midcom_error $e) {
                // FIXME: More informative error message
                $this->notify('Failed adding person %s to campaign %s', $_POST['add_to_campaign'], 'error');
                return;
            }

            // FIXME: use can_do check to be graceful
            $campaign->require_do('midgard:create');

            $member = new org_openpsa_directmarketing_campaign_member_dba();
            $member->orgOpenpsaObType = org_openpsa_directmarketing_campaign_member_dba::NORMAL;
            $member->person = $this->_request_data['person']->id;
            $member->campaign = $campaign->id;
            if ($member->create()) {
                $this->notify('added person %s to campaign %s', $campaign->title, 'info');
            } else {
                $this->notify('Failed adding person %s to campaign %s', $campaign->title, 'error');
            }
        }
    }

    private function notify($message, $label, $type)
    {
        midcom::get()->uimessages->add($this->_l10n->get($this->_component),
            sprintf($this->_l10n->get($message), $this->_request_data['person']->name, $label),
            $type
        );
    }

    /**
     * Show the list of existing campaigns
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_list($handler_id, array &$data)
    {
        $campaigns = [];

        midcom_show_style('show-campaign-list-header');

        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb->add_constraint('person', '=', $data['person']->id);
        $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::TESTER);

        $campaign_membership_map = [];
        foreach ($qb->execute() as $membership) {
            try {
                $campaigns[$membership->campaign] = new org_openpsa_directmarketing_campaign_dba($membership->campaign);
                $campaign_membership_map[$membership->campaign] = $membership;
            } catch (midcom_error $e) {
                debug_add('Failed to load campaign ' . $membership->campaign . ', reason: ' . $e->getMessage());
            }
        }

        // List active campaigns for the "add to campaign" selector
        $qb_all = org_openpsa_directmarketing_campaign_dba::new_query_builder();
        $qb_all->add_constraint('archived', '=', 0);
        $qb_all->add_constraint('id', 'NOT IN', array_keys($campaigns));
        $qb_all->add_order('metadata.created', $this->_config->get('campaign_list_order'));

        $data['campaigns_all'] = [];
        foreach ($qb_all->execute() as $campaign) {
            if ($campaign->can_do('midgard:create')) {
                $data['campaigns_all'][] = $campaign;
            }
        }

        foreach ($campaigns as $campaign) {
            $data['campaign'] = $campaign;
            $data['membership'] = $campaign_membership_map[$campaign->id];

            // TODO: Get count of members and messages here

            midcom_show_style('show-campaign-list-item');
        }

        midcom_show_style('show-campaign-list-footer');
    }

    /**
     * Handle the unsubscribe phase
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_unsubscribe($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->request_sudo($this->_component);

        $data['membership'] = new org_openpsa_directmarketing_campaign_member_dba($args[0]);
        $data['campaign'] = $this->_master->load_campaign($data['membership']->campaign);

        $data['membership']->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED;
        $data['unsubscribe_status'] = $data['membership']->update();
        debug_add("Unsubscribe status: {$data['unsubscribe_status']}");
        midcom::get()->auth->drop_sudo();
    }

    /**
     * Show the unsubscribe action
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_unsubscribe($handler_id, array &$data)
    {
        if ($this->_request_data['unsubscribe_status'] == false) {
            midcom_show_style('show-unsubscribe-failed');
        } else {
            midcom_show_style('show-unsubscribe-ok');
        }
    }

    /**
     * Support the AJAX request for unsubscribing from a campaign
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_unsubscribe_ajax($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->request_sudo($this->_component);
        $membership = new org_openpsa_directmarketing_campaign_member_dba($args[0]);
        $this->_master->load_campaign($membership->campaign);
        $membership->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED;
        $unsubscribe_status = $membership->update();
        midcom::get()->auth->drop_sudo();

        debug_add("Unsubscribe status: {$unsubscribe_status}");

        $response = new midcom_response_xml;
        $response->status = "Unsubscribe failed";
        $response->result = $unsubscribe_status;
        return $response;
    }

    /**
     * Handle the request for unsubscribing all subscribers from a campaign
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_unsubscribe_all($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->request_sudo($this->_component);
        $data['person'] = new org_openpsa_contacts_person_dba($args[0]);

        if ($handler_id === 'subscriber_unsubscribe_all_future') {
            $deny_type = strtolower($args[1]);
            $data['person']->set_parameter('org.openpsa.directmarketing', "send_{$deny_type}_denied", '1');
        }
        $data['unsubscribe_status'] = true;

        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb->add_constraint('campaign.node', '=', $this->_topic->id);
        $qb->add_constraint('person', '=', $data['person']->id);
        // FIXME: Use NOT IN
        $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED);
        $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::TESTER);

        foreach ($qb->execute() as $member) {
            $member->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED;
            if (!$member->update()) {
                //TODO: How to report failures of single rows when other succeed sensibly ??
                $data['unsubscribe_status'] = false;
            }
        }

        midcom::get()->auth->drop_sudo();
    }

    /**
     * Show the unsubscribe status for unsubscribe all
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_unsubscribe_all($handler_id, array &$data)
    {
        if ($data['unsubscribe_status'] == false) {
            midcom_show_style('show-unsubscribe-failed');
        } else {
            midcom_show_style('show-unsubscribe-ok');
        }
    }
}
