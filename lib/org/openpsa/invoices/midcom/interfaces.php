<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: interfaces.php 26462 2010-06-28 12:03:04Z gudd $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice management MidCOM interface class.
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    function __construct()
    {
        parent::__construct();

        $this->_component = 'org.openpsa.invoices';
        $this->_autoload_files = array();
        $this->_autoload_libraries = array();
    }

    function _on_resolve_permalink($topic, $config, $guid)
    {
        $invoice = new org_openpsa_invoices_invoice_dba($guid);

        if (   !$invoice
            || !$invoice->guid)
        {
            return null;
        }

        return "invoice/{$invoice->guid}/";
    }

    /**
     * Handle deletes of "parent" objects
     *
     * @param mixed $object The object triggering the watch
     */
    function _on_watched_dba_delete($object)
    {
        $_MIDCOM->auth->request_sudo();
        $qb_billing_data = org_openpsa_invoices_billing_data_dba::new_query_builder();
        $qb_billing_data->add_constraint('linkGuid' , '=' , $object->guid);
        $result = $qb_billing_data->execute();
        if (count($result) > 0 )
        {
            foreach ($result as $billing_data)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Delete billing data with guid:" . $billing_data->guid . " for object with guid:" . $object->guid);
                debug_pop();
                $billing_data->delete();
            }
        }
        $_MIDCOM->auth->drop_sudo();
    }
      /**
      * Iterate over all invoices and create index record using the datamanager indexer
     * method.
     */
    function _on_reindex($topic, $config, &$indexer)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        $_MIDCOM->load_library('midcom.helper.datamanager2');

        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();

        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            $schema = midcom_helper_datamanager2_schema::load_database($config->get('schemadb'));

            $datamanager = new midcom_helper_datamanager2_datamanager($schema);
            if (!$datamanager)
            {
                debug_add('Warning, failed to create a datamanager instance with this schemapath:' . $this->_config->get('schemadb'),
                    MIDCOM_LOG_WARN);
                return false;
            }
            foreach ($ret as $invoice)
            {
                if (!$datamanager->autoset_storage($invoice))
                {
                    debug_add("Warning, failed to initialize datamanager for invoice {$invoice->id}. See Debug Log for details.", MIDCOM_LOG_WARN);
                    debug_print_r('Invoice dump:', $invoice);
                    continue;
                }
                //create index_datamanger from datamanger
                $index_datamanager = new midcom_services_indexer_document_datamanager2($datamanager);

                //get guid , topic_url of passed node
                $nav = new midcom_helper_nav();
                $object = $nav->resolve_guid($topic->guid , true);
                $index_datamanager->topic_guid = $topic->guid;
                $index_datamanager->topic_url = $object[MIDCOM_NAV_FULLURL];
                $index_datamanager->component = $object[MIDCOM_NAV_COMPONENT];
                $indexer->index($index_datamanager);
            }
        }
        debug_pop();
        return true;
    }
}

?>