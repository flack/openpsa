<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Product search class
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_product_search extends midcom_baseclasses_components_handler
{
    /**
     * Redirector moving user to the search form of first schema
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_search_redirect($handler_id, array $args, array &$data)
    {
        return new midcom_response_relocate('search/' . key($data['schemadb_product']) . '/');
    }

    private function _validate_operator($operator)
    {
        switch ($operator)
        {
            case '<':
            case 'lt':
            case '<=':
            case 'lte':
            case '=':
            case 'eq':
            case '<>':
            case '!eq':
            case '>=':
            case 'gte':
            case '>':
            case 'gt':
            case 'LIKE':
            case 'NOT LIKE':
            case 'INTREE':
                return true;
            default:
                return false;
        }
    }

    private function _normalize_operator($operator)
    {
        switch ($operator)
        {
            case 'lt':
                return '<';
            case 'gt':
                return '>';
            case 'lte':
                return '<=';
            case 'gte':
                return '>=';
            case '!eq':
                return '<>';
            case 'eq':
                return '=';
            default:
                return $operator;
        }
    }

    /**
     * Check each search constraint for validity and normalize
     */
    private function _normalize_search($constraints)
    {
        $normalized_parameters = array();

        foreach ($constraints as $key => $constraint)
        {
            if (!array_key_exists('property', $constraint))
            {
                // No field defined for this parameter, skip
                continue;
            }

            if (strstr(',', $constraint['property']))
            {
                $properties = explode(',', $constraint['property']);
                unset($constraints[$key]);
                foreach ($properties as $property)
                {
                    $constraints[] = array
                    (
                        'property'   => $property,
                        'constraint' => $constraint['constraint'],
                        'value'      => $constraint['value'],
                        'group'      => 'OR',
                    );
                }
            }
        }

        foreach ($constraints as $constraint)
        {
            if (!array_key_exists('property', $constraint))
            {
                // No field defined for this parameter, skip
                continue;
            }

            if (!array_key_exists($constraint['property'], $this->_request_data['schemadb_product'][$this->_request_data['search_schema']]->fields))
            {
                // This field is not in the schema
                // TODO: Raise error?
                continue;
            }

            if (!array_key_exists('constraint', $constraint))
            {
                $constraint['constraint'] = '=';
            }

            // Validate available constraints
            if (!$this->_validate_operator($constraint['constraint']))
            {
                continue;
            }

            $constraint['constraint'] = $this->_normalize_operator($constraint['constraint']);

            if (   !array_key_exists('value', $constraint)
                || $constraint['value'] == '')
            {
                // No value specified for this constraint, skip
                continue;
            }

            if ($constraint['constraint'] == 'LIKE')
            {
                $constraint['value'] = str_replace('*', '%', $constraint['value']);

                if (!strstr($constraint['value'], '%'))
                {
                    // Append a wildcard
                    $constraint['value'] = '%' . $constraint['value'] . '%';
                }
                // Replace multiple consecutive wildcards with single one.
                $constraint['value'] = preg_replace('/%+/', '%', $constraint['value']);
            }

            // TODO: Handle typecasting of values to prevent QB errors

            $normalized_parameters[] = $constraint;
        }

        return $normalized_parameters;
    }

    /**
     * Search products using Midgard 1.8+ Query Builder
     */
    private function _qb_list_all()
    {
        $qb = new org_openpsa_qbpager('org_openpsa_products_product_dba', 'org_openpsa_products_product_dba');
        $qb->results_per_page = $this->_config->get('products_per_page');

        $return_products = array();

        // Check that the object has correct schema
        $qb->begin_group('AND');
        $qb->add_constraint('parameter.domain', '=', 'midcom.helper.datamanager2');
        $qb->add_constraint('parameter.name', '=', 'schema_name');
        $qb->add_constraint('parameter.value', '=', $this->_request_data['search_schema']);
        $qb->end_group();

        foreach ($this->_config->get('search_index_order') as $ordering)
        {
            $this->_add_ordering($qb, $ordering);
        }

        $products = $qb->execute();

        // FIXME: hack to prevent duplication of results
        foreach ($products as $product)
        {
            $return_products[$product->guid] = $product;
        }

        return $return_products;
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
     * Search products using Query Builder
     */
    private function _qb_search($constraints)
    {
        $qb = new org_openpsa_qbpager('org_openpsa_products_product_dba', 'org_openpsa_products_product_dba');
        $qb->results_per_page = $this->_config->get('products_per_page');

        // Check that the object has correct schema
        $mc = new midgard_collector('midgard_parameter', 'domain', 'midcom.helper.datamanager2');
        $mc->set_key_property('parentguid');
        $mc->add_constraint('name', '=', 'schema_name');
        $mc->add_constraint('value', '=', $this->_request_data['search_schema']);
        $mc->execute();
        $keys = $mc->list_keys();
        if (!empty($keys))
        {
            $qb->add_constraint('guid', 'IN', array_keys($keys));
        }
        unset($mc, $keys);

        if ($this->_request_data['search_type'] == 'OR')
        {
            $qb->begin_group('OR');
        }
        foreach ($constraints as $constraint)
        {
            $storage = $this->_request_data['schemadb_product'][$this->_request_data['search_schema']]->fields[$constraint['property']]['storage'];
            debug_print_r('constraint', $constraint);
            debug_print_r('storage', $storage);
            if (   !is_array($storage)
                // Do not add constraint if it's all wildcards
                || preg_match('/^%+$/', $constraint['value']))
            {
                continue;
            }
            if (   $storage['location'] == 'parameter'
                || $storage['location'] == 'configuration')
            {
                $mc = new midgard_collector('midgard_parameter', 'domain', $storage['domain']);
                $mc->set_key_property('parentguid');
                $mc->add_constraint('name', '=', $constraint['property']);
                $mc->add_constraint('value', $constraint['constraint'], $constraint['value']);
                $mc->execute();
                $keys = $mc->list_keys();
                if (!empty($keys))
                {
                    $qb->add_constraint('guid', 'IN', array_keys($keys));
                }
                unset($mc, $keys);
            }
            else
            {
                // Simple field storage
                if (is_numeric($constraint['value']))
                {
                    // TODO: When 1.8.4 becomes more common we can reflect this instead
                    $constraint['value'] = (int) $constraint['value'];
                }
                $qb->add_constraint($storage['location'], $constraint['constraint'], $constraint['value']);
            }
        }
        if ($this->_request_data['search_type'] == 'OR')
        {
            $qb->end_group();
        }

        foreach ($this->_config->get('search_index_order') as $ordering)
        {
            $this->_add_ordering($qb, $ordering);
        }

        $ret = $qb->execute();
        /* FIXME: It this the right way to do this? */
        $this->_request_data['search_qb'] =& $qb;

        return $ret;
    }

    /**
     * Looks up a product to display.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_search($handler_id, array $args, array &$data)
    {
        $data['search_schema'] = $args[0];
        if (!array_key_exists($data['search_schema'], $data['schemadb_product']))
        {
            throw new midcom_error_notfound('Invalid search schema');
        }

        if ($handler_id == 'view_search_raw')
        {
            midcom::get()->skip_page_style = true;
        }

        $data['results'] = array();

        // Determine search type (AND vs OR)
        switch (true)
        {
            case (!array_key_exists('org_openpsa_products_search_type', $_REQUEST)):
            default:
                $data['search_type'] = 'AND';
                break;
            case ($_REQUEST['org_openpsa_products_search_type'] == 'OR'):
                $data['search_type'] = 'OR';
                break;
        }

        if (   array_key_exists('org_openpsa_products_search', $_REQUEST)
            && is_array($_REQUEST['org_openpsa_products_search']))
        {
            // Normalize the constraints
            $data['search_constraints'] = $this->_normalize_search($_REQUEST['org_openpsa_products_search']);

            if (count($data['search_constraints']) > 0)
            {
                // Process search
                $data['results'] = $this->_qb_search($data['search_constraints']);
            }
        }
        else if (array_key_exists('org_openpsa_products_list_all', $_REQUEST))
        {
            // Process search
            $data['results'] = $this->_qb_list_all();
        }
        else
        {
            // No search has yet been made
            if ($this->_config->get('search_default_to_all'))
            {
                // Process search
                $data['results'] = $this->_qb_list_all();
            }
        }

        // Prepare datamanager
        $data['datamanager'] = new midcom_helper_datamanager2_datamanager($data['schemadb_product']);

        $this->add_stylesheet(MIDCOM_STATIC_URL."/midcom.helper.datamanager2/legacy.css");

        $this->_populate_toolbar();

        $data['view_title'] = $this->_l10n->get('search') . ': ' . $this->_l10n->get($data['schemadb_product'][$data['search_schema']]->description);

        midcom::get('head')->set_pagetitle($data['view_title']);
    }

    private function _populate_toolbar()
    {
        if ($this->_topic->can_do('midgard:create'))
        {
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "create/{$this->_request_data['root_group']}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf
                    (
                        $this->_l10n_midcom->get('create %s'),
                        $this->_l10n->get('product group')
                    ),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
                )
            );

            foreach (array_keys($this->_request_data['schemadb_product']) as $name)
            {
                $this->_node_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "product/create/{$this->_request_data['root_group']}/{$name}/",
                        MIDCOM_TOOLBAR_LABEL => sprintf
                        (
                            $this->_l10n_midcom->get('create %s'),
                            $this->_l10n->get($this->_request_data['schemadb_product'][$name]->description)
                        ),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
                    )
                );
            }
        }

        $this->bind_view_to_object($this->_topic, $this->_request_data['search_schema']);
    }

    /**
     * Shows the loaded product.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_search($handler_id, array &$data)
    {
        midcom_show_style('product_search_header');
        midcom_show_style('product_search_form');

        if (count($data['results']) == 0)
        {
            midcom_show_style('product_search_noresults');
        }
        else
        {
            $data['results_count'] = count($data['results']);
            $results_counter = 0;

            midcom_show_style('product_search_result_header');
            foreach ($data['results'] as $result)
            {
                $results_counter++;
                $data['results_counter'] = $results_counter;

                if (! $data['datamanager']->autoset_storage($result))
                {
                    debug_add("The datamanager for product {$result->id} could not be initialized, skipping it.");
                    debug_print_r('Object was:', $result);
                    continue;
                }

                $data['product'] = $result;
                midcom_show_style('product_search_result_item');
            }
            midcom_show_style('product_search_result_footer');
        }

        midcom_show_style('product_search_footer');
    }

    /**
     * Static helper for finding queried search values
     */
    function get_queried_value($property, $constraint = null)
    {
        if (   !array_key_exists('org_openpsa_products_search', $_REQUEST)
            || !is_array($_REQUEST['org_openpsa_products_search']))
        {
            // No search was made
            return null;
        }

        if (   !array_key_exists($property, $_REQUEST['org_openpsa_products_search'])
            || !is_array($_REQUEST['org_openpsa_products_search'][$property]))
        {
            // This property wasn't specified
            return null;
        }

        if (!array_key_exists('value', $_REQUEST['org_openpsa_products_search'][$property]))
        {
            return null;
        }

        if (   !is_null($constraint)
            && $_REQUEST['org_openpsa_products_search'][$property]['constraint'] != $constraint)
        {
            return null;
        }

        return $_REQUEST['org_openpsa_products_search'][$property]['value'];
    }
}
?>