<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: viewer.php 25548 2010-04-06 10:53:55Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * n.n.static site interface class
 *
 * This is a complete rewrite of the topic-article viewer the has been made for MidCOM 2.6.
 * It incorporates all of the goodies current MidCOM has to offer and can serve as an
 * example component therefore.
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

        $GLOBALS['net_nehmer_static_mode'] = 'view';

        // *** Prepare the request switch ***

        // Administrative stuff
        $this->_request_switch['edit'] = array
        (
            'handler' => array
            (
                'net_nehmer_static_handler_admin',
                'edit',
            ),
            'fixed_args' => array
            (
                'edit',
            ),
            'variable_args' => 1,
        );
        $this->_request_switch['delete'] = array
        (
            'handler' => array
            (
                'net_nehmer_static_handler_admin',
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
                'net_nehmer_static_handler_admin',
                'deletelink',
            ),
            'fixed_args' => array
            (
                'delete',
                'link',
            ),
            'variable_args' => 1,
        );
        $this->_request_switch['create_link'] = array
        (
            'handler' => array
            (
                'net_nehmer_static_handler_link',
                'create',
            ),
            'fixed_args' => array
            (
                'create',
                'link',
            ),
        );
        $this->_request_switch['create'] = array
        (
            'handler' => array
            (
                'net_nehmer_static_handler_create',
                'create',
            ),
            'fixed_args' => array
            (
                'create',
            ),
            'variable_args' => 1,
        );
        $this->_request_switch['createindex'] = array
        (
            'handler' => array
            (
                'net_nehmer_static_handler_create',
                'create',
            ),
            'fixed_args' => array
            (
                'createindex',
            ),
            'variable_args' => 1,
        );
        $this->_request_switch['config'] = array
        (
            'handler' => array
            (
                'midcom_core_handler_configdm2',
                'config',
            ),
            'fixed_args' => array
            (
                'config',
            ),
        );


        // View mode handler, set index viewer according to autoindex setting.
        // These, especially the general view handler, must come last, otherwise we'll hide other
        // handlers
        if ($this->_config->get('autoindex'))
        {
            $this->_request_switch['autoindex'] = array
            (
                'handler' => array
                (
                    'net_nehmer_static_handler_autoindex',
                    'autoindex',
                ),
            );
        }
        else
        {
            $this->_request_switch['index'] = array
            (
                'handler' => array
                (
                    'net_nehmer_static_handler_view',
                    'view',
                ),
            );
        }

        // AJAX version of view, which skips style.
        $this->_request_switch['view_raw'] = array
        (
            'handler' => array
            (
                'net_nehmer_static_handler_view',
                'view',
            ),
            'fixed_args' => array
            (
                'raw',
            ),
            'variable_args' => 1,
        );

        $this->_request_switch['view'] = array
        (
            'handler' => array
            (
                'net_nehmer_static_handler_view',
                'view',
            ),
            'variable_args' => 1,
        );

    }

    /**
     * Set the content topic to use. This will check against the configuration setting 'symlink_topic'.
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

        if ($this->_content_topic->component != 'net.nehmer.static')
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
     * @param midcom_db_topic $topic The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    function index(&$dm, &$indexer, $topic)
    {
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
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->storage->object);
        $document->component = $topic->component;
        $indexer->index($document);
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
     * The handle callback populates the toolbars.
     */
    function _on_handle($handler, $args)
    {
        $GLOBALS['net_nehmer_static_mode'] = $handler;

        $this->_request_data['schemadb'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        $GLOBALS['net_nehmer_static_schemadbs'] = array();
        $GLOBALS['net_nehmer_static_schemadbs'][''] = $this->_l10n_midcom->get('default setting');

        $config_schemadbs = $this->_config->get('schemadbs');
        if (is_array($config_schemadbs))
        {
            foreach ($config_schemadbs as $key => $description)
            {
                $GLOBALS['net_nehmer_static_schemadbs'][$key] = $this->_l10n->get($description);
            }
        }
        unset($config_schemadbs);

        $this->_populate_node_toolbar();

        return true;
    }
}
?>
