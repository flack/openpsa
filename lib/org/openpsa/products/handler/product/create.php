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
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['schema'] =& $this->_schema;
        $this->_request_data['schemadb'] =& $this->_schemadb;
    }

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
    public function & dm2_create_callback (&$controller)
    {
        $this->_product = new org_openpsa_products_product_dba();

        $this->_request_data['up'] = $controller->formmanager->get_value('productGroup');
        $this->_product->productGroup = $this->_request_data['up'];

        if (! $this->_product->create())
        {
            debug_print_r('We operated on this object:', $this->_product);
            throw new midcom_error("Failed to create a new product under product group #{$this->_request_data['up']}. Error: " . midcom_connection::get_error_string());
        }

        return $this->_product;
    }

    /**
     * Displays an article edit view.
     *
     * Note, that the article for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation article
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->_master->find_parent($args);

        if ($handler_id == 'create_product')
        {
            $data['selected_schema'] = $args[0];
        }
        else
        {
            $data['selected_schema'] = $args[1];
        }

        if (!array_key_exists($data['selected_schema'], $data['schemadb_product']))
        {
            throw new midcom_error_notfound('Schema ' . $data['selected_schema'] . ' was not found in schemadb');
        }
        $this->_schema =& $data['selected_schema'];

        $data['controller'] = $this->get_controller('create');

        switch ($data['controller']->process_form())
        {
            case 'save':

                if ($this->_config->get('index_products'))
                {
                    // Index the product
                    $indexer = midcom::get('indexer');
                    org_openpsa_products_viewer::index($data['controller']->datamanager, $indexer, $this->_topic);
                }

                midcom::get('cache')->invalidate($this->_product->guid);

                return new midcom_response_relocate("product/{$this->_product->guid}/");

            case 'cancel':
                if ($this->_request_data['up'] == 0)
                {
                    return new midcom_response_relocate('');
                }
                return new midcom_response_relocate("{$this->_request_data['up']}/");
        }

        $this->_prepare_request_data();

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $this->_request_data['view_title'] = sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description));
        midcom::get('head')->set_pagetitle($this->_request_data['view_title']);

        $this->_update_breadcrumb_line();
    }

    /**
     * Shows the loaded article.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_create($handler_id, array &$data)
    {
        midcom_show_style('product_create');
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     */
    private function _update_breadcrumb_line()
    {
        $tmp = array();

        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "product/create/",
            MIDCOM_NAV_NAME => $this->_request_data['view_title'],
        );

        if (isset($this->_request_data['parent']))
        {
            $group = $this->_request_data['parent'];
            $root_group = $this->_config->get('root_group');

            if (!$group)
            {
                return false;
            }

            $parent = $group;

            while ($parent)
            {
                $group = $parent;

                if (   $group->guid === $root_group
                    || !$group->guid)
                {
                    break;
                }

                if ($group->code)
                {
                    $url = "{$group->code}/";
                }
                else
                {
                    $url = "{$group->guid}/";
                }

                $tmp[] = Array
                (
                    MIDCOM_NAV_URL => $url,
                    MIDCOM_NAV_NAME => $group->title,
                );
                $parent = $group->get_parent();
            }
        }

        $reversed = array_reverse($tmp);
        midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $reversed);
    }
}
?>
