<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\controller;
use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Product management handler
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_product_admin extends midcom_baseclasses_components_handler
{
    private org_openpsa_products_product_dba $product;

    /**
     * Generates an product update view.
     */
    public function _handler_update(Request $request, string $guid, array &$data)
    {
        $this->product = new org_openpsa_products_product_dba($guid);
        $this->product->require_do('midgard:update');

        $dm = new datamanager($data['schemadb_product']);
        $data['controller'] = $dm
            ->set_storage($this->product)
            ->get_controller();

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->product->title));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    public function save_callback(controller $controller)
    {
        if ($this->_config->get('index_products')) {
            // Index the product
            $indexer = midcom::get()->indexer;
            org_openpsa_products_viewer::index($controller->get_datamanager(), $indexer, $this->_topic);
        }

        midcom::get()->cache->invalidate($this->product->guid);
        return $this->router->generate('view_product', ['guid' => $this->product->guid]);
    }

    /**
     * Process object delete
     */
    public function _handler_delete(Request $request, string $guid)
    {
        $this->product = new org_openpsa_products_product_dba($guid);
        $workflow = $this->get_workflow('delete', ['object' => $this->product]);
        return $workflow->run($request);
    }
}
