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
    /**
     * The product we're working on
     *
     * @var org_openpsa_products_product_dba
     */
    private $_product;

    /**
     * @var org_openpsa_products_product_group_dba
     */
    private $parent;

    private function load_controller($schema)
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
     *
     * @param Request $request The request object
     * @param array $data The local request data.
     * @param string $schema DM schema
     * @param string $group The product group GUID (or ID apparently)
     */
    public function _handler_create(Request $request, array &$data, $schema, $group = null)
    {
        $this->find_parent($group);
        $this->_product = new org_openpsa_products_product_dba();

        $data['controller'] = $this->load_controller($schema);

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    private function find_parent($group)
    {
        if (mgd_is_guid($group)) {
            $qb2 = org_openpsa_products_product_group_dba::new_query_builder();
            $qb2->add_constraint('guid', '=', $group);
            $up_group = $qb2->execute();
            if (!empty($up_group)) {
                //We just pick the first category here
                $qb = org_openpsa_products_product_group_dba::new_query_builder();
                $qb->add_constraint('up', '=', $up_group[0]->id);
                $qb->add_order('code', 'ASC');
                $qb->set_limit(1);
                $up_group = $qb->execute();
                if (count($up_group) == 1) {
                    $this->parent = $up_group[0];
                }
            }
        } elseif ((int) $group > 0) {
            try {
                $this->parent = new org_openpsa_products_product_group_dba((int) $group);
            } catch (midcom_error $e) {
                $e->log();
            }
        }

        if (!$this->parent) {
            midcom::get()->auth->require_user_do('midgard:create', null, org_openpsa_products_product_dba::class);
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

        midcom::get()->cache->invalidate($this->_product->guid);

        return $this->router->generate('view_product', ['guid' => $this->_product->guid]);
    }
}
