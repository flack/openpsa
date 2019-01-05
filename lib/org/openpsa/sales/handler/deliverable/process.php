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
     * Processes a deliverable.
     *
     * @param array $args The argument list.
     */
    public function _handler_process(array $args)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            throw new midcom_error_forbidden('Only POST requests are allowed here.');
        }

        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args[0]);
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($this->_deliverable->salesproject);

        $supported_operations = [
            'decline', 'order', 'deliver', 'invoice', 'run_cycle'
        ];

        foreach ($supported_operations as $operation) {
            if (array_key_exists($operation, $_POST)) {
                if (!$this->_deliverable->$operation()) {
                    throw new midcom_error('Operation failed. Last Midgard error was: ' . midcom_connection::get_error_string());
                }
                // Get user back to the sales project
                return new midcom_response_relocate($this->router->generate('salesproject_view', ['guid' => $this->_salesproject->guid]));
            }
        }

        throw new midcom_error('No valid operation specified.');
    }
}
