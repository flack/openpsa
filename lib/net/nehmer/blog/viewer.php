<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: viewer.php 26587 2010-08-05 13:58:52Z jval $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Newsticker / Blog site interface class
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
     * @access private
     */
    var $_content_topic = null;

    function __construct($topic, $config)
    {
        parent::__construct($topic, $config);
    }

    /**
     * Initialize the request switch and the content topic.
     *
     * @access protected
     */
    function _on_initialize()
    {
        $this->_determine_content_topic();
        $this->_request_data['content_topic'] =& $this->_content_topic;

        // *** Prepare the request switch ***

        // Index
        $this->_request_switch['index'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_index',
                'index',
            ),
        );
        $this->_request_switch['latest'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_index',
                'index',
            ),
            'fixed_args' => array
            (
                'latest',
            ),
            'variable_args' => 1,
        );

        // Handler for /ajax/latest/<number>
        $this->_request_switch['ajax-latest'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_index',
                'index',
            ),
            'fixed_args' => array
            (
                'ajax',
                'latest',
            ),
            'variable_args' => 1,
        );

        // Handler for /category/<category>
        $this->_request_switch['index-category'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_index',
                'index',
            ),
            'fixed_args' => array
            (
                'category',
            ),
            'variable_args' => 1,
        );
        // Handler for /category/latest/<category/<number>
        $this->_request_switch['latest-category'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_index',
                'index',
            ),
            'fixed_args' => array
            (
                'category',
                'latest',
            ),
            'variable_args' => 2,
        );

        // Various Feeds and their index page
        $this->_request_switch['feed-index'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_feed',
                'index',
            ),
            'fixed_args' => array
            (
                'feeds',
            ),
        );
        $this->_request_switch['feed-category-rss2'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_feed',
                'feed',
            ),
            'fixed_args' => array
            (
                'feeds',
                'category',
            ),
            'variable_args' => 1,
        );
        $this->_request_switch['feed-rss2'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_feed',
                'feed',
            ),
            'fixed_args' => array
            (
                'rss.xml',
            ),
        );        $this->_request_switch['feed-rss1'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_feed',
                'feed',
            ),
            'fixed_args' => array
            (
                'rss1.xml',
            ),
        );        $this->_request_switch['feed-rss091'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_feed',
                'feed',
            ),
            'fixed_args' => array
            (
                'rss091.xml',
            ),
        );        $this->_request_switch['feed-atom'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_feed',
                'feed',
            ),
            'fixed_args' => array
            (
                'atom.xml',
            ),
        );        $this->_request_switch['feed-rsd'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_api_metaweblog',
                'rsd',
            ),
            'fixed_args' => array
            (
                'rsd.xml',
            ),
        );
        // The Archive
        $this->_request_switch['archive-welcome'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_archive',
                'welcome',
            ),
            'fixed_args' => array
            (
                'archive',
            ),
        );        $this->_request_switch['archive-year'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_archive',
                'list',
            ),
            'fixed_args' => array
            (
                'archive',
                'year',
            ),
            'variable_args' => 1,
        );
        $this->_request_switch['archive-year-category'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_archive',
                'list',
            ),
            'fixed_args' => array
            (
                'archive',
                'year',
            ),
            'variable_args' => 2,
        );
        $this->_request_switch['archive-month'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_archive',
                'list',
            ),
            'fixed_args' => array
            (
                'archive',
                'month',
            ),
            'variable_args' => 2,
        );

        // Administrative stuff
        $this->_request_switch['edit'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_admin',
                'edit',
            ),
            'fixed_args' => array
            (
                'edit',
            ),
            'variable_args' => 1,
        );
        $this->_request_switch['delete'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_admin',
                'delete',
            ),
            'fixed_args' => array
            (
                'delete',
            ),
            'variable_args' => 1,
        );
        $this->_request_switch['delete_link'] = array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_admin',
                'deletelink',
            ),
            'fixed_args' => array
            (
                'delete',
                'link',
            ),
            'variable_args' => 1,
        );
        $this->_request_switch['create_link'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_link',
                'create',
            ),
            'fixed_args' => array
            (
                'create',
                'link',
            ),
        );

        $this->_request_switch['create'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_create',
                'create',
            ),
            'fixed_args' => array
            (
                'create',
            ),
            'variable_args' => 1,
        );

        $this->_request_switch['config'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_configuration',
                'config',
            ),
            'fixed_args' => array
            (
                'config',
            ),
        );

        $this->_request_switch['config_recreate'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_configuration',
                'recreate',
            ),
            'fixed_args' => array
            (
                'config',
                'recreate',
            ),
        );
        $this->_request_switch['api-email'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_api_email',
                'import',
            ),
            'fixed_args' => array
            (
                'api',
                'email',
            ),
        );
        $this->_request_switch['api-email-basicauth'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_api_email',
                'import',
            ),
            'fixed_args' => array
            (
                'api',
                'email_basicauth',
            ),
        );
        $this->_request_switch['api-metaweblog'] = Array
        (
            'handler' => array
            (
                'net_nehmer_blog_handler_api_metaweblog',
                'server',
            ),
            'fixed_args' => array
            (
                'api',
                'metaweblog',
            ),
        );
        // View article
        if ($this->_config->get('view_in_url'))
        {
            $this->_request_switch['view-raw'] = Array
            (
                'handler' => array
                (
                    'net_nehmer_blog_handler_view',
                    'view',
                ),
                'fixed_args' => array
                (
                    'view',
                    'raw',
                ),
                'variable_args' => 1,
            );
            $this->_request_switch['view'] = Array
            (
                'handler' => array
                (
                    'net_nehmer_blog_handler_view',
                    'view',
                ),
                'fixed_args' => array
                (
                    'view',
                ),
                'variable_args' => 1,
            );
        }
        else
        {
            $this->_request_switch['view-raw'] = Array
            (
                'handler' => array
                (
                    'net_nehmer_blog_handler_view',
                    'view',
                ),
                'fixed_args' => array
                (
                    'raw',
                ),
                'variable_args' => 1,
            );
            $this->_request_switch['view'] = Array
            (
                'handler' => array
                (
                    'net_nehmer_blog_handler_view',
                    'view',
                ),
                'variable_args' => 1,
            );
        }

        if ($this->_config->get('rss_subscription_enable'))
        {
            $_MIDCOM->load_library('net.nemein.rss');
            $rss_switches = net_nemein_rss_manage::get_plugin_handlers();
            $this->_request_switch = array_merge($this->_request_switch, $rss_switches);
        }

    }

    /**
     * Adds the RSS Feed LINK head elements.
     *
     * @access protected
     */
    function _add_link_head()
    {
        if ($this->_config->get('rss_enable'))
        {
            $_MIDCOM->add_link_head
            (
                array
                (
                    'rel'   => 'alternate',
                    'type'  => 'application/rss+xml',
                    'title' => $this->_l10n->get('rss 2.0 feed'),
                    'href'  => $_MIDCOM->get_host_name() . $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . 'rss.xml',
                )
            );
            $_MIDCOM->add_link_head
            (
                array
                (
                    'rel'   => 'alternate',
                    'type'  => 'application/atom+xml',
                    'title' => $this->_l10n->get('atom feed'),
                    'href'  => $_MIDCOM->get_host_name() . $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . 'atom.xml',
                )
            );
        }

        // RSD (Really Simple Discoverability) autodetection
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'EditURI',
                'type' => 'application/rsd+xml',
                'title' => 'RSD',
                'href' => $_MIDCOM->get_host_name() . $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . 'rsd.xml',
            )
        );
    }

    /**
     * Populates the node toolbar depending on the user's rights.
     *
     * @access protected
     */
    function _populate_node_toolbar()
    {
        if ($this->_content_topic->can_do('midgard:create'))
        {
            foreach (array_keys($this->_request_data['schemadb']) as $name)
            {
                $this->_node_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "create/{$name}/",
                        MIDCOM_TOOLBAR_LABEL => sprintf
                        (
                            $this->_l10n_midcom->get('create %s'),
                            $this->_l10n->get($this->_request_data['schemadb'][$name]->description)
                        ),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
                        MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                    )
                );
            }
        }

        if ($this->_config->get('rss_subscription_enable'))
        {
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'feeds/subscribe/',
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('subscribe feeds', 'net.nemein.rss'),
                    MIDCOM_TOOLBAR_ICON => 'net.nemein.rss/rss-16.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_topic->can_do('midgard:create'),
                )
            );
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'feeds/list/',
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('manage feeds', 'net.nemein.rss'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_topic->can_do('midgard:create'),
                )
            );
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "feeds/fetch/all",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('refresh all feeds', 'net.nemein.rss'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_refresh.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_topic->can_do('midgard:create'),
                )
            );
        }

        if (   $this->_config->get('enable_article_links')
            && $this->_content_topic->can_do('midgard:create'))
        {
            $this->_node_toolbar->add_item(
                array
                (
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('article link')),
                    MIDCOM_TOOLBAR_URL => "create/link/",
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/attach.png',
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
     * If the folder already has content in it we should disable the language chooser to avoid confusion
     *
     * @return boolean
     */
    static function disable_language_select()
    {
        // We cannot use $this->_topic in a static method
        $topic = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_CONTENTTOPIC);
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $topic->id);
        $qb->set_limit(1);
        if ($qb->count() > 0)
        {
            return true;
        }
        return false;
    }

    /**
     * Generic request startup work:
     *
     * - Load the Schema Database
     * - Add the LINK HTML HEAD elements
     * - Populate the Node Toolbar
     */
    function _on_can_handle($handler, $args)
    {
        $this->_request_data['viewer_instance'] =& $this;
        return true;
    }

    function _on_handle($handler, $args)
    {
        $this->_request_data['schemadb'] =
            midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        $this->_add_categories();

        $this->_add_link_head();
        $this->_populate_node_toolbar();
        return true;
    }

    /**
     * Populate the categories configured for the topic into the schemas
     */
    function _add_categories()
    {
        if ($this->_config->get('categories') == '')
        {
            // No categories defined, skip this.
            $this->_request_data['categories'] = Array();
            return false;
        }

        $this->_request_data['categories'] = explode(',', $this->_config->get('categories'));

        foreach ($this->_request_data['schemadb'] as $name => $schema)
        {
            if (   array_key_exists('categories', $schema->fields)
                && $this->_request_data['schemadb'][$name]->fields['categories']['type'] == 'select')
            {
                // TODO: Merge schema local and component config categories?
                $this->_request_data['schemadb'][$name]->fields['categories']['type_config']['options'] = Array();
                foreach ($this->_request_data['categories'] as $category)
                {
                    $this->_request_data['schemadb'][$name]->fields['categories']['type_config']['options'][$category] = $category;
                }
            }
        }
    }

    /**
     * Set the content topic to use. This will check against the configuration setting
     * 'symlink_topic'.
     *
     * @access protected
     */
    function _determine_content_topic()
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $guid = $this->_config->get('symlink_topic');
        if (is_null($guid))
        {
            // No symlink topic
            // Workaround, we should talk to a DBA object automatically here in fact.
            $this->_content_topic = midcom_db_topic::get_cached($this->_topic->id);
            debug_pop();
            return;
        }

        $this->_content_topic = midcom_db_topic::get_cached($guid);

        // Validate topic.

        if (! $this->_content_topic)
        {
            debug_add('Failed to open symlink content topic, (might also be an invalid object) last Midgard Error: '
                . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to open symlink content topic.');
            // This will exit.
        }

        if ($this->_content_topic->component != 'net.nehmer.blog')
        {
            debug_print_r('Retrieved topic was:', $this->_content_topic);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'Symlink content topic is invalid, see the debug level log for details.');
            // This will exit.
        }

        debug_pop();
    }

    /**
     * Indexes an article.
     *
     * This function is usually called statically from various handlers.
     *
     * @param midcom_helper_datamanager2_datamanager &$dm The Datamanager encapsulating the event.
     * @param midcom_services_indexer &$indexer The indexer instance to use.
     * @param midcom_db_topic The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    function index(&$dm, &$indexer, $topic)
    {
        $config = new midcom_helper_configuration($topic, 'net.nehmer.blog');

        if ($config->get('disable_indexing'))
        {
            return;
        }

        if (!is_object($topic))
        {
            $tmp = new midcom_db_topic($topic);
            if (! $tmp)
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Failed to load the topic referenced by {$topic} for indexing, this is fatal.");
                // This will exit.
            }
            $topic = $tmp;
        }

        // Don't index directly, that would loose a reference due to limitations
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
    function get_last_modified($topic, $content_topic)
    {
        // Get last modified timestamp
        $qb = midcom_db_article::new_query_builder();
        // FIXME: use the constraints method below
        $qb->add_constraint('topic', '=', $content_topic->id);
        $qb->add_order('metadata.revised', 'DESC');
        $qb->set_limit(1);

        $articles = $qb->execute();

        if ($articles)
        {
            if (array_key_exists(0, $articles))
            {
                return max($topic->metadata->revised, $articles[0]->metadata->revised);
            }
            return $topic->metadata->revised;
        }
        else
        {
            return $topic->metadata->revised;
        }
    }

    /**
     * Sets the constraints for QB for articles, supports article links etc..
     *
     * @param midgard_query_builder $qb reference to the QB object
     * @param array $data reference to the request_data array
     */
    function article_qb_constraints(&$qb, &$data, $handler_id)
    {
        $config =& $data['config'];
        // GUIDs of topics to list articles from.
        $guids_array = array();
        $guids_array[] = $data['content_topic']->guid;
        // Resolve any other topics we may need
        $list_from_folders = $config->get('list_from_folders');
        if ($list_from_folders)
        {
            // We have specific folders to list from, therefore list from them and current node
            $guids = explode('|', $config->get('list_from_folders'));
            foreach ($guids as $guid)
            {
                if (   !$guid
                    || !mgd_is_guid($guid))
                {
                    // Skip empty and broken guids
                    continue;
                }

                $guids_array[] = $guid;
            }
        }

        /**
         * Ref #1776, expands GUIDs before adding them as constraints, should save query time
         */
        $topic_ids = array();
        $topic_ids[] = $data['content_topic']->id;
        if (   !empty($guids_array)
            && $_MIDGARD['sitegroup'])
        {
            $mc = midcom_db_topic::new_collector('sitegroup', $_MIDGARD['sitegroup']);
            $mc->add_constraint('guid', 'IN', $guids_array);
            $mc->add_value_property('id');
            $mc->execute();
            $keys = $mc->list_keys();
            foreach ($keys as $guid => $dummy)
            {
                $topic_ids[] = $mc->get_subkey($guid, 'id');
            }
            unset($mc, $keys, $guid, $dummy);
        }

        // Include the article links to the indexes if enabled
        if ($config->get('enable_article_links'))
        {
            $mc = net_nehmer_blog_link_dba::new_collector('topic', $data['content_topic']->id);
            $mc->add_value_property('article');
            $mc->add_constraint('topic', '=', $data['content_topic']->id);
            $mc->add_order('metadata.published', 'DESC');

            // Use sophisticated guess to limit the amount: there shouldn't be more than
            // the required amount of links that is needed. Even if some links would fall
            // off due to a broken link (i.e. removed article), there should be enough
            // of content to fill the blank
            switch ($handler_id)
            {
                case 'index':
                case 'index-category':
                    $mc->set_limit((int) $config->get('index_entries'));
                    break;

                case 'latest':
                case 'ajax-latest':
                    $mc->set_limit((int) $config->get('index_entries'));
                    break;

                case 'latest-category':
                    $mc->set_limit((int) $config->get('index_entries'));
                    break;

                default:
                    $mc->set_limit((int) $config->get('index_entries'));
                    break;
            }

            // Get the results
            $mc->execute();

            $links = $mc->list_keys();
            $qb->begin_group('OR');
                foreach ($links as $guid => $link)
                {
                    $article_id = $mc->get_subkey($guid, 'article');
                    $qb->add_constraint('id', '=', $article_id);
                }
                unset($mc, $links, $guid, $link);
                /**
                 * Ref #1776, expands GUIDs before adding them as constraints, should save query time
                $qb->add_constraint('topic.guid', 'IN', $guids_array);
                 */
                $qb->add_constraint('topic', 'IN', $topic_ids);
            $qb->end_group();
        }
        else
        {
            /**
             * Ref #1776, expands GUIDs before adding them as constraints, should save query time
            $qb->add_constraint('topic.guid', 'IN', $guids_array);
             */
            $qb->add_constraint('topic', 'IN', $topic_ids);
        }

        if (   count($topic_ids) > 1
            && $list_from_folders_categories = $config->get('list_from_folders_categories'))
        {
            // TODO: check schema storage to get fieldname
            $multiple_categories = true;
            if (   isset($data['schemadb']['default'])
                && isset($data['schemadb']['default']->fields['list_from_folders_categories'])
                && array_key_exists('allow_multiple', $data['schemadb']['default']->fields['list_from_folders_categories']['type_config'])
                && !$data['schemadb']['default']->fields['list_from_folders_categories']['type_config']['allow_multiple'])
            {
                $multiple_categories = false;
            }
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("multiple_categories={$multiple_categories}");
            debug_pop();

            $qb->begin_group('OR');
                $list_from_folders_categories = explode(',', $list_from_folders_categories);
                $is_content_topic = true;
                foreach ($topic_ids as $topic_id)
                {
                    if ($is_content_topic)
                    {
                        $qb->add_constraint('topic', '=', $topic_id);
                        $is_content_topic = false;
                        continue;
                    }
                    $qb->begin_group('AND');
                        $qb->add_constraint('topic', '=', $topic_id);
                        $qb->begin_group('OR');
                            foreach ($list_from_folders_categories as $category)
                            {
                                if ($category = trim($category))
                                {
                                    if ($multiple_categories)
                                    {
                                        $qb->add_constraint('extra1', 'LIKE', "%|{$category}|%");
                                    }
                                    else
                                    {
                                        $qb->add_constraint('extra1', '=', $category);
                                    }
                                }
                            }
                        $qb->end_group();
                    $qb->end_group();
                }
            $qb->end_group();
        }

        // Hide the articles that have the publish time in the future and if
        // the user is not administrator
        if (   $config->get('enable_scheduled_publishing')
            && !$_MIDCOM->auth->admin)
        {
            // Show the article only if the publishing time has passed or the viewer
            // is the author
            $qb->begin_group('OR');
                $qb->add_constraint('metadata.published', '<', gmdate('Y-m-d H:i:s'));

                if (   $_MIDCOM->auth->user
                    && isset($_MIDCOM->auth->user->guid))
                {
                    $qb->add_constraint('metadata.authors', 'LIKE', '|' . $_MIDCOM->auth->user->guid . '|');
                }
            $qb->end_group();
        }


        $qb->add_constraint('up', '=', 0);
    }

}

?>
