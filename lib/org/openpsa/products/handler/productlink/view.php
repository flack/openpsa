<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Product display class
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_productlink_view extends midcom_baseclasses_components_handler
{
    /**
     * The product to display
     *
     * @var midcom_db_product
     */
    private $_productlink = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['productlink'] =& $this->_productlink;

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "productlink/edit/{$this->_productlink->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_productlink->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "productlink/delete/{$this->_productlink->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_productlink->can_do('midgard:delete'),
            )
        );
    }

    /**
     * Looks up a product to display.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        if (preg_match('/_raw$/', $handler_id))
        {
            $_MIDCOM->skip_page_style = true;
        }

        $this->_productlink = new org_openpsa_products_product_link_dba($args[0]);

        $data['controller'] = null;
        $data['datamanager'] = new midcom_helper_datamanager2_datamanager($data['schemadb_productlink']);
        if (!$data['datamanager']->autoset_storage($this->_productlink))
        {
            throw new midcom_error("Failed to create a DM2 instance for productlink {$this->_productlink->guid}.");
        }

        $this->_prepare_request_data();
        $_MIDCOM->bind_view_to_object($this->_productlink, $data['datamanager']->schema->name);

        $breadcrumb = org_openpsa_products_viewer::update_breadcrumb_line($this->_productlink);
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $breadcrumb);

        midcom::get('metadata')->set_request_metadata($this->_productlink->metadata->revised, $this->_productlink->guid);

//        midcom::get('head')->set_pagetitle($this->_request_data['view_title']);
    }

    /**
     * Shows the loaded product.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        $data['view_productlink'] = $data['datamanager']->get_content_html();
        midcom_show_style('productlink_view');
    }
}
?>