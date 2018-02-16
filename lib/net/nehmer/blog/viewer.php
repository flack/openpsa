<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;

/**
 * Blog site interface class
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_viewer extends midcom_baseclasses_components_request
{
    /**
     * Initialize the request switch and the content topic.
     */
    public function _on_initialize()
    {
        if ($this->_config->get('view_in_url')) {
            $this->_request_switch['view-raw'] = [
                'handler' => [net_nehmer_blog_handler_view::class, 'view'],
                'fixed_args' => ['view', 'raw'],
                'variable_args' => 1,
            ];
            $this->_request_switch['view'] = [
                'handler' => [net_nehmer_blog_handler_view::class, 'view'],
                'fixed_args' => 'view',
                'variable_args' => 1,
            ];
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
            midcom::get()->head->add_link_head([
                'rel'   => 'alternate',
                'type'  => 'application/rss+xml',
                'title' => $this->_l10n->get('rss 2.0 feed'),
                'href'  => midcom::get()->get_host_name() . midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . 'rss.xml',
            ]);
            midcom::get()->head->add_link_head([
                'rel'   => 'alternate',
                'type'  => 'application/atom+xml',
                'title' => $this->_l10n->get('atom feed'),
                'href'  => midcom::get()->get_host_name() . midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . 'atom.xml',
            ]);
        }

        // RSD (Really Simple Discoverability) autodetection
        midcom::get()->head->add_link_head([
            'rel' => 'EditURI',
            'type' => 'application/rsd+xml',
            'title' => 'RSD',
            'href' => midcom::get()->get_host_name() . midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . 'rsd.xml',
        ]);
    }

    /**
     * Populates the node toolbar depending on the user's rights.
     */
    private function _populate_node_toolbar()
    {
        $buttons = [];
        $workflow = $this->get_workflow('datamanager');
        if ($this->_topic->can_do('midgard:create')) {
            foreach ($this->_request_data['schemadb']->all() as $name => $schema) {
                $buttons[] = $workflow->get_button("create/{$name}/", [
                    MIDCOM_TOOLBAR_LABEL => sprintf(
                        $this->_l10n_midcom->get('create %s'),
                        $this->_l10n->get($schema->get('description'))
                    ),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                ]);
            }
        }

        if ($this->_config->get('rss_subscription_enable')) {
            net_nemein_rss_manage::add_toolbar_buttons($this->_node_toolbar, $this->_topic->can_do('midgard:create'));
        }

        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config')) {
            $buttons[] = $workflow->get_button('config/', [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
            ]);
        }
        $this->_node_toolbar->add_items($buttons);
    }

    public function _on_handle($handler, array $args)
    {
        $this->_request_data['schemadb'] = schemadb::from_path($this->_config->get('schemadb'));
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
            $this->_request_data['categories'] = [];
            return false;
        }

        $this->_request_data['categories'] = explode(',', $this->_config->get('categories'));

        foreach ($this->_request_data['schemadb']->all() as $name => $schema) {
            if (   $schema->has_field('categories')
                && $schema->get_field('categories')['type'] == 'select') {
                // TODO: Merge schema local and component config categories?
                $options = array_combine($this->_request_data['categories'], $this->_request_data['categories']);
                $field =& $schema->get_field('categories');
                $field['type_config']['options'] = $options;
            }
        }
    }

    /**
     * Indexes an article.
     *
     * @param datamanager $dm The Datamanager encapsulating the event.
     * @param midcom_services_indexer $indexer The indexer instance to use.
     * @param midcom_db_topic|midcom_core_dbaproxy The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    public static function index(datamanager $dm, $indexer, $topic)
    {
        $config = new midcom_helper_configuration($topic, 'net.nehmer.blog');

        if ($config->get('disable_indexing')) {
            return;
        }

        $nav = new midcom_helper_nav();
        $node = $nav->get_node($topic->id);

        $document = $indexer->new_document($dm);
        $document->topic_guid = $topic->guid;
        $document->component = $topic->component;
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->get_storage()->get_value());
        $indexer->index($document);
    }

    /**
     * Simple helper, gets the last modified timestamp of the topic combination
     * specified.
     *
     * @param midcom_db_topic $topic The base topic to use.
     */
    public static function get_last_modified($topic)
    {
        // Get last modified timestamp
        $qb = midcom_db_article::new_query_builder();
        // FIXME: use the constraints method below
        $qb->add_constraint('topic', '=', $topic->id);
        $qb->add_order('metadata.revised', 'DESC');
        $qb->set_limit(1);

        $articles = $qb->execute();

        if (array_key_exists(0, $articles)) {
            return max($topic->metadata->revised, $articles[0]->metadata->revised);
        }
        return $topic->metadata->revised;
    }

    /**
     * Sets the constraints for QB for articles
     *
     * @param midgard_query_builder $qb The QB object
     */
    public function article_qb_constraints($qb, $handler_id)
    {
        $topic_guids = [$this->_topic->guid];

        // Resolve any other topics we may need
        if ($list_from_folders = $this->_config->get('list_from_folders')) {
            // We have specific folders to list from, therefore list from them and current node
            $guids = explode('|', $list_from_folders);
            $topic_guids = array_merge($topic_guids, array_filter($guids, 'mgd_is_guid'));
        }

        $qb->add_constraint('topic.guid', 'IN', $topic_guids);

        if (   count($topic_guids) > 1
            && $list_from_folders_categories = $this->_config->get('list_from_folders_categories')) {
            $list_from_folders_categories = explode(',', $list_from_folders_categories);

            $qb->begin_group('OR');
            $qb->add_constraint('topic.guid', '=', $topic_guids[0]);
            $qb->begin_group('OR');
            foreach ($list_from_folders_categories as $category) {
                $this->apply_category_constraint($qb, $category);
            }
            $qb->end_group();
            $qb->end_group();
        }

        // Hide the articles that have the publish time in the future and if
        // the user is not administrator
        if (   $this->_config->get('enable_scheduled_publishing')
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

    /**
     * @param midgard_query_builder $qb
     * @param string $category
     */
    public function apply_category_constraint($qb, $category)
    {
        if ($category = trim($category)) {
            $qb->begin_group('OR');
                $qb->add_constraint('extra1', 'LIKE', "%|{$category}|%");
                $qb->add_constraint('extra1', '=', $category);
            $qb->end_group();
        }
    }
}
