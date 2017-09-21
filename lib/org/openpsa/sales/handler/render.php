<?php
/**
 * @package org.openpsa.sales
 * @author CONTENT CONTROL, http://contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL, http://contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Sales render handler creates pdf-file
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_render extends midcom_baseclasses_components_handler
{
    private $salesproject = null;

    private $client_class;

    private function load_pdf_builder(org_openpsa_sales_salesproject_dba $salesproject)
    {
        $client_class = midcom_baseclasses_components_configuration::get('org.openpsa.sales', 'config')->get('sales_pdfbuilder_class');
        if (!class_exists($client_class)) {
            throw new midcom_error('Could not find PDF renderer ' . $client_class);
        }

        $this->client_class = new $client_class($salesproject);
    }

    /**
     * @param mixed $handler_id The ID of the handler._config
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return midcom_response
     */
    public function _handler_create_pdf($handler_id, array $args, array &$data)
    {
        $this->salesproject = new org_openpsa_sales_salesproject_dba($args[0]);
        $this->salesproject->require_do('midgard:update');

        $this->load_pdf_builder($this->salesproject);
        return $this->client_class->handle();
    }
}
