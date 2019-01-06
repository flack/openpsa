<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

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
    private $_deliverable;

    /**
     * The salesproject the deliverable is connected to
     *
     * @var org_openpsa_sales_salesproject_dba
     */
    private $_salesproject;

    /**
     * Processes a deliverable.
     *
     * @param string $guid The deliverable GUID
     */
    public function _handler_process(Request $request, $guid)
    {
        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba($guid);
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($this->_deliverable->salesproject);

        $supported_operations = [
            'decline', 'order', 'deliver', 'invoice', 'run_cycle'
        ];

        foreach ($supported_operations as $operation) {
            if ($request->request->has($operation)) {
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
