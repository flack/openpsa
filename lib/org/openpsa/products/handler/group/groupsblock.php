<?php
/**
 * Created on 2006-08-09
 * @author Henri Bergius
 * @package org.openpsa.products
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 *
 */

/**
 * The midcom_baseclasses_components_handler class defines a bunch of helper vars
 *
 * @see midcom_baseclasses_components_handler
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_group_groupsblock  extends midcom_baseclasses_components_handler
{
    /**
     * Can-Handle check against the current group GUID. We have to do this explicitly
     * in can_handle already, otherwise we would hide all subtopics as the request switch
     * accepts all argument count matches unconditionally.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean True if the request can be handled, false otherwise.
     */
    public function _can_handle_groupsblock($handler_id, array $args, array &$data)
    {
        // We're in some level of groups
        $qb = org_openpsa_products_product_group_dba::new_query_builder();
        $qb->add_constraint('code', '=', $args[0]);
        $qb->set_limit(1);
        $results = $qb->execute();

        if (count($results) == 0)
        {
            try
            {
                $data['group'] = new org_openpsa_products_product_group_dba($args[0]);
            }
            catch (midcom_error $e)
            {
                return false;
            }
        }
        else
        {
            $data['group'] = $results[0];
        }

        $data['parent_group'] = $data['group']->id;
        $data['view_title'] = "{$data['group']->code} {$data['group']->title}";
        $data['acl_object'] = $data['group'];

        return true;
    }

    /**
     * The handler for the group_groupsblock article.
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param Array &$data The local request data.
     */
    public function _handler_groupsblock($handler_id, array $args, array &$data)
    {
        // Query for sub-objects
        $group_qb = org_openpsa_products_product_group_dba::new_query_builder();

        $guidgroup_qb = org_openpsa_products_product_group_dba::new_query_builder();
        $guidgroup_qb->add_constraint('guid', '=', $args[0]);
        $groups = $guidgroup_qb->execute();

        if (count($groups) > 0)
        {
            $categories_qb = org_openpsa_products_product_group_dba::new_query_builder();
            $categories_qb->add_constraint('id', '=', $groups[0]->up);
            $categories = $categories_qb->execute();

            $data['parent_category'] = $categories[0]->code;
        }
        else
        {
            //do not set the parent category. The category is already a top category.
        }

        $group_qb->add_constraint('up', '=', $data['parent_group']);

        foreach ($this->_config->get('groups_listing_order') as $ordering)
        {
            $this->_add_ordering($group_qb, $ordering);
        }

        $data['groups'] = $group_qb->execute();
        $data['products'] = array();
        if ($this->_config->get('group_list_products'))
        {
            $this->_list_group_products();
        }

        // Prepare datamanager
        $data['datamanager_group'] = new midcom_helper_datamanager2_datamanager($data['schemadb_group']);
        $data['datamanager_product'] = new midcom_helper_datamanager2_datamanager($data['schemadb_product']);

        if ($data['group'])
        {
            if ($GLOBALS['midcom_config']['enable_ajax_editing'])
            {
                $data['controller'] = midcom_helper_datamanager2_controller::create('ajax');
                $data['controller']->schemadb =& $data['schemadb_group'];
                $data['controller']->set_storage($data['group']);
                $data['controller']->process_ajax();
                $data['datamanager_group'] =& $data['controller']->datamanager;
            }
            else
            {
                $data['controller'] = null;
                if (!$data['datamanager_group']->autoset_storage($data['group']))
                {
                    throw new midcom_error("Failed to create a DM2 instance for product group {$data['group']->guid}.");
                }
            }
            $this->bind_view_to_object($data['group'], $data['datamanager_group']->schema->name);
        }

        $this->_populate_toolbar();

        $this->_update_breadcrumb_line();

        // Set the active leaf
        if (   $this->_config->get('display_navigation')
            && $data['group'])
        {
            $group =& $data['group'];

            // Loop as long as it is possible to get the parent group
            while ($group->guid)
            {
                // Break to the requested level (probably the root group of the products content topic)
                if (   $group->id === $this->_config->get('root_group')
                    || $group->guid === $this->_config->get('root_group'))
                {
                    break;
                }
                $temp = $group->id;
                if ($group->up == 0)
                {
                    break;
                }
                $group = new org_openpsa_products_product_group_dba($group->up);
            }

            if (isset($temp))
            {
                // Active leaf of the topic
                $this->set_active_leaf($temp);
            }
        }

        midcom::get('head')->set_pagetitle($data['view_title']);
    }

    private function _list_group_products()
    {
        $product_qb = new org_openpsa_qbpager('org_openpsa_products_product_dba', 'org_openpsa_products_product_dba');
        $product_qb->results_per_page = $this->_config->get('products_per_page');

        $product_qb->add_constraint('productGroup', '=', $this->_request_data['parent_group']);

        // This should be a helper function, same functionality, but with different config-parameter is used in /handler/product/search.php
        foreach ($this->_config->get('products_listing_order') as $ordering)
        {
            $this->_add_ordering($product_qb, $ordering);
        }

        if ($this->_config->get('enable_scheduling'))
        {
            /*
             * List products that either have no defined end-of-market dates
             * or are still in market
             */
            $product_qb->add_constraint('start', '<=', time());
            $product_qb->begin_group('OR');
                $product_qb->add_constraint('end', '=', 0);
                $product_qb->add_constraint('end', '>=', time());
            $product_qb->end_group();
        }

        $this->_request_data['products'] = $product_qb->execute();
        $this->_request_data['products_qb'] =& $product_qb;
    }

    private function _populate_toolbar()
    {
        // Populate toolbar
        if ($this->_request_data['group'])
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "edit/{$this->_request_data['group']->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_request_data['group']->can_do('midgard:update'),
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );
        }

        if ($this->_request_data['group'])
        {
            $allow_create_group = $this->_request_data['group']->can_do('midgard:create');
            $allow_create_product = $this->_request_data['group']->can_do('midgard:create');
        }
        else
        {
            $allow_create_group = midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_products_product_group_dba');
            $allow_create_product = midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_products_product_dba');
        }

        foreach (array_keys($this->_request_data['schemadb_group']) as $name)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "create/{$this->_request_data['parent_group']}/{$name}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf
                    (
                        $this->_l10n_midcom->get('create %s'),
                        $this->_l10n->get($this->_request_data['schemadb_group'][$name]->description)
                    ),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
                    MIDCOM_TOOLBAR_ENABLED => $allow_create_group,
                )
            );
        }

        foreach (array_keys($this->_request_data['schemadb_product']) as $name)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "product/create/{$this->_request_data['parent_group']}/{$name}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf
                    (
                        $this->_l10n_midcom->get('create %s'),
                        $this->_l10n->get($this->_request_data['schemadb_product'][$name]->description)
                    ),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                    MIDCOM_TOOLBAR_ENABLED => $allow_create_product,
                )
            );
        }
    }

    private function _add_ordering(&$qb, $ordering)
    {
        if (preg_match('/\s*reversed?\s*/', $ordering))
        {
            $reversed = true;
            $ordering = preg_replace('/\s*reversed?\s*/', '', $ordering);
        }
        else
        {
            $reversed = false;
        }

        if ($reversed)
        {
            $qb->add_order($ordering, 'DESC');
        }
        else
        {
            $qb->add_order($ordering);
        }
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_groupsblock($handler_id, array &$data)
    {
        if ($data['group'])
        {
            if ($data['controller'])
            {
                $data['view_group'] = $data['controller']->get_content_html();
            }
            else
            {
                $data['view_group'] = $data['datamanager_group']->get_content_html();
            }
        }

        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);


        if (count($data['groups']) > 0)
        {
            $groups_counter = 0;
            $data['groups_count'] = count($data['groups']);

            midcom_show_style('groupsblock_subgroups_header');

            foreach ($data['groups'] as $group)
            {
                $groups_counter++;
                $data['groups_counter'] = $groups_counter;

                $data['group'] = $group;
                if (! $data['datamanager_group']->autoset_storage($group))
                {
                    debug_add("The datamanager for group #{$group->id} could not be initialized, skipping it.");
                    debug_print_r('Object was:', $group);
                    continue;
                }
                $data['view_group'] = $data['datamanager_group']->get_content_html();

                if ($group->code)
                {
                    if (isset($data["parent_category"]))
                    {
                        $data['view_group_url'] = "{$prefix}" . $data["parent_category"] . "/{$group->code}/";
                    }
                    else
                    {
                        $data['view_group_url'] = "{$prefix}{$group->code}/";
                    }
                }
                else
                {
                    $data['view_group_url'] = "{$prefix}{$group->guid}/";
                }

                midcom_show_style('group_subgroups_item');
            }

            midcom_show_style('groupsblock_subgroups_footer');
        }
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     */
    private function _update_breadcrumb_line()
    {
        $tmp = Array();

        $group = $this->_request_data['group'];
        $root_group = $this->_config->get('root_group');

        if (!$group)
        {
            return false;
        }

        $parent = $group;

        while ($parent)
        {
            $group = $parent;

            if ($group->guid === $root_group)
            {
                break;
            }

            if ($group->code)
            {
                $url = "{$group->code}";
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

        // If navigation is configured to display product groups, remove the lowest level
        // parent to prevent duplicate entries in breadcrumb display
        if (   $this->_config->get('display_navigation')
            && isset($tmp[count($tmp) - 1]))
        {
            unset($tmp[count($tmp) - 1]);
        }

        $reversed = array_reverse($tmp);
        midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $reversed);
    }
}
?>
