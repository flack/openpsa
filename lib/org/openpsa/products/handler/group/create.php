<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Product database create group handler
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_group_create extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * The article which has been created
     *
     * @var org_openpsa_products_product_group_dba
     */
    private $_group = null;

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
        $this->_schemadb = $this->_request_data['schemadb_group'];
        return $this->_schemadb;
    }

    public function get_schema_name()
    {
        return $this->_schema;
    }

    public function get_schema_defaults()
    {
        $defaults = array();
        $qb = org_openpsa_products_product_group_dba::new_query_builder();
        $qb->add_constraint('up', '=', $this->_request_data['up']);
        $existing_groups = $qb->count_unchecked();

        $defaults['code'] = $existing_groups + 1;
        $defaults['up'] = $this->_request_data['up'];
        return $defaults;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->_group = new org_openpsa_products_product_group_dba();
        $this->_group->up = $this->_request_data['up'];

        if (!$this->_group->create())
        {
            debug_print_r('We operated on this object:', $this->_group);
            throw new midcom_error('Failed to create a new product group. Last Midgard error was: ' . midcom_connection::get_error_string());
        }

        return $this->_group;
    }

    /**
     * Displays an group create view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $data['up'] = (int) $args[0];

        if ($data['up'] == 0)
        {
            midcom::get()->auth->require_user_do('midgard:create', null, 'org_openpsa_products_product_group_dba');
        }
        else
        {
            $parent = new org_openpsa_products_product_group_dba($data['up']);
            $parent->require_do('midgard:create');
        }

        $data['selected_schema'] = $args[1];
        if (!array_key_exists($data['selected_schema'], $data['schemadb_group']))
        {
            throw new midcom_error_notfound('Schema ' . $data['selected_schema'] . ' was not found it schemadb');
        }
        $this->_schema = $data['selected_schema'];
        $data['controller'] = $this->get_controller('create');

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description)));

        $workflow = $this->get_workflow('datamanager2', array
        (
            'controller' => $data['controller'],
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        if ($this->_config->get('index_groups'))
        {
            // Index the group
            $indexer = midcom::get()->indexer;
            org_openpsa_products_viewer::index($controller->datamanager, $indexer, $this->_topic);
        }
        midcom::get()->cache->invalidate($this->_topic->guid);
        return "{$this->_group->guid}/";
    }
}
