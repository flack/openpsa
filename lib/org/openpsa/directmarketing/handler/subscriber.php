<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * org.openpsa.directmarketing campaign handler and viewer class.
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_subscriber extends midcom_baseclasses_components_handler
{
    use org_openpsa_directmarketing_handler;

    /**
     * @var org_openpsa_contacts_person_dba
     */
    private $person;

    public function _on_initialize()
    {
        //This is often called by people who should not see anything pointing to OpenPSA, also allows full styling of the unsubscribe page
        midcom::get()->skip_page_style = true;
    }

    /**
     * Phase for showing the list of campaigns
     */
    public function _handler_list(Request $request, string $person)
    {
        midcom::get()->auth->require_valid_user();
        $this->person = new org_openpsa_contacts_person_dba($person);

        if ($add_to = $request->request->get('add_to_campaign')) {
            // Add person to campaign
            try {
                $campaign = new org_openpsa_directmarketing_campaign_dba($add_to);
            } catch (midcom_error $e) {
                // FIXME: More informative error message
                $this->notify('Failed adding person %s to campaign %s', $add_to, 'error');
                return;
            }

            // FIXME: use can_do check to be graceful
            $campaign->require_do('midgard:create');

            $member = new org_openpsa_directmarketing_campaign_member_dba();
            $member->orgOpenpsaObType = org_openpsa_directmarketing_campaign_member_dba::NORMAL;
            $member->person = $this->person->id;
            $member->campaign = $campaign->id;
            if ($member->create()) {
                $this->notify('added person %s to campaign %s', $campaign->title, 'info');
            } else {
                $this->notify('Failed adding person %s to campaign %s', $campaign->title, 'error');
            }
        }
    }

    private function notify(string $message, string $label, string $type)
    {
        midcom::get()->uimessages->add($this->_l10n->get($this->_component),
            sprintf($this->_l10n->get($message), $this->person->name, $label),
            $type
        );
    }

    /**
     * Show the list of existing campaigns
     */
    public function _show_list(string $handler_id, array &$data)
    {
        $campaigns = [];

        midcom_show_style('show-campaign-list-header');

        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb->add_constraint('person', '=', $this->person->id);
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
     */
    public function _handler_unsubscribe(string $member, array &$data)
    {
        midcom::get()->auth->request_sudo($this->_component);

        $data['membership'] = new org_openpsa_directmarketing_campaign_member_dba($member);
        $data['campaign'] = $this->load_campaign($data['membership']->campaign);

        $data['membership']->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED;
        $unsubscribe_status = $data['membership']->update();
        debug_add("Unsubscribe status: {$unsubscribe_status}");
        midcom::get()->auth->drop_sudo();

        if (!$unsubscribe_status) {
            return $this->show('show-unsubscribe-failed');
        }
        return $this->show('show-unsubscribe-ok');
    }

    /**
     * Support the AJAX request for unsubscribing from a campaign
     */
    public function _handler_unsubscribe_ajax(string $member)
    {
        midcom::get()->auth->request_sudo($this->_component);
        $membership = new org_openpsa_directmarketing_campaign_member_dba($member);
        $this->load_campaign($membership->campaign);
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
     */
    public function _handler_unsubscribe_all(string $handler_id, array $args)
    {
        midcom::get()->auth->request_sudo($this->_component);
        $this->person = new org_openpsa_contacts_person_dba($args[0]);

        if ($handler_id === 'subscriber_unsubscribe_all_future') {
            $deny_type = strtolower($args[1]);
            $this->person->set_parameter('org.openpsa.directmarketing', "send_{$deny_type}_denied", '1');
        }
        $unsubscribe_status = true;

        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb->add_constraint('campaign.node', '=', $this->_topic->id);
        $qb->add_constraint('person', '=', $this->person->id);
        $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED);
        $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::TESTER);

        foreach ($qb->execute() as $member) {
            $member->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED;
            if (!$member->update()) {
                //TODO: How to report failures of single rows when other succeed sensibly ??
                $unsubscribe_status = false;
            }
        }

        midcom::get()->auth->drop_sudo();

        if (!$unsubscribe_status) {
            return $this->show('show-unsubscribe-failed');
        }
        return $this->show('show-unsubscribe-ok');
    }
}
