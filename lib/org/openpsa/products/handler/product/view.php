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
class org_openpsa_products_handler_product_view extends midcom_baseclasses_components_handler
{
    /**
     * The product to display
     *
     * @var midcom_db_product
     */
    private $_product = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['product'] =& $this->_product;

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "product/edit/{$this->_product->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_product->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "product/delete/{$this->_product->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_product->can_do('midgard:delete'),
            )
        );
        if (   $this->_config->get('enable_productlinks')
            && $this->_request_data['is_linked_from'] != '')
        {
            $product_link_guid = $this->_request_data['is_linked_from'];
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "productlink/{$product_link_guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('view productlink'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/view.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_product->can_do('midgard:update'),
                )
            );
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "productlink/edit/{$product_link_guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit productlink'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_product->can_do('midgard:update'),
                )
            );
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "productlink/delete/{$product_link_guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete productlink'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_product->can_do('midgard:delete'),
                )
            );
        }

        if ($_MIDCOM->componentloader->is_installed('org.openpsa.relatedto'))
        {
            org_openpsa_relatedto_plugin::add_button($this->_view_toolbar, $this->_product->guid);
        }
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

        $qb = org_openpsa_products_product_dba::new_query_builder();
        if (preg_match('/^view_product_intree/', $handler_id))
        {
            $group_qb = org_openpsa_products_product_group_dba::new_query_builder();
            if (mgd_is_guid($args[0]))
            {
                $group_qb->add_constraint('guid', '=', $args[0]);
            }
            else
            {
                $group_qb->add_constraint('code', '=', $args[0]);
            }
            $groups = $group_qb->execute();

            if (empty($groups))
            {
                throw new midcom_error_notfound("Product group {$args[0]} not found" );
            }

            $categories_mc = org_openpsa_products_product_group_dba::new_collector('up', $groups[0]->id);
            $categories_in = $categories_mc->get_values('id');

            if (count($categories_in) == 0)
            {
                /* No matching categories belonging to this group
                 * So we can search for the application using only
                 * this group id
                 */
                //Commented out for now because of http://trac.midgard-project.org/ticket/1976
                //$qb->add_constraint('productGroup', 'INTREE', $groups[0]->id);
                //workaround:
                $qb->begin_group('OR');
                $qb->add_constraint('productGroup.up', 'INTREE', $groups[0]->id);
                $qb->add_constraint('productGroup', '=', $groups[0]->id);
                $qb->end_group();
            }
            else
            {
                $categories_in[] = $groups[0]->id;
                $qb->add_constraint('productGroup', 'IN', $categories_in);
            }

            if (mgd_is_guid($args[1]))
            {
                $qb->add_constraint('guid', '=', $args[1]);
            }
            else
            {
                $qb->add_constraint('code', '=', $args[1]);
            }
        }
        else
        {
            if (mgd_is_guid($args[0]))
            {
                $qb->add_constraint('guid', '=', $args[0]);
            }
            else
            {
                $qb->add_constraint('code', '=', $args[0]);
            }
        }

        if ($this->_config->get('enable_scheduling'))
        {
            $qb->add_constraint('start', '<=', time());
            $qb->begin_group('OR');
                /*
                 * List products that either have no defined end-of-market dates
                 * or are still in market
                 */
                $qb->add_constraint('end', '=', 0);
                $qb->add_constraint('end', '>=', time());
            $qb->end_group();
        }

        $results = $qb->execute();

        $this->_request_data['is_linked_from'] = '';

        if (!empty($results))
        {
            $this->_product = $results[0];

            if (   $this->_config->get('enable_productlinks')
                && $this->_product->productGroup != 0)
            {
                $root_group_guid = $this->_config->get('root_group');
                if ($root_group_guid != '')
                {
                    $root_group = org_openpsa_products_product_group_dba::get_cached($root_group_guid);
                }

                if ($root_group->id != $this->_product->productGroup)
                {
                    $product_group = new org_openpsa_products_product_group_dba($this->_product->productGroup);

                    $mc_intree = org_openpsa_products_product_group_dba::new_collector('id', $product_group->id);
                    $mc_intree->add_constraint('up', 'INTREE', $root_group->id);
                    $count = $mc_intree->count();
                    if ($count == 0)
                    {
                        $mc_intree = org_openpsa_products_product_link_dba::new_collector('product', $this->_product->id);
                        $mc_intree->add_constraint('productGroup', 'INTREE', $root_group->id);
                        $mc_intree->execute();
                        $results = $mc_intree->list_keys();
                        if (count($results) > 0)
                        {
                            foreach ($results as $guid => $array)
                            {
                                $this->_request_data['is_linked_from'] = $guid;
                            }
                        }
                    }
                }
            }
        }
        else
        {
            if (preg_match('/^view_product_intree/', $handler_id))
            {
                $this->_product = new org_openpsa_products_product_dba($args[1]);
            }
            else
            {
                $this->_product = new org_openpsa_products_product_dba($args[0]);
            }
        }

        if ($GLOBALS['midcom_config']['enable_ajax_editing'])
        {
            $data['controller'] = midcom_helper_datamanager2_controller::create('ajax');
            $data['controller']->schemadb =& $data['schemadb_product'];
            $data['controller']->set_storage($this->_product);
            $data['controller']->process_ajax();
            $data['datamanager'] =& $data['controller']->datamanager;
        }
        else
        {
            $data['controller'] = null;
            $data['datamanager'] = new midcom_helper_datamanager2_datamanager($data['schemadb_product']);
            if (! $data['datamanager']->autoset_storage($this->_product))
            {
                throw new midcom_error("Failed to create a DM2 instance for product {$this->_product->guid}.");
            }
        }

        $this->_prepare_request_data();
        $_MIDCOM->bind_view_to_object($this->_product, $data['datamanager']->schema->name);

        if (isset($product_group))
        {
            unset($product_group);
        }

        $product_group = null;

        if ($this->_request_data['is_linked_from'] != '')
        {
            $linked_product = new org_openpsa_products_product_link_dba($data['is_linked_from']);

            if ($linked_product->productGroup != 0)
            {
                $product_group = new org_openpsa_products_product_group_dba($linked_product->productGroup);
            }
        }

        $breadcrumb = org_openpsa_products_viewer::update_breadcrumb_line($this->_product, $product_group);

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $breadcrumb);

        $_MIDCOM->set_26_request_metadata($this->_product->metadata->revised, $this->_product->guid);

        $title = $this->_config->get('product_page_title');

        if (strstr($title, '<PRODUCTGROUP'))
        {
            try
            {
                $productgroup = new org_openpsa_products_product_group_dba($this->_product->productGroup);
                $title = str_replace('<PRODUCTGROUP_TITLE>', $productgroup->title, $title);
                $title = str_replace('<PRODUCTGROUP_CODE>', $productgroup->code, $title);
            }
            catch (midcom_error $e)
            {
                $title = str_replace('<PRODUCTGROUP_TITLE>', '', $title);
                $title = str_replace('<PRODUCTGROUP_CODE>', '', $title);
            }
        }

        $title = str_replace('<PRODUCT_CODE>', $this->_product->code, $title);
        $title = str_replace('<PRODUCT_TITLE>', $this->_product->title, $title);
        $title = str_replace('<TOPIC_TITLE>', $this->_topic->extra, $title);

        $_MIDCOM->set_pagetitle($title);
    }

    /**
     * Shows the loaded product.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        if ($data['controller'])
        {
            // For AJAX handling it is the controller that renders everything
            $data['view_product'] = $data['controller']->get_content_html();
        }
        else
        {
            $data['view_product'] = $data['datamanager']->get_content_html();
        }
        midcom_show_style('product_view');
    }
}
?>