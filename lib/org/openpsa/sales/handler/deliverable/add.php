<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Deliverable creation class
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_deliverable_add extends midcom_baseclasses_components_handler
{
    use org_openpsa_sales_handler;

    /**
     * The deliverable to display
     *
     * @var org_openpsa_sales_salesproject_deliverable_dba
     */
    private $_deliverable;

    /**
     * The salesproject the deliverable is connected to
     *
     * @var org_openpsa_sales_salesproject_dba
     */
    private $_salesproject;

    /**
     * The product to deliver
     *
     * @var org_openpsa_products_product_dba
     */
    private $_product;

    /**
     * @return \midcom\datamanager\controller
     */
    private function load_controller()
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb_deliverable'));
        $schema = 'default';

        if ($this->_product->delivery == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
            $schema = 'subscription';
            $field =& $schemadb->get('subscription')->get_field('start');
            $field['type_config']['min_date'] = strftime('%Y-%m-%d');
        } else {
            $end =& $schemadb->get('default')->get_field('end');
            $end['type_config']['min_date'] = strftime('%Y-%m-%d');
            if ($this->_product->costType == "%") {
                $costPerUnit =& $schemadb->get('default')->get_field('costPerUnit');
                $costPerUnit['title'] = $this->_l10n->get("cost per unit (percentage)");
            }
        }

        $defaults = [
            'product' => $this->_product->id,
            'units' => 1,

            // Copy values from product
            'unit' => $this->_product->unit,
            'pricePerUnit' => $this->_product->price,
            'costPerUnit' => $this->_product->cost,
            'costType' => $this->_product->costType,
            'title' => $this->_product->title,
            'description' => $this->_product->description,
            'supplier' => $this->_product->supplier,
            'orgOpenpsaObtype' => $this->_product->delivery,
        ];

        //TODO: Copy tags from product
        //$tagger = new net_nemein_tag_handler();
        //$tagger->copy_tags($this->_product, $this->_deliverable);

        $dm = new datamanager($schemadb);
        $dm->set_defaults($defaults);
        $dm->set_storage($this->_deliverable, $schema);
        return $dm->get_controller();
    }

    /**
     * Looks up a deliverable to display.
     *
     * @param Request $request The request object
     * @param string $guid The deliverable GUID
     * @param array &$data The local request data.
     */
    public function _handler_add(Request $request, $guid, array &$data)
    {
        if (   !$request->request->has('product')
            && !$request->request->has('org_openpsa_sales')) {
            throw new midcom_error('No product specified, aborting.');
        }

        $this->_salesproject = new org_openpsa_sales_salesproject_dba($guid);
        $this->_salesproject->require_do('midgard:create');

        if ($request->request->has('org_openpsa_sales')) {
            $selection = json_decode($request->request->get('org_openpsa_sales')['product']['selection']);
            $product_id = current($selection);
        } else {
            $product_id = $request->request->getInt('product');
        }
        $this->_product = new org_openpsa_products_product_dba($product_id);

        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba();
        $this->_deliverable->salesproject = $this->_salesproject->id;
        $this->_deliverable->state = org_openpsa_sales_salesproject_deliverable_dba::STATE_NEW;
        $this->_deliverable->orgOpenpsaObtype = $this->_product->delivery;

        $data['controller'] = $this->load_controller();

        midcom::get()->head->set_pagetitle($this->_l10n->get('add item'));
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/sales.js');
        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    public function save_callback(controller $controller)
    {
        $formdata = $controller->get_form_values();
        $this->process_notify_date((int) $formdata['notify'], $this->_deliverable);
        return $this->router->generate('salesproject_view', ['guid' => $this->_salesproject->guid]);
    }
}
