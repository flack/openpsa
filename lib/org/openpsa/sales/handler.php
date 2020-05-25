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
     * @param org_openpsa_sales_salesproject_deliverable_dba $deliverable The current deliverable
     */
    public function process_notify_date($notify, org_openpsa_sales_salesproject_deliverable_dba $deliverable)
    {
        //check if there is already an at_entry
        $mc = org_openpsa_relatedto_dba::new_collector('toGuid', $deliverable->guid);
        $mc->add_constraint('fromClass', '=', midcom_services_at_entry_dba::class);
        $mc->add_constraint('toClass', '=', org_openpsa_sales_salesproject_deliverable_dba::class);
        $mc->add_constraint('toExtra', '=', 'notify_at_entry');
        $at_entries = $mc->get_values('fromGuid');

        //check date
        if ($notify) {
            $at_entry = null;

            //get guid of at_entry
            foreach ($at_entries as $guid) {
                //check if related at_entry exists
                try {
                    $at_entry = new midcom_services_at_entry_dba($guid);
                } catch (midcom_error $e) {
                    $e->log();
                }
            }

            if ($at_entry === null) {
                $at_entry = new midcom_services_at_entry_dba;
                $at_entry->method = 'new_notification_message';
                $at_entry->component = 'org.openpsa.sales';
                $at_entry->arguments = ['deliverable' => $deliverable->guid];
                $at_entry->create();
                //relatedto from notification to deliverable
                org_openpsa_relatedto_plugin::create($at_entry, 'midcom.services.at', $deliverable, 'org.openpsa.sales', null, ['toExtra' => 'notify_at_entry']);
            }
            $at_entry->start = $notify;
            $at_entry->update();
        } else {
            //void date - so delete existing at_entries for this notify_date
            foreach ($at_entries as $guid) {
                try {
                    $at_entry = new midcom_services_at_entry_dba($guid);
                    //check if related at_entry exists & delete it
                    $at_entry->delete();
                } catch (midcom_error $e) {
                    $e->log();
                }
            }
        }
    }
}
