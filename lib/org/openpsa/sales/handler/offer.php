<?php
/**
 * @package org.openpsa.sales
 * @author CONTENT CONTROL, http://contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL, http://contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @param Request $request The request object
     * @param string $guid The salesproject GUID
     * @return Response
     */
    public function _handler_create(Request $request, $guid)
    {
        $this->salesproject = new org_openpsa_sales_salesproject_dba($guid);
        $this->salesproject->require_do('midgard:update');
        $this->offer = $this->prepare_offer();
        return $this->run_form($request);
    }

    private function prepare_offer() : org_openpsa_sales_salesproject_offer_dba
    {
        $billingdata = org_openpsa_invoices_billing_data_dba::get_by_object($this->salesproject);

        $offer = new org_openpsa_sales_salesproject_offer_dba;
        $offer->designation = '';
        $offer->introduction = $this->_l10n->get('offer intro');
        $offer->salesproject = $this->salesproject->id;
        $offer->notice = $billingdata->remarks;

        $mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('salesproject', $this->salesproject->id);
        $mc->add_constraint('up', '=', 0);
        $mc->add_constraint('state', '<', org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED);
        $mc->add_order('metadata.created', 'ASC');
        $offer->deliverables = serialize(array_values($mc->get_values('id')));

        return $offer;
    }

    /**
     * @param string $guid The offer GUID
     * @return Response
     */
    public function _handler_delete($guid)
    {
        $offer = new org_openpsa_sales_salesproject_offer_dba($guid);
        $salesproject = $offer->get_parent();
        $offer->require_do('midgard:delete');
        $offer->delete();
        return new midcom_response_relocate($this->router->generate('salesproject_view', ['guid' => $salesproject->guid]));
    }

    /**
     * @param Request $request The request object
     * @param string $guid The offer GUID
     * @return Response
     */
    public function _handler_edit(Request $request, $guid)
    {
        $this->offer = new org_openpsa_sales_salesproject_offer_dba($guid);
        $this->salesproject = $this->offer->get_parent();
        $this->salesproject->require_do('midgard:update');
        return $this->run_form($request);
    }

    private function run_form(Request $request) : Response
    {
        $this->load_pdf_builder();

        $schemadb = schemadb::from_path($this->_config->get('schemadb_pdf'));
        $schemadb->get_first()->get_field('deliverables')['type_config']['constraints'][] = [
            'field' => 'salesproject',
            'op' => '=',
            'value' => $this->offer->salesproject
        ];

        $dm = new datamanager($schemadb);

        $controller = $dm
            ->set_storage($this->offer)
            ->get_controller();

        midcom::get()->head->set_pagetitle($this->_l10n->get('create_offer'));
        $wf = new midcom\workflow\datamanager(['controller' => $controller]);
        $response = $wf->run($request);
        if ($wf->get_state() == 'save') {
            try {
                $output_filename = $this->_l10n->get('offer_filename_prefix') . '-' . $this->salesproject->code . '.pdf';
                $this->client->render($output_filename);
                midcom::get()->uimessages->add($this->_l10n->get('offer created'), $this->_l10n->get('please verify the file'));
            }
            catch (midcom_error $e) {
                midcom::get()->uimessages->add($this->_l10n->get('offer not created'), $e->getMessage(), 'error');
            }
        }
        return $response;
    }
}
