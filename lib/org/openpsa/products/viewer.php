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
     * Indexes a product
     *
     * @param midcom_helper_datamanager2_datamanager $dm The Datamanager encapsulating the event.
     * @param midcom_services_indexer $indexer The indexer instance to use.
     * @param midcom_db_topic|midcom_core_dbaproxy The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    public static function index($dm, $indexer, $topic, $config = null)
    {
        if ($config == null) {
            $config = midcom_baseclasses_components_configuration::get('org.openpsa.products', 'config');
        }
        $object = $dm->storage->object;

        // Don't index directly, that would lose a reference due to limitations
        // of the index() method. Needs fixes there.

        $document = $indexer->new_document($dm);
        if (   $config->get('enable_scheduling')
            && midcom::get()->dbfactory->is_a($object, 'org_openpsa_products_product_dba')) {
            // Check start/end for products
            if (   $object->start > time()
                || (   $object->end != 0
                    && $object->end < time())) {
                // Not in market, remove from index
                $indexer->delete($document->RI);
                return;
            }
            // FIXME: add midcom at job or somesuch to reindex products after their end time (and start time if in the future)
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
     */
    private function _populate_node_toolbar()
    {
        $buttons = array();
        if ($this->_topic->can_do('midgard:update')) {
            if ($this->_topic->can_do('midgard:create')) {
                $buttons[] = array(
                    MIDCOM_TOOLBAR_URL => 'export/product/csv/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('export products'),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n->get('export products'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/editshred.png',
                );
                $buttons[] = array(
                    MIDCOM_TOOLBAR_URL => 'import/product/csv/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('import products'),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n->get('import products from csv-file'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/editshred.png',
                );
            }
            if ($this->_topic->can_do('midcom:component_config')) {
                $workflow = $this->get_workflow('datamanager2');
                $buttons[] = $workflow->get_button('config/', array(
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
                ));
            }
        }
        $this->_node_toolbar->add_items($buttons);
    }

    /**
     * The handle callback populates root group information
     */
    public function _on_can_handle($argc, array $argv)
    {
        if ($this->_config->get('root_group') === 0) {
            $this->_request_data['root_group'] = 0;
        } else {
            $root_group = org_openpsa_products_product_group_dba::get_cached($this->_config->get('root_group'));
            $this->_request_data['root_group'] = $root_group->id;
        }

        if ($argc >= 1) {
            $mc = midcom_db_topic::new_collector('up', $this->_topic->id);
            $mc->add_constraint('name', '=', $argv[0]);
            $mc->execute();
            $keys = $mc->list_keys();
            if (count($keys) > 0) {
                // the values are dummy...
                return false;
            }
        }

        return true;
    }

    /**
     * The handle callback populates the toolbars.
     */
    public function _on_handle($handler, array $args)
    {
        $this->_request_data['schemadb_group'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
        $this->_request_data['schemadb_product'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_product'));

        $this->_populate_node_toolbar();

        if ($this->_config->get('custom_rss_feeds')) {
            $feeds = $this->_config->get('custom_rss_feeds');
            if (!empty($feeds)) {
                foreach ($feeds as $title => $url) {
                    midcom::get()->head->add_link_head(
                        array(
                            'rel'   => 'alternate',
                            'type'  => 'application/rss+xml',
                            'title' => $this->_l10n->get($title),
                            'href'  => $url,
                        )
                    );
                }
            }
        } else {
            midcom::get()->head->add_link_head(
                array(
                    'rel'   => 'alternate',
                    'type'  => 'application/rss+xml',
                    'title' => $this->_l10n->get('updated products'),
                    'href'  => midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . 'rss.xml',
                )
            );
        }
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     *
     * @param midcom_core_dbaobject $object
     */
    public function update_breadcrumb_line($object)
    {
        $tmp = array();
        $root_group = $this->_config->get('root_group');

        while ($object) {
            $parent = $object->get_parent();

            if ($object instanceof org_openpsa_products_product_dba) {
                $tmp[] = array(
                    MIDCOM_NAV_URL => "product/{$object->guid}/",
                    MIDCOM_NAV_NAME => $object->title,
                );
            } else {
                if ($object->guid === $root_group) {
                    break;
                }

                $tmp[] = array(
                    MIDCOM_NAV_URL => $object->guid . '/',
                    MIDCOM_NAV_NAME => $object->title,
                );
            }
            $object = $parent;
        }
        return array_reverse($tmp);
    }

    public static function get_unit_options()
    {
        $unit_options = midcom_baseclasses_components_configuration::get('org.openpsa.products', 'config')->get('unit_options');
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.products');
        $options = array();
        foreach ($unit_options as $key => $name) {
            $options[$key] = $l10n->get($name);
        }
        return $options;
    }

    public static function get_unit_option($unit)
    {
        $unit_options = self::get_unit_options();
        if (array_key_exists($unit, $unit_options)) {
            return $unit_options[$unit];
        }
        return '';
    }
}
