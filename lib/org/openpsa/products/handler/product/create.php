<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Product database create product handler
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_product_create extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * The article which has been created
     *
     * @var org_openpsa_products_product_dba
     */
    private $_product = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    /**
     * The schema to use for the new article.
     *
     * @var string
     */
    private $_schema = 'default';

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        $this->_schemadb =& $this->_request_data['schemadb_product'];

        return $this->_schemadb;
    }

    public function get_schema_name()
    {
        return $this->_schema;
    }

    public function get_schema_defaults()
    {
        return array('productGroup' => $this->_request_data['up']);
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->_product = new org_openpsa_products_product_dba();

        $this->_request_data['up'] = $controller->formmanager->get_value('productGroup');
        $this->_product->productGroup = $this->_request_data['up'];

        if (!$this->_product->create()) {
            debug_print_r('We operated on this object:', $this->_product);
            throw new midcom_error("Failed to create a new product under product group #{$this->_request_data['up']}. Error: " . midcom_connection::get_error_string());
        }

        return $this->_product;
    }

    /**
     * Displays an product create view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->_master->find_parent($args);

        if ($handler_id == 'create_product') {
            $this->_schema = $args[0];
        } else {
            $this->_schema = $args[1];
        }

        if (!array_key_exists($this->_schema, $data['schemadb_product'])) {
            throw new midcom_error_notfound('Schema ' . $this->_schema . ' was not found in schemadb');
        }

        $data['controller'] = $this->get_controller('create');
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description)));

        $workflow = $this->get_workflow('datamanager2', array(
            'controller' => $data['controller'],
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        if ($this->_config->get('index_products')) {
            // Index the product
            $indexer = midcom::get()->indexer;
            org_openpsa_products_viewer::index($controller->datamanager, $indexer, $this->_topic);
        }

        midcom::get()->cache->invalidate($this->_product->guid);

        return "product/{$this->_product->guid}/";
    }
}
