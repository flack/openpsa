<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: process.php 23975 2009-11-09 05:44:22Z rambo $
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
     * @access private
     */
    var $_deliverable = null;

    /**
     * The salesproject the deliverable is connected to
     *
     * @var org_openpsa_sales_salesproject_dba
     * @access private
     */
    var $_salesproject = null;

    /**
     * The product to deliver
     *
     * @var org_openpsa_products_product_dba
     * @access private
     */
    var $_product = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    function _prepare_request_data()
    {
        $this->_request_data['deliverable'] =& $this->_deliverable;
    }


    /**
     * Processes a deliverable.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_process($handler_id, $args, &$data)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST')
        {
            $_MIDCOM->generate_error(MIDCOM_ERRFORBIDDEN, 'Only POST requests are allowed here.');
        }

        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args[0]);
        if (!$this->_deliverable)
        {
            return false;
        }

        $this->_salesproject = new org_openpsa_sales_salesproject_dba($this->_deliverable->salesproject);
        if (!$this->_salesproject)
        {
            return false;
        }

        $this->_product = new org_openpsa_products_product_dba($this->_deliverable->product);
        if (!$this->_product)
        {
            return false;
        }

        // Check what status change user requested
        if (array_key_exists('mark_proposed', $_POST))
        {
            if (!$this->_deliverable->propose())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    'Failed to mark the deliverable as proposed, cannot continue. Last Midgard error was: ' . midcom_connection::get_error_string());
                // This will exit.
            }
        }
        else if (array_key_exists('mark_declined', $_POST))
        {
            if (!$this->_deliverable->decline())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    'Failed to mark the deliverable as declined, cannot continue. Last Midgard error was: ' . midcom_connection::get_error_string());
                // This will exit.
            }
        }
        else if (array_key_exists('mark_ordered', $_POST))
        {
            if (!$this->_deliverable->order())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    'Failed to mark the deliverable as ordered, cannot continue. Last Midgard error was: ' . midcom_connection::get_error_string());
                // This will exit.
            }
        }
        else if (array_key_exists('mark_delivered', $_POST))
        {
            if (!$this->_deliverable->deliver())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    'Failed to mark the deliverable as delivered, cannot continue. Last Midgard error was: ' . midcom_connection::get_error_string());
                // This will exit.
            }
        }
        else if (   array_key_exists('mark_invoiced', $_POST)
                && array_key_exists('invoice', $_POST))
        {
            if (!$this->_deliverable->invoice($_POST['invoice']))
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    'Failed to mark the deliverable as invoiced, cannot continue. Last Midgard error was: ' . midcom_connection::get_error_string());
                // This will exit.
            }
        }
        else
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'No procedure specified, aborting.');
        }

        // Get user back to the sales project
        $_MIDCOM->relocate("salesproject/{$this->_salesproject->guid}/");
        // This will exit.
    }
}
?>