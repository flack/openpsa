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
 * - org_openpsa_campaign_message_receipt
 * - org_openpsa_link_log
 * - org_openpsa_campaign_member (where orgOpenpsaObtype is not ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER)
 * - org_openpsa_person (without username)
 * 
 * that have not been updated within the configured time (by default one month).
 * 
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_cleanup extends midcom_baseclasses_components_purecode
{
    private function get_deletion_timestamp()
    {
        return gmdate('Y-m-d H:i:s', time() - 3600 * 24 * $this->_config->get('delete_older_than_days'));
    }

    private function get_message_receipt_qb($kept = false)
    {
        $qb = org_openpsa_directmarketing_campaign_message_receipt_dba::new_query_builder();
        if ($kept)
        {
            $qb->add_constraint('metadata.revised', '>=', $this->get_deletion_timestamp());
        }
        else
        {
            $qb->add_constraint('metadata.revised', '<', $this->get_deletion_timestamp());
        }
        return $qb;
    }
    
    private function get_link_log_qb($kept = false)
    {
        $qb = org_openpsa_directmarketing_link_log_dba::new_query_builder();
        if ($kept)
        {
            $qb->add_constraint('metadata.revised', '>=', $this->get_deletion_timestamp());
        }
        else
        {
            $qb->add_constraint('metadata.revised', '<', $this->get_deletion_timestamp());
        }
        return $qb;
    }
    
    private function get_campaign_member_qb($kept = false)
    {
        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        if ($kept)
        {
            $qb->begin_group('OR');
            $qb->add_constraint('metadata.revised', '>=', $this->get_deletion_timestamp());
            $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER);
            $qb->add_constraint('campaign.orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_CAMPAIGN_SMART);
            $qb->end_group();
        }
        else
        {
            $qb->add_constraint('metadata.revised', '<', $this->get_deletion_timestamp());
            
            // Don't delete testers
            $qb->add_constraint('orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER);

            // Don't delete from smart campaigns
            $qb->add_constraint('campaign.orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_CAMPAIGN_SMART);
        }
        return $qb;
    }
 
    private function get_person_qb($kept = false)
    {
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        if ($kept)
        {
            $qb->begin_group('OR');
            $qb->add_constraint('metadata.revised', '>=', $this->get_deletion_timestamp());
            $qb->add_constraint('username', '<>', '');
            $qb->end_group();
        }
        else
        {
            $qb->add_constraint('metadata.revised', '<', $this->get_deletion_timestamp());
            
            // Don't delete OpenPSA users
            $qb->add_constraint('username', '=', '');
        }
        return $qb;
    }
 
    public function count($kept = false)
    {
        $deletion = array();
        $deletion['message_receipt'] = $this->get_message_receipt_qb($kept)->count_unchecked();
        $deletion['link_log'] = $this->get_link_log_qb($kept)->count_unchecked();
        $deletion['campaign_member'] = $this->get_campaign_member_qb($kept)->count_unchecked();
        $deletion['person'] = $this->get_person_qb($kept)->count_unchecked();
        return $deletion;
    }
    
    public function delete()
    {
        if (!$this->_config->get('delete_older'))
        {
            return;
        }
        
        //Disable limits
        // TODO: Could this be done more safely somehow
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);
        
        $qb = $this->get_message_receipt_qb();
        $qb->set_limit($this->_config->get('delete_older_per_run'));
        $receipts = $qb->execute();
        foreach ($receipts as $receipt)
        {
            $receipt->delete();
        }

        $qb = $this->get_link_log_qb();
        $qb->set_limit($this->_config->get('delete_older_per_run'));
        $logs = $qb->execute();
        foreach ($logs as $log)
        {
            $log->delete();
        }

        $qb = $this->get_campaign_member_qb();
        $qb->set_limit($this->_config->get('delete_older_per_run'));
        $members = $qb->execute();
        foreach ($members as $member)
        {
            $member->delete();
        }

        $qb = $this->get_person_qb();
        $qb->set_limit($this->_config->get('delete_older_per_run'));
        $persons = $qb->execute();
        foreach ($persons as $person)
        {
            $person->delete();
        }
    }
}
?>