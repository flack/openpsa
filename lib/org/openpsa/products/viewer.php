<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the class that defines which URLs should be handled by this module.
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_viewer extends midcom_baseclasses_components_request
{
    /**
     * Initialize the request switch and the content topic.
     *
     * @access protected
     */
    public function _on_initialize()
    {
        if ($this->_config->get('search_first'))
        {
            $this->_request_switch['index'] = Array
            (
                'handler' => Array('org_openpsa_products_handler_product_search', 'search_redirect'),
            );
        }
    }

    /**
     * Generate markdown documentation for API docs based on schema
     *
     * @return string documentation
     */
    function help_schemafields2postargs()
    {
        $schema_string = '';
        foreach ($this->_request_data['schemadb_product'] as $schema)
        {
            foreach ($schema->fields as $fieldname => $field_setup)
            {
                if ($field_setup['readonly'])
                {
                    continue;
                }

                if ($fieldname == 'productGroup')
                {
                    $fieldname = 'productgroup';
                    $field_setup['required'] = true;
                }
                $schema_string .= "\n_{$field_setup['type']}_ `{$fieldname}`";
                if ($field_setup['required'])
                {
                    $schema_string .= " __*__";
                }

                $schema_string .= "\n";
                $schema_string .= ":    {$field_setup['title']}.";

                if (   $field_setup['type'] == 'select'
                    && isset($field_setup['type_config']['options']))
                {
                    $schema_string .= " Options:<br />\n";
                    foreach ($field_setup['type_config']['options'] as $key => $value)
                    {
                        $schema_string .= "- **{$key}**: {$value}<br />\n";
                    }
                }

                $schema_string .= "\n";
            }
        }
        return $schema_string;
    }

    /**
     * Indexes a product
     *
     * This function is usually called statically from various handlers.
     *
     * @param midcom_helper_datamanager2_datamanager &$dm The Datamanager encapsulating the event.
     * @param midcom_services_indexer &$indexer The indexer instance to use.
     * @param midcom_db_topic The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    function index(&$dm, &$indexer, $topic, $config = null)
    {
        if ($config == null)
        {
            $config = $this->_config;
        }
        $object =& $dm->storage->object;
        if (!is_object($topic))
        {
            $tmp = new midcom_db_topic($topic);
            if (! $tmp->guid)
            {
                throw new midcom_error("Failed to load the topic referenced by {$topic} for indexing.");
            }
            $topic = $tmp;
        }

        // Don't index directly, that would loose a reference due to limitations
        // of the index() method. Needs fixes there.

        $document = $indexer->new_document($dm);
        if ($_MIDCOM->dbfactory->is_a($object, 'org_openpsa_products_product_dba'))
        {
            if ($config->get('enable_scheduling'))
            {
                // Check start/end for products
                if (   (   $object->start != 0
                        && $object->start > time())
                    || (   $object->end != 0
                        && $object->end < time())
                    )
                {
                    // Not in market, remove from index
                    $indexer->delete($document->RI);
                    return;
                }
                // FIXME: add midcom at job or somesuch to reindex products after their end time (and start time if in the future)
            }
        }

        $document->topic_guid = $topic->guid;
        $document->component = $topic->component;
        $nav = new midcom_helper_nav();
        $node = $nav->get_node($topic->id);
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($object);
        $document->content = "{$dm->schema->name} {$dm->schema->description} {$document->content}";
        $indexer->index($document);
    }

    /**
     * Populates the node toolbar depending on the user's rights.
     *
     * @access protected
     */
    private function _populate_node_toolbar()
    {
        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midgard:create'))
        {
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'api/product/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('export products'),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n->get('export products'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/editshred.png',
                )
            );
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'import/product/csv/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('import products'),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n->get('import products from csv-file'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/editshred.png',
                )
            );
        }
        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config'))
        {
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'config/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
                )
            );
        }
    }

    /**
     * The handle callback populates root group information
     */
    public function _on_can_handle($argc, $argv)
    {
        if ($this->_config->get('root_group') === 0)
        {
            $this->_request_data['root_group'] = 0;
        }
        else
        {
            $root_group = org_openpsa_products_product_group_dba::get_cached($this->_config->get('root_group'));
            if (!$root_group)
            {
                return false;
            }
            $this->_request_data['root_group'] = $root_group->id;
        }

        if ($argc >= 1)
        {
            $mc = midcom_db_topic::new_collector('up', $this->_topic->id);
            $mc->add_constraint('name', '=', $argv[0]);
            $mc->execute();
            $keys = $mc->list_keys();
            if (count($keys) > 0)
            {
                // the values are dummy...
                return false;
            }
        }

        return true;
    }

    /**
     * The handle callback populates the toolbars.
     */
    public function _on_handle($handler, $args)
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $this->_request_data['schemadb_group'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
        $this->_request_data['schemadb_product'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_product'));

        if ($this->_config->get('enable_productlinks'))
        {
            $this->_request_data['schemadb_productlink'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_productlink'));
        }

        $this->_populate_node_toolbar();

        if ($this->_config->get('custom_rss_feeds'))
        {
            $feeds = $this->_config->get('custom_rss_feeds');
            if (   $feeds !== false
                && count($feeds) > 0)
            {
                foreach ($feeds as $title => $url)
                {
                    $_MIDCOM->add_link_head
                    (
                        array
                        (
                            'rel'   => 'alternate',
                            'type'  => 'application/rss+xml',
                            'title' => $this->_l10n->get($title),
                            'href'  => $url,
                        )
                    );
                }
            }
        }
        else
        {
            $_MIDCOM->add_link_head
            (
                array
                (
                    'rel'   => 'alternate',
                    'type'  => 'application/rss+xml',
                    'title' => $this->_l10n->get('updated products'),
                    'href'  => $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . 'rss.xml',
                )
            );
        }
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param org_openpsa_products_product $object
     */
    function update_breadcrumb_line($object, $product_group = null)
    {
        $tmp = Array();

        if (get_class($object) == 'org_openpsa_products_product_link_dba')
        {
            if ($object->product != 0)
            {
                $product = new org_openpsa_products_product_dba($object->product);
            }
            if (isset($product))
            {
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => "{$object->guid}/",
                    MIDCOM_NAV_NAME => $product->title,
                );
            }
        }
        else if (get_class($object) != 'org_openpsa_products_product_dba')
        {
            $tmp[] = array
            (
                MIDCOM_NAV_URL => "{$object->code}/",
                MIDCOM_NAV_NAME => $object->title,
            );
        }

        while ($object)
        {
            if (   get_class($object) == 'org_openpsa_products_product_dba'
                && $product_group != null)
            {
                $parent = $product_group;
            }
            else
            {
                $parent = $object->get_parent();
            }

            if (get_class($object) == 'org_openpsa_products_product_dba')
            {
                if (isset($object->productGroup))
                {
                    $group = new org_openpsa_products_product_group_dba($object->productGroup);
                    if ($group->up !== 0)
                    {
                        $parent_group = new org_openpsa_products_product_group_dba($group->up);
                        $tmp[] = array
                        (
                            MIDCOM_NAV_URL => "product/{$parent_group->code}/{$object->code}/",
                            MIDCOM_NAV_NAME => $object->title,
                        );
                    }
                }
                else
                {
                    $tmp[] = array
                    (
                        MIDCOM_NAV_URL => "product/{$object->code}/",
                        MIDCOM_NAV_NAME => $object->title,
                    );
                }
            }
            elseif (get_class($object) != 'org_openpsa_products_product_link_dba')
            {
                if (isset($object->up))
                {
                    $parentgroup_qb = org_openpsa_products_product_group_dba::new_query_builder();
                    $parentgroup_qb->add_constraint('id', '=', $object->up);
                    $group = $parentgroup_qb->execute();
                    if (count($group) > 0)
                    {
                        $tmp[] = array
                        (
                            MIDCOM_NAV_URL => "{$group[0]->code}/{$object->code}/",
                            MIDCOM_NAV_NAME => $object->title,
                        );
                    }
                }
                else if ($parent != null)
                {
                    $tmp[] = array
                    (
                        MIDCOM_NAV_URL => "{$parent->code}/{$object->code}/",
                        MIDCOM_NAV_NAME => $object->title,
                    );
                }
                else
                {
                    $tmp[] = array
                    (
                        MIDCOM_NAV_URL => "{$object->code}/",
                        MIDCOM_NAV_NAME => $object->title,
                    );
                }
            }
            $object = $parent;
        }
        $tmp = array_reverse($tmp);
        return $tmp;
    }
}
?>