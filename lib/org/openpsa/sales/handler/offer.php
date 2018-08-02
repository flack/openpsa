<?php
/**
 * @package org.openpsa.sales
 * @author CONTENT CONTROL, http://contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL, http://contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Sales offer handler creates pdf offers
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_offer extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_sales_salesproject_dba
     */
    private $salesproject;

    /**
     * @var org_openpsa_sales_interfaces_pdfbuilder
     */
    private $client;

    /**
     * @var org_openpsa_sales_salesproject_offer_dba
     */
    private $offer;

    private function load_pdf_builder()
    {
        $client_class = $this->_config->get('sales_pdfbuilder_class');
        if (!class_exists($client_class)) {
            throw new midcom_error('Could not find PDF renderer ' . $client_class);
        }

        $this->client = new $client_class($this->offer);
    }

    /**
     * @param mixed $handler_id The ID of the handler._config
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return midcom_response
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->salesproject = new org_openpsa_sales_salesproject_dba($args[0]);
        $this->salesproject->require_do('midgard:update');
        $this->offer = $this->prepare_offer();
        return $this->run_form();
    }

    private function prepare_offer()
    {
        $billingdata = org_openpsa_invoices_billing_data_dba::get_by_object($this->salesproject);

        $offer = new org_openpsa_sales_salesproject_offer_dba;
        $offer->designation = '';
        $offer->introduction = $this->_l10n->get('offer intro');
        $offer->salesproject = $this->salesproject->id;
        $offer->notice = $billingdata->remarks;
        return $offer;
    }

    /**
     * @param mixed $handler_id The ID of the handler._config
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return midcom_response
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $offer = new org_openpsa_sales_salesproject_offer_dba($args[0]);
        $salesproject = $offer->get_parent();
        $offer->require_do('midgard:delete');
        $offer->delete();
        return new midcom_response_relocate($this->router->generate('salesproject_view', ['guid' => $salesproject->guid]));
    }

    /**
     * @param mixed $handler_id The ID of the handler._config
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return midcom_response
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->offer = new org_openpsa_sales_salesproject_offer_dba($args[0]);
        $this->salesproject = $this->offer->get_parent();
        $this->salesproject->require_do('midgard:update');
        return $this->run_form();
    }

    private function run_form()
    {
        $this->load_pdf_builder();

        $controller = datamanager::from_schemadb($this->_config->get('schemadb_pdf'))
            ->set_storage($this->offer)
            ->get_controller();

        midcom::get()->head->set_pagetitle($this->_l10n->get('create_offer'));
        $wf = new midcom\workflow\datamanager(['controller' => $controller]);
        $response = $wf->run();
        if ($wf->get_state() == 'save') {
            try {
                $output_filename = $this->_l10n->get('offer_filename_prefix') . '-' . $this->salesproject->code . '.pdf';
                $this->client->render($output_filename);
                midcom::get()->uimessages->add($this->_l10n->get('offer created'), $this->_l10n->get('please verify the file'));
            }
            catch (midcom_error $e) {
                midcom::get()->uimessages->add($this->_l10n->get('offer not ceated'), $e->getMessage(), 'error');
            }
        }
        return $response;
    }
}
