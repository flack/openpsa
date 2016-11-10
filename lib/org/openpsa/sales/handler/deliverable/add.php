<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Deliverable display class
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_deliverable_add extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
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

    public function load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_deliverable'));

        if ($this->get_schema_name() == 'subscription') {
            $schemadb['subscription']->fields['start']['type_config']['min_date'] = strftime('%Y-%m-%d');
        } else {
            $schemadb['default']->fields['end']['type_config']['min_date'] = strftime('%Y-%m-%d');
        }

        return $schemadb;
    }

    public function get_schema_name()
    {
        // Set schema based on product type
        if ($this->_product->delivery == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
            return 'subscription';
        }

        return 'default';
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba();
        $this->_deliverable->salesproject = $this->_salesproject->id;
        $this->_deliverable->state = org_openpsa_sales_salesproject_deliverable_dba::STATE_NEW;
        $this->_deliverable->orgOpenpsaObtype = $this->_product->delivery;

        if (!$this->_deliverable->create()) {
            debug_print_r('We operated on this object:', $this->_deliverable);
            throw new midcom_error('Failed to create a new deliverable. Last Midgard error was: ' . midcom_connection::get_error_string());
        }

        return $this->_deliverable;
    }

    public function get_schema_defaults()
    {
        $defaults = array(
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
        );

        //TODO: Copy tags from product
        //$tagger = new net_nemein_tag_handler();
        //$tagger->copy_tags($this->_product, $this->_deliverable);

        return $defaults;
    }

    /**
     * loads the controller instance
     *
     * @return midcom_helper_datamanager2_controller
     */
    private function _prepare_datamanager()
    {
        $controller = $this->get_controller('create');

        // adjust cost per unit label
        if ($this->_product->costType == "%") {
            $cost_per_unit_title = "cost per unit (percentage)";
            $controller->schemadb["default"]->fields['costPerUnit']['title'] = $this->_l10n->get($cost_per_unit_title);
        }

        $controller->initialize();
        return $controller;
    }

    /**
     * Looks up a deliverable to display.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_add($handler_id, array $args, array &$data)
    {
        if (!array_key_exists('product', $_POST)) {
            throw new midcom_error('No product specified, aborting.');
        }

        $this->_salesproject = new org_openpsa_sales_salesproject_dba($args[0]);
        $this->_salesproject->require_do('midgard:create');

        if (is_array($_POST['product'])) {
            $selection = json_decode($_POST['product']['selection']);
            $product_id = current($selection);
        } else {
            $product_id = (int) $_POST['product'];
        }
        $this->_product = new org_openpsa_products_product_dba($product_id);

        $data['controller'] = $this->_prepare_datamanager();

        midcom::get()->head->set_pagetitle($this->_l10n->get('add offer'));
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/sales.js');
        $workflow = $this->get_workflow('datamanager2', array(
            'controller' => $data['controller'],
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        $formdata = $controller->datamanager->types;
        $this->_master->process_notify_date($formdata, $this->_deliverable);
        return "salesproject/{$this->_salesproject->guid}/";
    }
}
