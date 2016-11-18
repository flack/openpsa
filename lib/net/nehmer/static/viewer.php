<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * n.n.static site interface class
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_viewer extends midcom_baseclasses_components_request
{
    /**
     * The topic in which to look for articles. This defaults to the current content topic
     * unless overridden by the symlink topic feature.
     *
     * @var midcom_db_topic
     */
    private $_content_topic = null;

    /**
     * Initialize the request switch and the content topic.
     */
    public function _on_initialize()
    {
        $this->_determine_content_topic();
        $this->_request_data['content_topic'] = $this->_content_topic;

        // View mode handler, set index viewer according to autoindex setting.
        // These, especially the general view handler, must come last, otherwise we'll hide other
        // handlers
        if ($this->_config->get('autoindex')) {
            $this->_request_switch['autoindex'] = array(
                'handler' => array('net_nehmer_static_handler_autoindex', 'autoindex'),
            );
        } else {
            $this->_request_switch['index'] = array(
                'handler' => array('net_nehmer_static_handler_view', 'view'),
            );
        }
    }

    /**
     * Set the content topic to use. This will check against the configuration setting 'symlink_topic'.
     */
    private function _determine_content_topic()
    {
        $guid = $this->_config->get('symlink_topic');
        if (is_null($guid)) {
            // No symlink topic
            // Workaround, we should talk to a DBA object automatically here in fact.
            $this->_content_topic = midcom_db_topic::get_cached($this->_topic->id);
            return;
        }

        $this->_content_topic = midcom_db_topic::get_cached($guid);
        // Validate topic.
        if ($this->_content_topic->component != 'net.nehmer.static') {
            debug_print_r('Retrieved topic was:', $this->_content_topic);
            throw new midcom_error('Symlink content topic is invalid, see the debug level log for details.');
        }
    }

    /**
     * Indexes an article.
     *
     * @param midcom_helper_datamanager2_datamanager $dm The Datamanager encapsulating the event.
     * @param midcom_services_indexer $indexer The indexer instance to use.
     * @param midcom_db_topic $topic The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    public static function index($dm, $indexer, midcom_db_topic $topic)
    {
        // Don't index directly, that would lose a reference due to limitations
        // of the index() method. Needs fixes there.

        $nav = new midcom_helper_nav();
        $node = $nav->get_node($topic->id);

        $document = $indexer->new_document($dm);
        $document->topic_guid = $topic->guid;
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->storage->object);
        $document->component = $topic->component;
        $indexer->index($document);
    }

    /**
     * Populates the node toolbar depending on the user's rights.
     */
    private function _populate_node_toolbar()
    {
        $buttons = array();
        $workflow = $this->get_workflow('datamanager2');
        if ($this->_content_topic->can_do('midgard:create')) {
            foreach (array_keys($this->_request_data['schemadb']) as $name) {
                $buttons[] = $workflow->get_button("create/{$name}/", array(
                    MIDCOM_TOOLBAR_LABEL => sprintf(
                        $this->_l10n_midcom->get('create %s'),
                        $this->_l10n->get($this->_request_data['schemadb'][$name]->description)
                    ),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                ));
            }
        }

        if (   $this->_config->get('enable_article_links')
            && $this->_content_topic->can_do('midgard:create')) {
            $buttons[] = $workflow->get_button("create/link/", array(
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('article link')),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/attach.png',
            ));
        }

        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config')) {
            $buttons[] = $workflow->get_button('config/', array(
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
            ));
        }
        $this->_node_toolbar->add_items($buttons);
    }

    /**
     * The handle callback populates the toolbars.
     */
    public function _on_handle($handler, array $args)
    {
        $this->_request_data['schemadb'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        $this->_populate_node_toolbar();
    }

    /**
     *
     * @param midcom_helper_configuration $config
     * @param integer $id The topic ID
     * @return midcom_core_querybuilder The querybuilder instance
     */
    public static function get_topic_qb(midcom_helper_configuration $config, $id)
    {
        $qb = midcom_db_article::new_query_builder();

        // Include the article links to the indexes if enabled
        if ($config->get('enable_article_links')) {
            $mc = net_nehmer_static_link_dba::new_collector('topic', $id);

            $qb->begin_group('OR');
            $qb->add_constraint('id', 'IN', $mc->get_values('article'));
            $qb->add_constraint('topic', '=', $id);
            $qb->end_group();
        } else {
            $qb->add_constraint('topic', '=', $id);
        }
        return $qb;
    }
}
