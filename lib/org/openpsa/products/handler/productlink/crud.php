<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Productlink management handler
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_productlink_crud extends midcom_baseclasses_components_handler_crud
{
    public function __construct()
    {
        $this->_dba_class = 'org_openpsa_products_product_link_dba';
    }

    /**
     * Return the URL to the productlink view handler
     *
     * @inheritdoc
     */
    public function _get_object_url(midcom_core_dbaobject $object)
    {
        if ($object->code)
        {
            if ($object->productGroup)
            {
                $this->_group = new org_openpsa_products_product_group_dba($object->productGroup);
                if ($this->_group->up)
                {
                    $this->_group_up = new org_openpsa_products_product_group_dba($this->_group->up);
                }
            }
            if (   isset($this->_group_up)
                && isset($this->_group))
            {
                return "productlink/{$this->_group_up->code}/{$object->code}/";
            }
            else
            {
                return "productlink/{$object->code}/";
            }
        }
        return "productlink/{$object->guid}/";
    }

    public function _update_breadcrumb($handler_id)
    {
        // Get common breadcrumb for the product
        $breadcrumb = org_openpsa_products_viewer::update_breadcrumb_line($this->_object);

        // Handler-based additions
        switch ($handler_id)
        {
            case 'edit_productlink':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => '',
                    MIDCOM_NAV_NAME => sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('productlink')),
                );
                break;
            case 'delete_productlink':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => '',
                    MIDCOM_NAV_NAME => sprintf($this->_l10n_midcom->get('delete %s'), $this->_l10n->get('productlink')),
                );
                break;
        }

        midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $breadcrumb);
    }

    public function _populate_toolbar($handler_id)
    {
        if ($this->_mode == 'delete')
        {
            org_openpsa_helpers::dm2_savecancel($this, 'delete');
        }
        if ($this->_object->can_do('midgard:update'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "productlink/edit/{$this->_object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );
        }

        if ($this->_object->can_do('midgard:delete'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "productlink/delete/{$this->_object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'd',
                )
            );
        }
    }

    /**
     * Load the schema for products
     */
    public function _load_schemadb()
    {
        $this->_schemadb =& $this->_request_data['schemadb_productlink'];
    }

    /**
     * Show method is overridden so that we can keep the style element naming consistent
     */
    public function _show_update($handler_id, array &$data)
    {
        $this->_request_data['view_productlink'] = $this->_request_data['controller']->datamanager->get_content_html();
        midcom_show_style('productlink_edit');
    }

    /**
     * Show method is overridden so that we can keep the style element naming consistent
     */
    public function _show_delete($handler_id, array &$data)
    {
        midcom_show_style('productlink_delete');
    }
}
?>