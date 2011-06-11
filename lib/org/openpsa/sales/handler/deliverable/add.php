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
{
    /**
     * The deliverable to display
     *
     * @var org_openpsa_sales_salesproject_deliverable_dba
     */
    private $_deliverable = null;

    /**
     * The salesproject the deliverable is connected to
     *
     * @var org_openpsa_sales_salesproject_dba
     */
    private $_salesproject = null;

    /**
     * The product to deliver
     *
     * @var org_openpsa_products_product_dba
     */
    private $_product = null;

    private function _create_deliverable(&$product, $up = 0, $units = 1)
    {
        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba();
        $deliverable->product = $product->id;
        $deliverable->salesproject = $this->_salesproject->id;
        $deliverable->up = $up;
        $deliverable->units = $units;

        // Copy values from product
        $deliverable->unit = $product->unit;
        $deliverable->pricePerUnit = $product->price;
        $deliverable->costPerUnit = $product->cost;
        $deliverable->costType = $product->costType;
        $deliverable->title = $product->title;
        $deliverable->description = $product->description;
        $deliverable->supplier = $product->supplier;

        $deliverable->state = org_openpsa_sales_salesproject_deliverable_dba::STATUS_NEW;

        $deliverable->orgOpenpsaObtype = $product->delivery;

        if (!$deliverable->create())
        {
            debug_print_r('We operated on this object:', $deliverable);
            throw new midcom_error('Failed to create a new deliverable. Last Midgard error was: ' . midcom_connection::get_error_string());
        }

        // Set schema based on product type
        if ($product->delivery == ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION)
        {
            $deliverable->parameter('midcom.helper.datamanager2', 'schema_name', 'subscription');
        }

        // Copy tags from product
        $tagger = new net_nemein_tag_handler();
        $tagger->copy_tags($product, $deliverable);

        return $deliverable;
    }

    /**
     * Looks up a deliverable to display.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_add($handler_id, array $args, array &$data)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST')
        {
            throw new midcom_error_forbidden('Only POST requests are allowed here.');
        }

        $this->_salesproject = new org_openpsa_sales_salesproject_dba($args[0]);
        $this->_salesproject->require_do('midgard:create');

        if (!array_key_exists('product', $_POST))
        {
            throw new midcom_error('No product specified, aborting.');
        }

        $this->_product = new org_openpsa_products_product_dba((int) $_POST['product']);
        $this->_deliverable = $this->_create_deliverable($this->_product);
        if ($this->_deliverable)
        {
            // Go to deliverable edit screen
            $_MIDCOM->relocate("deliverable/edit/{$this->_deliverable->guid}/");
        }
        else
        {
            // Get user back to the sales project
            // TODO: Add UImessage on why this failed
            $_MIDCOM->relocate("salesproject/{$this->_salesproject->guid}/");
        }
    }
}
?>