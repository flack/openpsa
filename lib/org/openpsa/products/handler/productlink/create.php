<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Productlink database create product handler
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_productlink_create extends midcom_baseclasses_components_handler
{
    /**
     * The product which has been created
     *
     * @var org_openpsa_products_product_dba
     */
    private $_productlink = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    /**
     * The schema to use for the new productklink.
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
        $this->_request_data['indexmode'] =& $this->_indexmode;
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
        $this->_schemadb =& $this->_request_data['schemadb_productlink'];
        return $this->_schemadb;
    }

    public function get_schema_name()
    {
        return $this->_schema;
    }

    public function get_schema_defaults()
    {
        $defaults = array();
        $defaults['productGroup'] = $this->_request_data['up'];
        return $defaults;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback (&$controller)
    {
        $this->_productlink = new org_openpsa_products_product_link_dba();

        if (isset($_POST['productGroup']))
        {
            $this->_request_data['up'] = (int) $_POST['productGroup'];
        }
        $this->_productlink->productGroup = $this->_request_data['up'];
        if (isset($_POST['product']))
        {
            $this->_request_data['product'] = (int) $_POST['product'];
        }
        $this->_productlink->product = $this->_request_data['product'];

        if (! $this->_productlink->create())
        {
            debug_print_r('We operated on this object:', $this->_productlink);
            throw new midcom_error("Failed to create a new productlink under product group #{$this->_request_data['up']}. Error: " . midcom_connection::get_error_string());
        }

        return $this->_productlink;
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

        $data['selected_schema'] = $args[1];
        if (!array_key_exists($data['selected_schema'], $data['schemadb_productlink']))
        {
            throw new midcom_error_notfound('Invalid schema selected');
        }
        $this->_schema =& $data['selected_schema'];

        $data['controller'] = $this->get_controller('create');

        switch ($data['controller']->process_form())
        {
            case 'save':
                midcom::get('cache')->invalidate($this->_productlink->guid);

                return new midcom_response_relocate("productlink/{$this->_productlink->guid}/");

            case 'cancel':
                if ($this->_request_data['up'] == 0)
                {
                    return new midcom_response_relocate('');
                }
                else
                {
                    return new midcom_response_relocate("{$this->_request_data['up']}/");
                }
        }

        $this->_prepare_request_data();

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        if ($this->_productlink)
        {
            midcom::get('metadata')->set_request_metadata($this->_productlink->metadata->revised, $this->_productlink->guid);
        }
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
        midcom_show_style('productlink_create');
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
            MIDCOM_NAV_URL => "productlink/create/",
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

        midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', array_reverse($tmp));
    }
}
?>