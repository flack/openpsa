<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Deliverable processing class
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_deliverable_process extends midcom_baseclasses_components_handler
{
    /**
     * The deliverable we're working with
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

    /**
     * Processes a deliverable.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_process($handler_id, $args, &$data)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST')
        {
            throw new midcom_error_forbidden('Only POST requests are allowed here.');
        }

        $this->_deliverable = $this->load_object('org_openpsa_sales_salesproject_deliverable_dba', $args[0]);
        $this->_salesproject = $this->load_object('org_openpsa_sales_salesproject_dba', $this->_deliverable->salesproject);
        $this->_product = $this->load_object('org_openpsa_products_product_dba', $this->_deliverable->product);

        // Check what status change user requested
        if (array_key_exists('mark_proposed', $_POST))
        {
            if (!$this->_deliverable->propose())
            {
                throw new midcom_error('Failed to mark the deliverable as proposed. Last Midgard error was: ' . midcom_connection::get_error_string());
            }
        }
        else if (array_key_exists('mark_declined', $_POST))
        {
            if (!$this->_deliverable->decline())
            {
                throw new midcom_error('Failed to mark the deliverable as declined. Last Midgard error was: ' . midcom_connection::get_error_string());
            }
        }
        else if (array_key_exists('mark_ordered', $_POST))
        {
            if (!$this->_deliverable->order())
            {
                throw new midcom_error('Failed to mark the deliverable as ordered. Last Midgard error was: ' . midcom_connection::get_error_string());
            }
        }
        else if (array_key_exists('mark_delivered', $_POST))
        {
            if (!$this->_deliverable->deliver())
            {
                throw new midcom_error('Failed to mark the deliverable as delivered. Last Midgard error was: ' . midcom_connection::get_error_string());
            }
        }
        else if (   array_key_exists('mark_invoiced', $_POST)
                && array_key_exists('invoice', $_POST))
        {
            if (!$this->_deliverable->invoice($_POST['invoice']))
            {
                throw new midcom_error('Failed to mark the deliverable as invoiced. Last Midgard error was: ' . midcom_connection::get_error_string());
            }
        }
        else
        {
            throw new midcom_error('No procedure specified.');
        }

        // Get user back to the sales project
        $_MIDCOM->relocate("salesproject/{$this->_salesproject->guid}/");
        // This will exit.
    }
}
?>