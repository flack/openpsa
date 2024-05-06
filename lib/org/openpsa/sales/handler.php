<?php
/**
 * @package org.openpsa.sales
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Handler addons
 *
 * @package org.openpsa.sales
 */
trait org_openpsa_sales_handler
{
    /**
     * Function to process the notify date
     * creates/edits/deletes the corresponding at_entry if needed
     *
     * @param integer $notify The notify date
     */
    public function process_notify_date(int $notify, org_openpsa_sales_salesproject_deliverable_dba $deliverable)
    {
        //check if there is already an at_entry
        $mc = new org_openpsa_relatedto_collector($deliverable->guid, midcom_services_at_entry_dba::class);
        $mc->add_constraint('toClass', '=', org_openpsa_sales_salesproject_deliverable_dba::class);
        $mc->add_constraint('toExtra', '=', 'notify_at_entry');
        $at_entries = $mc->get_related_objects();
        //check date
        if ($notify) {
            if (empty($at_entries)) {
                $at_entry = new midcom_services_at_entry_dba;
                $at_entry->method = 'new_notification_message';
                $at_entry->component = 'org.openpsa.sales';
                $at_entry->arguments = ['deliverable' => $deliverable->guid];
                $at_entry->create();
                //relatedto from notification to deliverable
                org_openpsa_relatedto_plugin::create($at_entry, 'midcom.services.at', $deliverable, 'org.openpsa.sales', extra: ['toExtra' => 'notify_at_entry']);
            } else {
                $at_entry = end($at_entries);
            }
            $at_entry->start = $notify;
            $at_entry->update();
        } else {
            //void date - so delete existing at_entries for this notify_date
            foreach ($at_entries as $at_entry) {
                //check if related at_entry exists & delete it
                $at_entry->delete();
            }
        }
    }
}
