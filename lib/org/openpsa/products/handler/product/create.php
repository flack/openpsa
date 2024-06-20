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
 * Product database create product handler
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_product_create extends midcom_baseclasses_components_handler
{
    private org_openpsa_products_product_dba $_product;

    private ?org_openpsa_products_product_group_dba $parent = null;

    private function load_controller(string $schema) : controller
    {
        $schemadb = $this->_request_data['schemadb_product'];
        if (!$schemadb->has($schema)) {
            throw new midcom_error_notfound('Schema ' . $schema . ' was not found in schemadb');
        }
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($schemadb->get($schema)->get('description'))));

        $defaults = [];
        if ($this->parent) {
            $defaults['productGroup'] = $this->parent->id;
        }

        $dm = new datamanager($schemadb);
        return $dm
            ->set_defaults($defaults)
            ->set_storage($this->_product, $schema)
            ->get_controller();
    }

    /**
     * Displays an product create view.
     */
    public function _handler_create(Request $request, array &$data, string $schema, ?int $group = null)
    {
        $this->find_parent($group);
        $this->_product = new org_openpsa_products_product_dba();

        $data['controller'] = $this->load_controller($schema);

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => $this->save_callback(...)
        ]);
        return $workflow->run($request);
    }

    private function find_parent(?int $group = null)
    {
        if ($group > 0) {
            try {
                $this->parent = new org_openpsa_products_product_group_dba($group);
            } catch (midcom_error $e) {
                $e->log();
            }
        }

        if (!$this->parent) {
            midcom::get()->auth->require_user_do('midgard:create', class: org_openpsa_products_product_dba::class);
        } else {
            $this->parent->require_do('midgard:create');
        }
    }

    public function save_callback(controller $controller)
    {
        if ($this->_config->get('index_products')) {
            // Index the product
            $indexer = midcom::get()->indexer;
            org_openpsa_products_viewer::index($controller->get_datamanager(), $indexer, $this->_topic);
        }

        return $this->router->generate('view_product', ['guid' => $this->_product->guid]);
    }
}
