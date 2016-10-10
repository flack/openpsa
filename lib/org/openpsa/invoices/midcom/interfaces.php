<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice management MidCOM interface class.
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver, org_openpsa_contacts_duplicates_support
{
    public function get_merge_configuration($object_mode, $merge_mode)
    {
        $config = array();
        if ($merge_mode == 'future')
        {
            // Contacts does not have future references so we have nothing to transfer...
            return $config;
        }
        if ($object_mode == 'person')
        {
            $config['org_openpsa_invoices_invoice_dba'] = array
            (
                'customerContact' => array
                (
                    'target' => 'id'
                )
            );
            $config['org_openpsa_invoices_billing_data_dba'] = array
            (
                'linkGuid' => array
                (
                    'target' => 'guid',
                    'duplicate_check' => 'linkGuid'
                )
            );
        }
        return $config;
    }

    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if ($object instanceof org_openpsa_invoices_invoice_dba)
        {
            return "invoice/{$object->guid}/";
        }
        return null;
    }

    /**
     * Handle deletes of "parent" objects
     *
     * @param mixed $object The object triggering the watch
     */
    public function _on_watched_dba_delete($object)
    {
        midcom::get()->auth->request_sudo($this->_component);
        $qb_billing_data = org_openpsa_invoices_billing_data_dba::new_query_builder();
        $qb_billing_data->add_constraint('linkGuid', '=', $object->guid);
        $result = $qb_billing_data->execute();
        if (count($result) > 0)
        {
            foreach ($result as $billing_data)
            {
                debug_add("Delete billing data with guid:" . $billing_data->guid . " for object with guid:" . $object->guid);
                $billing_data->delete();
            }
        }
        midcom::get()->auth->drop_sudo();
    }

    /**
     * Prepare the indexer client
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $schemadb = midcom_helper_datamanager2_schema::load_database($config->get('schemadb'));

        $indexer = new org_openpsa_invoices_midcom_indexer($topic, $indexer);
        $indexer->add_query('invoices', $qb, $schemadb);

        return $indexer;
    }
}
