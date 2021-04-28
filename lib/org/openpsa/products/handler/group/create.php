<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Product database create group handler
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_group_create extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_products_product_group_dba
     */
    private $_group;

    private function load_controller(string $schema, int $up) : controller
    {
        $schemadb = $this->_request_data['schemadb_group'];
        if (!$schemadb->has($schema)) {
            throw new midcom_error_notfound('Schema ' . $schema . ' was not found it schemadb');
        }
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($schemadb->get($schema)->get('description'))));

        $qb = org_openpsa_products_product_group_dba::new_query_builder();
        $qb->add_constraint('up', '=', $up);
        $existing_groups = $qb->count_unchecked();

        $defaults = [
            'up' => $up,
            'code' => $existing_groups + 1
        ];

        $dm = new datamanager($schemadb);
        return $dm
            ->set_defaults($defaults)
            ->set_storage($this->_group, $schema)
            ->get_controller();
    }

    /**
     * Displays a group create view.
     */
    public function _handler_create(Request $request, int $group, string $schema, array &$data)
    {
        $up = $group;

        if ($up == 0) {
            midcom::get()->auth->require_user_do('midgard:create', null, org_openpsa_products_product_group_dba::class);
        } else {
            $parent = new org_openpsa_products_product_group_dba($up);
            $parent->require_do('midgard:create');
        }

        $this->_group = new org_openpsa_products_product_group_dba();
        $this->_group->up = $up;

        $data['controller'] = $this->load_controller($schema, $up);

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    public function save_callback(controller $controller)
    {
        if ($this->_config->get('index_groups')) {
            // Index the group
            $indexer = midcom::get()->indexer;
            org_openpsa_products_viewer::index($controller->get_datamanager(), $indexer, $this->_topic);
        }
        midcom::get()->cache->invalidate($this->_topic->guid);
        return $this->router->generate('list_group', ['guid' => $this->_group->guid]);
    }
}
