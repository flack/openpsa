<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Product management handler
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_product_crud extends midcom_baseclasses_components_handler_crud
{
    protected $_dba_class = 'org_openpsa_products_product_dba';

    /**
     * Return the URL to the product view handler
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
                return "product/{$this->_group_up->code}/{$object->code}/";
            }
            return "product/{$object->code}/";
        }
        return "product/{$object->guid}/";
    }

    public function _update_breadcrumb($handler_id)
    {
        // Get common breadcrumb for the product
        $breadcrumb = org_openpsa_products_viewer::update_breadcrumb_line($this->_object);

        $breadcrumb[] = array
        (
            MIDCOM_NAV_URL => '',
            MIDCOM_NAV_NAME => sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('product')),
        );

        midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $breadcrumb);
    }

    public function _populate_toolbar($handler_id)
    {
        org_openpsa_helpers::dm2_savecancel($this);
    }

    /**
     * Load the schema for products
     */
    public function _load_schemadb()
    {
        $this->_schemadb =& $this->_request_data['schemadb_product'];
    }

    /**
     * Show method is overridden so that we can keep the style element naming consistent
     */
    public function _show_update($handler_id, array &$data)
    {
        $this->_request_data['view_product'] = $this->_request_data['controller']->datamanager->get_content_html();
        midcom_show_style('product_edit');
    }


    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_load_object($handler_id, $args, $data);

        $this->_object->require_do('midgard:delete');

        $controller = midcom_helper_datamanager2_handler::get_delete_controller();

        if ($controller->process_form() == 'delete')
        {
            if ($this->_object->delete())
            {
                $indexer = midcom::get()->indexer;
                $indexer->delete($this->_object->guid);
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n_midcom->get("%s deleted"), $this->_l10n->get('product')));
                return new midcom_response_relocate($this->_object->get_parent()->code . '/');
            }
            // Failure, give a message
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n->get("failed to delete product, reason ") . midcom_connection::get_error_string(), 'error');
        }
        return new midcom_response_relocate($this->_get_object_url($this->_object));
    }
}
