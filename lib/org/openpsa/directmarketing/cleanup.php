<?php
/**
 * @package org.openpsa.directmarketing
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Direct Marketing cleanup system
 *
 * Usable for systems where the history of subscriptions and receipts is not absolutely necessary.
 * The cleanup script will remove all of the following:
 * - org_openpsa_campaign_messagereceipt
 * - org_openpsa_link_log
 * - org_openpsa_campaign_member (where orgOpenpsaObtype is not org_openpsa_directmarketing_campaign_member_dba::TESTER)
 * - org_openpsa_person (without account)
 *
 * that have not been updated within the configured time (by default one month).
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_cleanup
{
    use midcom_baseclasses_components_base;

    private function get_deletion_timestamp() : string
    {
        return gmdate('Y-m-d H:i:s', time() - 3600 * 24 * $this->_config->get('delete_older_than_days'));
    }

    private function add_time_filter(midcom_core_querybuilder $qb, bool $kept)
    {
        if ($kept) {
            $qb->add_constraint('metadata.revised', '>=', $this->get_deletion_timestamp());
        } else {
            $qb->add_constraint('metadata.revised', '<', $this->get_deletion_timestamp());
        }
    }

    private function get_message_receipt_qb(bool $kept = false) : midcom_core_querybuilder
    {
        $qb = org_openpsa_directmarketing_campaign_messagereceipt_dba::new_query_builder();
        $this->add_time_filter($qb, $kept);
        return $qb;
    }

    private function get_link_log_qb(bool $kept = false) : midcom_core_querybuilder
    {
        $qb = org_openpsa_directmarketing_link_log_dba::new_query_builder();
        $this->add_time_filter($qb, $kept);
        return $qb;
    }

    private function get_campaign_member_qb(bool $kept = false) : midcom_core_querybuilder
    {
        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        if ($kept) {
            $qb->begin_group('OR');
            $qb->add_constraint('metadata.revised', '>=', $this->get_deletion_timestamp());
            $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_member_dba::TESTER);
            $qb->add_constraint('campaign.orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_dba::TYPE_SMART);
            $qb->end_group();
        } else {
            $qb->add_constraint('metadata.revised', '<', $this->get_deletion_timestamp());

            // Don't delete testers
            $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::TESTER);

            // Don't delete from smart campaigns
            $qb->add_constraint('campaign.orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_dba::TYPE_SMART);
        }
        return $qb;
    }

    private function get_person_qb(bool $kept = false) : midcom_core_querybuilder
    {
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        if ($kept) {
            $qb->begin_group('OR');
            $qb->add_constraint('metadata.revised', '>=', $this->get_deletion_timestamp());
            midcom_core_account::add_username_constraint($qb, '<>', '');
            $qb->end_group();
        } else {
            $qb->add_constraint('metadata.revised', '<', $this->get_deletion_timestamp());

            // Don't delete OpenPSA users
            midcom_core_account::add_username_constraint($qb, '=', '');
        }
        return $qb;
    }

    public function count(bool $kept = false) : array
    {
        return [
            'message_receipt' => $this->get_message_receipt_qb($kept)->count_unchecked(),
            'link_log' => $this->get_link_log_qb($kept)->count_unchecked(),
            'campaign_member' => $this->get_campaign_member_qb($kept)->count_unchecked(),
            'person' => $this->get_person_qb($kept)->count_unchecked()
        ];
    }

    public function delete()
    {
        if ($this->_config->get('delete_older')) {
            midcom::get()->disable_limits();

            $this->delete_entries($this->get_message_receipt_qb());
            $this->delete_entries($this->get_link_log_qb());
            $this->delete_entries($this->get_campaign_member_qb());
            $this->delete_entries($this->get_person_qb());
        }
    }

    private function delete_entries(midcom_core_querybuilder $qb)
    {
        $qb->set_limit($this->_config->get('delete_older_per_run'));
        foreach ($qb->execute() as $object) {
            $object->delete();
        }
    }
}
