<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Blog site interface class
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_viewer extends midcom_baseclasses_components_request
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

        if ($this->_config->get('view_in_url')) {
            $this->_request_switch['view-raw'] = array
            (
                'handler' => array('net_nehmer_blog_handler_view', 'view'),
                'fixed_args' => array('view', 'raw'),
                'variable_args' => 1,
            );
            $this->_request_switch['view'] = array
            (
                'handler' => array('net_nehmer_blog_handler_view', 'view'),
                'fixed_args' => 'view',
                'variable_args' => 1,
            );
        }

        if ($this->_config->get('rss_subscription_enable')) {
            net_nemein_rss_manage::register_plugin($this);
        }
    }

    public function get_url(midcom_db_article $article, $allow_external = false)
    {
        if (   $allow_external
            && $this->_config->get('link_to_external_url')
            && !empty($article->url)) {
            return $article->url;
        }

        $view_url = $article->name ?: $article->guid;

        if ($this->_config->get('view_in_url')) {
            $view_url = 'view/' . $view_url;
        }
        return $view_url . '/';
    }

    /**
     * Adds the RSS Feed LINK head elements.
     */
    private function _add_link_head()
    {
        if ($this->_config->get('rss_enable')) {
            midcom::get()->head->add_link_head
            (
                array
                (
                    'rel'   => 'alternate',
                    'type'  => 'application/rss+xml',
                    'title' => $this->_l10n->get('rss 2.0 feed'),
                    'href'  => midcom::get()->get_host_name() . midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . 'rss.xml',
                )
            );
            midcom::get()->head->add_link_head
            (
                array
                (
                    'rel'   => 'alternate',
                    'type'  => 'application/atom+xml',
                    'title' => $this->_l10n->get('atom feed'),
                    'href'  => midcom::get()->get_host_name() . midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . 'atom.xml',
                )
            );
        }

        // RSD (Really Simple Discoverability) autodetection
        midcom::get()->head->add_link_head
        (
            array
            (
                'rel' => 'EditURI',
                'type' => 'application/rsd+xml',
                'title' => 'RSD',
                'href' => midcom::get()->get_host_name() . midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . 'rsd.xml',
            )
        );
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
                $buttons[] = $workflow->get_button("create/{$name}/", array
                (
                    MIDCOM_TOOLBAR_LABEL => sprintf
                    (
                        $this->_l10n_midcom->get('create %s'),
                        $this->_l10n->get($this->_request_data['schemadb'][$name]->description)
                    ),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                ));
            }
        }

        if ($this->_config->get('rss_subscription_enable')) {
            net_nemein_rss_manage::add_toolbar_buttons($this->_node_toolbar, $this->_topic->can_do('midgard:create'));
        }

        if (   $this->_config->get('enable_article_links')
            && $this->_content_topic->can_do('midgard:create')) {
            $buttons[] = $workflow->get_button("create/link/", array
            (
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('article link')),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/attach.png'
            ));
        }

        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config')) {
            $buttons[] = $workflow->get_button('config/', array
            (
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
            ));
        }
        $this->_node_toolbar->add_items($buttons);
    }

    /**
     * If the folder already has content in it we should disable the language chooser to avoid confusion
     *
     * @return boolean
     */
    public static function disable_language_select()
    {
        // We cannot use $this->_topic in a static method
        $topic = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_CONTENTTOPIC);
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $topic->id);
        $qb->set_limit(1);
        return ($qb->count() > 0);
    }

    public function _on_handle($handler, array $args)
    {
        $this->_request_data['schemadb'] =
            midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        $this->_add_categories();

        $this->_add_link_head();
        $this->_populate_node_toolbar();
    }

    /**
     * Populate the categories configured for the topic into the schemas
     */
    private function _add_categories()
    {
        if ($this->_config->get('categories') == '') {
            // No categories defined, skip this.
            $this->_request_data['categories'] = array();
            return false;
        }

        $this->_request_data['categories'] = explode(',', $this->_config->get('categories'));

        foreach ($this->_request_data['schemadb'] as $name => $schema) {
            if (   array_key_exists('categories', $schema->fields)
                && $this->_request_data['schemadb'][$name]->fields['categories']['type'] == 'select') {
                // TODO: Merge schema local and component config categories?
                $this->_request_data['schemadb'][$name]->fields['categories']['type_config']['options'] = array();
                foreach ($this->_request_data['categories'] as $category) {
                    $this->_request_data['schemadb'][$name]->fields['categories']['type_config']['options'][$category] = $category;
                }
            }
        }
    }

    /**
     * Set the content topic to use. This will check against the configuration setting
     * 'symlink_topic'.
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

        if ($this->_content_topic->component != 'net.nehmer.blog') {
            debug_print_r('Retrieved topic was:', $this->_content_topic);
            throw new midcom_error('Symlink content topic is invalid, see the debug level log for details.');
        }
    }

    /**
     * Indexes an article.
     *
     * @param midcom_helper_datamanager2_datamanager $dm The Datamanager encapsulating the event.
     * @param midcom_services_indexer $indexer The indexer instance to use.
     * @param midcom_db_topic The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    public static function index($dm, $indexer, $topic)
    {
        $config = new midcom_helper_configuration($topic, 'net.nehmer.blog');

        if ($config->get('disable_indexing')) {
            return;
        }

        if (!is_object($topic)) {
            $topic = new midcom_db_topic($topic);
        }

        // Don't index directly, that would lose a reference due to limitations
        // of the index() method. Needs fixes there.

        $nav = new midcom_helper_nav();
        $node = $nav->get_node($topic->id);

        $document = $indexer->new_document($dm);
        $document->topic_guid = $topic->guid;
        $document->component = $topic->component;
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->storage->object);
        $indexer->index($document);
    }

    /**
     * Simple helper, gets the last modified timestamp of the topic/content_topic combination
     * specified.
     *
     * @param midcom_db_topic $topic The base topic to use.
     * @param mdicom_db_topic $content_topic The topic where the articles are stored.
     */
    public static function get_last_modified($topic, $content_topic)
    {
        // Get last modified timestamp
        $qb = midcom_db_article::new_query_builder();
        // FIXME: use the constraints method below
        $qb->add_constraint('topic', '=', $content_topic->id);
        $qb->add_order('metadata.revised', 'DESC');
        $qb->set_limit(1);

        $articles = $qb->execute();

        if (array_key_exists(0, $articles)) {
            return max($topic->metadata->revised, $articles[0]->metadata->revised);
        }
        return $topic->metadata->revised;
    }

    /**
     * Sets the constraints for QB for articles, supports article links etc.
     *
     * @param midgard_query_builder $qb reference to the QB object
     * @param array $data reference to the request_data array
     */
    public static function article_qb_constraints($qb, array $data, $handler_id)
    {
        $config = $data['config'];
        $topic_guids = array($data['content_topic']->guid);

        // Resolve any other topics we may need
        if ($list_from_folders = $config->get('list_from_folders')) {
            // We have specific folders to list from, therefore list from them and current node
            $guids = explode('|', $list_from_folders);
            $topic_guids = array_merge($topic_guids, array_filter($guids, 'mgd_is_guid'));
        }

        // Include the article links to the indexes if enabled
        if ($config->get('enable_article_links')) {
            $mc = net_nehmer_blog_link_dba::new_collector('topic', $data['content_topic']->id);
            $mc->add_order('metadata.published', 'DESC');
            $mc->set_limit((int) $config->get('index_entries'));

            // Get the results
            $qb->begin_group('OR');
            $qb->add_constraint('id', 'IN', $mc->get_values('article'));
            $qb->add_constraint('topic.guid', 'IN', $topic_guids);
            $qb->end_group();
        } else {
            $qb->add_constraint('topic.guid', 'IN', $topic_guids);
        }

        if (   count($topic_guids) > 1
            && $list_from_folders_categories = $config->get('list_from_folders_categories')) {
            $list_from_folders_categories = explode(',', $list_from_folders_categories);
            // TODO: check schema storage to get fieldname
            $multiple_categories = true;
            if (   isset($data['schemadb']['default'])
                && isset($data['schemadb']['default']->fields['list_from_folders_categories'])
                && array_key_exists('allow_multiple', $data['schemadb']['default']->fields['list_from_folders_categories']['type_config'])
                && !$data['schemadb']['default']->fields['list_from_folders_categories']['type_config']['allow_multiple']) {
                $multiple_categories = false;
            }
            debug_add("multiple_categories={$multiple_categories}");

            $qb->begin_group('OR');
            $qb->add_constraint('topic.guid', '=', $topic_guids[0]);
            $qb->begin_group('OR');
            foreach ($list_from_folders_categories as $category) {
                if ($category = trim($category)) {
                    if ($multiple_categories) {
                        $qb->add_constraint('extra1', 'LIKE', "%|{$category}|%");
                    } else {
                        $qb->add_constraint('extra1', '=', $category);
                    }
                }
            }
            $qb->end_group();
            $qb->end_group();
        }

        // Hide the articles that have the publish time in the future and if
        // the user is not administrator
        if (   $config->get('enable_scheduled_publishing')
            && !midcom::get()->auth->admin) {
            // Show the article only if the publishing time has passed or the viewer
            // is the author
            $qb->begin_group('OR');
            $qb->add_constraint('metadata.published', '<', gmdate('Y-m-d H:i:s'));

            if (!empty(midcom::get()->auth->user->guid)) {
                $qb->add_constraint('metadata.authors', 'LIKE', '|' . midcom::get()->auth->user->guid . '|');
            }
            $qb->end_group();
        }

        $qb->add_constraint('up', '=', 0);
    }
}
