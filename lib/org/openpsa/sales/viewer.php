<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Sales viewer interface class.
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_viewer extends midcom_baseclasses_components_request
{
    /**
     * Generic request startup work:
     *
     * - Load the Schema Database
     * - Add the LINK HTML HEAD elements
     */
    public function _on_handle($handler, array $args)
    {
        org_openpsa_widgets_contact::add_head_elements();

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.projects/projects.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.invoices/invoices.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.sales/sales.css");

        midcom::get()->auth->require_valid_user();
    }

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
        $mc_entry = org_openpsa_relatedto_dba::new_collector('toGuid', $deliverable->guid);
        $mc_entry->add_constraint('fromClass', '=', midcom_services_at_entry_dba::class);
        $mc_entry->add_constraint('toClass', '=', org_openpsa_sales_salesproject_deliverable_dba::class);
        $mc_entry->add_constraint('toExtra', '=', 'notify_at_entry');
        $entry_keys = $mc_entry->get_values('fromGuid');

        //check date
        if ($notify) {
            $notification_entry = null;

            if (count($entry_keys) == 0) {
                $notification_entry = new midcom_services_at_entry_dba();
                $notification_entry->create();
                //relatedto from notifcation to deliverable
                org_openpsa_relatedto_plugin::create($notification_entry, 'midcom.services.at', $deliverable, 'org.openpsa.sales', false, ['toExtra' => 'notify_at_entry']);
            } else {
                //get guid of at_entry
                foreach ($entry_keys as $key => $entry) {
                    //check if related at_entry exists
                    try {
                        $notification_entry = new midcom_services_at_entry_dba($entry);
                    } catch (midcom_error $e) {
                        //relatedto links to a non-existing at_entry - so create a new one an link to it
                        $notification_entry = new midcom_services_at_entry_dba();
                        $notification_entry->create();
                        $relatedto = new org_openpsa_relatedto_dba($key);
                        $relatedto->fromGuid = $notification_entry->guid;
                        $relatedto->update();
                    }
                }
            }
            $notification_entry->start = $notify;
            $notification_entry->method = 'new_notification_message';
            $notification_entry->component = 'org.openpsa.sales';
            $notification_entry->arguments = ['deliverable' => $deliverable->guid];
            $notification_entry->update();
        } else {
            //void date - so delete existing at_entrys for this notify_date
            foreach ($entry_keys as $key => $empty) {
                try {
                    $notification_entry = new midcom_services_at_entry_dba($mc_entry->get_subkey($key, 'fromGuid'));
                    //check if related at_entry exists & delete it
                    $notification_entry->delete();
                } catch (midcom_error $e) {
                    $e->log();
                }
            }
        }
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     *
     * @param mixed $object
     * @param mixed $handler The current handler
     */
    public static function add_breadcrumb_path($object, $handler)
    {
        $tmp = [];

        while ($object) {
            if (midcom::get()->dbfactory->is_a($object, org_openpsa_sales_salesproject_deliverable_dba::class)) {
                if ($object->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
                    $prefix = $handler->_l10n->get('subscription');
                } else {
                    $prefix = $handler->_l10n->get('single delivery');
                }
                $tmp["deliverable/{$object->guid}/"] = $prefix . ': ' . $object->title;
            } else {
                $tmp["salesproject/{$object->guid}/"] = $object->title;
            }
            $object = $object->get_parent();
        }
        $tmp = array_reverse($tmp);

        foreach ($tmp as $url => $title) {
            $handler->add_breadcrumb($url, $title);
        }
    }
}
