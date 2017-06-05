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
     *
     * @var org_openpsa_products_product_group_dba
     */
    private $parent;

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
        if (!$this->parent) {
            return [];
        }
        return ['productGroup' => $this->parent->id];
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->_product = new org_openpsa_products_product_dba();
        $this->_product->productGroup = $controller->formmanager->get_value('productGroup');

        if (!$this->_product->create()) {
            debug_print_r('We operated on this object:', $this->_product);
            throw new midcom_error("Failed to create a new product under product group #{$this->_product->productGroup}. Error: " . midcom_connection::get_error_string());
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
        $this->find_parent($args);

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

        $workflow = $this->get_workflow('datamanager2', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    private function find_parent($args)
    {
        if (mgd_is_guid($args[0])) {
            $qb2 = org_openpsa_products_product_group_dba::new_query_builder();
            $qb2->add_constraint('guid', '=', $args[0]);
            $up_group = $qb2->execute();
            if (count($up_group)) {
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
        } elseif ((int) $args[0] > 0) {
            try {
                $this->parent = new org_openpsa_products_product_group_dba((int) $args[0]);
            } catch (midcom_error $e) {
                $e->log();
            }
        }

        if (!$this->parent) {
            midcom::get()->auth->require_user_do('midgard:create', null, 'org_openpsa_products_product_dba');
        } else {
            $this->parent->require_do('midgard:create');
        }
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
