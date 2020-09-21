<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Invoice management MidCOM interface class.
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver
{
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object) : ?string
    {
        if ($object instanceof org_openpsa_invoices_invoice_dba) {
            return "invoice/{$object->guid}/";
        }
        return null;
    }

    /**
     * Handle deletes of "parent" objects
     *
     * @param midcom_core_dbaobject $object The object triggering the watch
     */
    public function _on_watched_dba_delete(midcom_core_dbaobject $object)
    {
        midcom::get()->auth->request_sudo($this->_component);
        $qb_billing_data = org_openpsa_invoices_billing_data_dba::new_query_builder();
        $qb_billing_data->add_constraint('linkGuid', '=', $object->guid);
        $result = $qb_billing_data->execute();
        foreach ($result as $billing_data) {
            debug_add("Delete billing data with guid:" . $billing_data->guid . " for object with guid:" . $object->guid);
            $billing_data->delete();
        }
        midcom::get()->auth->drop_sudo();
    }

    /**
     * Prepare the indexer client
     */
    public function _on_reindex(midcom_core_dbaobject $topic, midcom_helper_configuration $config, midcom_services_indexer &$indexer)
    {
        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $dm = datamanager::from_schemadb($config->get('schemadb'));

        $indexer = new org_openpsa_invoices_midcom_indexer($topic, $indexer);
        $indexer->add_query('invoices', $qb, $dm);

        return $indexer;
    }
}
