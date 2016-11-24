<?php
/**
 * Created on 2006-08-09
 * @author Henri Bergius
 * @package org.openpsa.products
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_group_list  extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param array &$data The local request data.
     */
    public function _handler_index($handler_id, array $args, array &$data)
    {
        $data['parent_group'] = $data['root_group'];

        $group_qb = org_openpsa_products_product_group_dba::new_query_builder();
        $group_qb->add_constraint('up', '=', $data['parent_group']);

        foreach ($this->_config->get('groups_listing_order') as $ordering) {
            $this->_add_ordering($group_qb, $ordering);
        }

        $data['groups'] = $group_qb->execute();
        $data['products'] = array();
        if ($this->_config->get('group_list_products')) {
            $this->_list_group_products();
        }

        $data['datamanager_group'] = new midcom_helper_datamanager2_datamanager($data['schemadb_group']);

        $this->_populate_toolbar();
        $data['view_title'] = $this->_l10n->get('product database');
        midcom::get()->head->set_pagetitle($data['view_title']);
        org_openpsa_widgets_grid::add_head_elements();
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_index($handler_id, array &$data)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

        if (   count($data['groups']) >= 1
            && (   count($data['products']) == 0
                || $this->_config->get('listing_primary') == 'groups')) {
            if ($this->_config->get('disable_subgroups_on_frontpage') !== true) {
                midcom_show_style('group_header');

                $data['groups_count'] = count($data['groups']);

                midcom_show_style('group_subgroups_header');
                foreach ($data['groups'] as $group) {
                    $data['group'] = $group;
                    if (!$data['datamanager_group']->autoset_storage($group)) {
                        debug_add("The datamanager for group #{$group->id} could not be initialized, skipping it.");
                        debug_print_r('Object was:', $group);
                        continue;
                    }
                    $data['view_group'] = $data['datamanager_group']->get_content_html();
                    $data['view_group_url'] = $prefix . $group->guid . '/';

                    midcom_show_style('group_subgroups_item');
                }

                midcom_show_style('group_subgroups_footer');
                midcom_show_style('group_footer');
            }
        } elseif (count($data['products']) > 0) {
            $data['datamanager_product'] = new midcom_helper_datamanager2_datamanager($data['schemadb_product']);
            midcom_show_style('group_header');
            midcom_show_style('group_products_grid');
            midcom_show_style('group_products_footer');
            midcom_show_style('group_footer');
        } else {
            midcom_show_style('group_empty');
        }
    }

    /**
     * The handler for the group_list article.
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $data['group'] = new org_openpsa_products_product_group_dba($args[0]);
        $data['parent_group'] = $data['group']->id;

        $group_qb = org_openpsa_products_product_group_dba::new_query_builder();
        $group_qb->add_constraint('up', '=', $data['parent_group']);

        foreach ($this->_config->get('groups_listing_order') as $ordering) {
            $this->_add_ordering($group_qb, $ordering);
        }

        $data['groups'] = $group_qb->execute();
        $data['products'] = array();
        if ($this->_config->get('group_list_products')) {
            $this->_list_group_products();
        }

        $data['datamanager_group'] = new midcom_helper_datamanager2_datamanager($data['schemadb_group']);

        $this->_populate_toolbar();

        if (midcom::get()->config->get('enable_ajax_editing')) {
            $data['controller'] = midcom_helper_datamanager2_controller::create('ajax');
            $data['controller']->schemadb =& $data['schemadb_group'];
            $data['controller']->set_storage($data['group']);
            $data['controller']->process_ajax();
            $data['datamanager_group'] = $data['controller']->datamanager;
        } else {
            $data['controller'] = null;
            if (!$data['datamanager_group']->autoset_storage($data['group'])) {
                throw new midcom_error("Failed to create a DM2 instance for product group {$data['group']->guid}.");
            }
        }
        $this->bind_view_to_object($data['group'], $data['datamanager_group']->schema->name);

        // Set the active leaf
        if ($this->_config->get('display_navigation')) {
            $group = $data['group'];

            // Loop until root group
            while (   $group->id !== $this->_config->get('root_group')
                   && $group->guid !== $this->_config->get('root_group')) {
                if ($group->up == 0) {
                    // Active leaf of the topic
                    $this->set_active_leaf($group->id);
                    break;
                }
                $group = new org_openpsa_products_product_group_dba($group->up);
            }
        }

        $this->_update_breadcrumb_line();

        $data['view_title'] = $data['group']->title;
        if ($this->_config->get('code_in_title')) {
            $data['view_title'] = $data['group']->code . ' ' . $data['view_title'];
        }

        midcom::get()->head->set_pagetitle($data['view_title']);
        org_openpsa_widgets_grid::add_head_elements();
    }

    private function _add_ordering($qb, $ordering)
    {
        if (preg_match('/\s*reversed?\s*/', $ordering)) {
            $ordering = preg_replace('/\s*reversed?\s*/', '', $ordering);
            $qb->add_order($ordering, 'DESC');
        } else {
            $qb->add_order($ordering);
        }
    }

    private function _populate_toolbar()
    {
        if (!empty($this->_request_data['group'])) {
            $workflow = $this->get_workflow('datamanager2');
            $this->_view_toolbar->add_item($workflow->get_button("edit/{$this->_request_data['group']->guid}/", array(
                MIDCOM_TOOLBAR_ENABLED => $this->_request_data['group']->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )));
            $allow_create_group = $this->_request_data['group']->can_do('midgard:create');
            $allow_create_product = $this->_request_data['group']->can_do('midgard:create');

            if ($this->_request_data['group']->orgOpenpsaObtype == org_openpsa_products_product_group_dba::TYPE_SMART) {
                $allow_create_product = false;
            }
        } else {
            $allow_create_group = midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_products_product_group_dba');
            $allow_create_product = midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_products_product_dba');
        }

        $this->_add_schema_buttons('schemadb_group', 'new-dir', '', $allow_create_group);
        $this->_add_schema_buttons('schemadb_product', 'new-text', 'product/', $allow_create_product);
    }

    private function _add_schema_buttons($schemadb_name, $default_icon, $prefix, $allowed)
    {
        $workflow = $this->get_workflow('datamanager2');
        foreach (array_keys($this->_request_data[$schemadb_name]) as $name) {
            $config = array(
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/' . $default_icon . '.png',
                MIDCOM_TOOLBAR_ENABLED => $allowed,
                MIDCOM_TOOLBAR_LABEL => sprintf(
                    $this->_l10n_midcom->get('create %s'),
                    $this->_l10n->get($this->_request_data[$schemadb_name][$name]->description)
                ),
            );
            if (isset($this->_request_data[$schemadb_name][$name]->customdata['icon'])) {
                $config[MIDCOM_TOOLBAR_ICON] = $this->_request_data[$schemadb_name][$name]->customdata['icon'];
            }
            $create_url = 'create/' . $this->_request_data['parent_group'] . '/' . $name . '/';
            $this->_view_toolbar->add_item($workflow->get_button($prefix . $create_url, $config));
        }
    }

    private function _list_group_products()
    {
        $product_qb = org_openpsa_products_product_dba::new_query_builder();

        if (   !empty($this->_request_data['group'])
            && $this->_request_data['group']->orgOpenpsaObtype == org_openpsa_products_product_group_dba::TYPE_SMART) {
            // Smart group, query products by stored constraints
            $constraints = $this->_request_data['group']->list_parameters('org.openpsa.products:constraints');
            if (empty($constraints)) {
                $product_qb->add_constraint('productGroup', '=', $this->_request_data['parent_group']);
            }

            $reflector = new midgard_reflection_property('org_openpsa_products_product');

            foreach ($constraints as $constraint_string) {
                $constraint_members = explode(',', $constraint_string);
                if (count($constraint_members) != 3) {
                    throw new midcom_error("Invalid constraint '{$constraint_string}'");
                }

                // Reflection is needed here for safety
                $field_type = $reflector->get_midgard_type($constraint_members[0]);
                switch ($field_type) {
                    case 4:
                        throw new midcom_error("Invalid constraint: '{$constraint_members[0]}' is not a Midgard property");
                    case MGD_TYPE_INT:
                        $constraint_members[2] = (int) $constraint_members[2];
                        break;
                    case MGD_TYPE_FLOAT:
                        $constraint_members[2] = (float) $constraint_members[2];
                        break;
                    case MGD_TYPE_BOOLEAN:
                        $constraint_members[2] = (boolean) $constraint_members[2];
                        break;
                }
                $product_qb->add_constraint($constraint_members[0], $constraint_members[1], $constraint_members[2]);
            }
        } else {
            $product_qb->add_constraint('productGroup', '=', $this->_request_data['parent_group']);
        }

        // This should be a helper function, same functionality, but with different config-parameter is used in /handler/product/search.php
        foreach ($this->_config->get('products_listing_order') as $ordering) {
            $this->_add_ordering($product_qb, $ordering);
        }

        if ($this->_config->get('enable_scheduling')) {
            $product_qb->add_constraint('start', '<=', time());
            $product_qb->begin_group('OR');
            /*
             * List products that either have no defined end-of-market dates
             * or are still in market
             */
            $product_qb->add_constraint('end', '=', 0);
            $product_qb->add_constraint('end', '>=', time());
            $product_qb->end_group();
        }

        $this->_request_data['products'] = $product_qb->execute();
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        if ($data['controller']) {
            $data['view_group'] = $data['controller']->get_content_html();
        } else {
            $data['view_group'] = $data['datamanager_group']->get_content_html();
        }

        $this->_show_index($handler_id, $data);
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     */
    private function _update_breadcrumb_line()
    {
        $tmp = $this->_master->update_breadcrumb_line($this->_request_data['group']);

        // If navigation is configured to display product groups, remove the lowest level
        // parent to prevent duplicate entries in breadcrumb display
        if ($this->_config->get('display_navigation')) {
            array_shift($tmp);
        }

        midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $tmp);
    }
}
