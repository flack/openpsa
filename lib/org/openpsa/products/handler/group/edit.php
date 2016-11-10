<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

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
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_group = new org_openpsa_products_product_group_dba($args[0]);

        $data['controller'] = midcom_helper_datamanager2_controller::create('simple');
        $data['controller']->schemadb =& $this->_request_data['schemadb_group'];
        $data['controller']->set_storage($this->_group);
        if (!$data['controller']->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 controller instance for product {$this->_group->id}.");
        }

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_group->title));

        $workflow = $this->get_workflow('datamanager2', array
        (
            'controller' => $data['controller'],
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        if ($this->_config->get('index_groups')) {
            // Index the group
            $indexer = midcom::get()->indexer;
            org_openpsa_products_viewer::index($controller->datamanager, $indexer, $this->_topic);
        }
        midcom::get()->cache->invalidate($this->_topic->guid);
        return "{$this->_group->guid}/";
    }
}
