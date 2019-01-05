<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;

/**
 * Product editing class
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_group_edit extends midcom_baseclasses_components_handler
{
    /**
     * The product to display
     *
     * @var org_openpsa_products_product_group_dba
     */
    private $_group = null;

    /**
     * Looks up a product to display.
     *
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit(array $args, array &$data)
    {
        $this->_group = new org_openpsa_products_product_group_dba($args[0]);

        $dm = new datamanager($data['schemadb_group']);
        $data['controller'] = $dm
            ->set_storage($this->_group)
            ->get_controller();

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_group->title));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
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
