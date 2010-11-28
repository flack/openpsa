<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: viewer.php 20913 2009-03-08 13:39:13Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wiki Site interface class.
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_viewer extends midcom_baseclasses_components_request
{
    function _on_initialize()
    {
        // Match /delete/<wikipage>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'delete',
            'variable_args' => 1,
            'handler' => Array('net_nemein_wiki_handler_delete', 'delete'),
        );

        // Match /raw/<wikipage>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'raw',
            'variable_args' => 1,
            'handler' => Array('net_nemein_wiki_handler_view', 'raw'),
        );

        // Match /source/<wikipage>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'source',
            'variable_args' => 1,
            'handler' => Array('net_nemein_wiki_handler_view', 'source'),
        );

        // Match /whatlinks/<wikipage>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'whatlinks',
            'variable_args' => 1,
            'handler' => Array('net_nemein_wiki_handler_view', 'whatlinks'),
        );

        // Match /subscribe/<wikipage>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'subscribe',
            'variable_args' => 1,
            'handler' => Array('net_nemein_wiki_handler_view', 'subscribe'),
        );

        // Match /create/
        $this->_request_switch['create_by_word'] = array
        (
            'fixed_args' => 'create',
            'handler' => Array('net_nemein_wiki_handler_create', 'create'),
        );

        // Match /create/<schema>
        $this->_request_switch['create_by_word_schema'] = array
        (
            'fixed_args' => 'create',
            'variable_args' => 1,
            'handler' => Array('net_nemein_wiki_handler_create', 'create'),
        );

        // Match /tags/<tag>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'tags',
            'variable_args' => 1,
            'handler' => Array('net_nemein_wiki_handler_tag', 'tagged'),
        );

        // Match /notfound/<wikiword>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'notfound',
            'variable_args' => 1,
            'handler' => Array('net_nemein_wiki_handler_notfound', 'notfound'),
        );

        // Match /edit/<wikipage>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'edit',
            'variable_args' => 1,
            'handler' => Array('net_nemein_wiki_handler_edit', 'edit'),
        );

        // Match /change/<wikipage>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'change',
            'variable_args' => 1,
            'handler' => Array('net_nemein_wiki_handler_edit', 'change'),
        );

        // Match /rss.xml
        $this->_request_switch[] = array
        (
            'fixed_args' => 'rss.xml',
            'handler' => Array('net_nemein_wiki_handler_feed', 'rss'),
        );

        // Match /latest
        $this->_request_switch[] = array
        (
            'fixed_args' => 'latest',
            'handler' => Array('net_nemein_wiki_handler_latest', 'latest'),
        );

        // Match /orphans
        $this->_request_switch[] = array
        (
            'fixed_args' => 'orphans',
            'handler' => Array('net_nemein_wiki_handler_orphan', 'orphan'),
        );


        // Match /
        $this->_request_switch[] = array
        (
            'handler' => Array('net_nemein_wiki_handler_view', 'view'),
        );

        // Match /email_import
        $this->_request_switch[] = array
        (
            'fixed_args' => 'email_import',
            'handler' => Array('net_nemein_wiki_handler_emailimport', 'emailimport'),
        );

        // Match /<wikipage>
        $this->_request_switch[] = array
        (
            'variable_args' => 1,
            'handler' => Array('net_nemein_wiki_handler_view', 'view'),
        );
    }

    function _on_handle($handler_id, $args)
    {
        $this->_request_data['schemadb'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        // Add machine-readable RSS link
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel'   => 'alternate',
                'type'  => 'application/rss+xml',
                'title' => 'Latest changes RSS',
                'href'  => $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . 'rss.xml',
            )
        );

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/net.nemein.wiki/wiki.css");

        if ($_MIDCOM->auth->user)
        {
            $user = $_MIDCOM->auth->user->get_storage();
            if ($this->_topic->parameter('net.nemein.wiki:watch', $user->guid))
            {
                $this->_node_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "subscribe/index/",
                        MIDCOM_TOOLBAR_LABEL => $this->_request_data['l10n']->get('unsubscribe'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_mail.png',
                        MIDCOM_TOOLBAR_POST => true,
                        MIDCOM_TOOLBAR_POST_HIDDENARGS => array
                        (
                            'unsubscribe' => 1,
                            'target'      => 'folder',
                        ),
                    )
                );
            }
            else
            {
                $this->_node_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "subscribe/index/",
                        MIDCOM_TOOLBAR_LABEL => $this->_request_data['l10n']->get('subscribe'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_mail.png',
                        MIDCOM_TOOLBAR_POST => true,
                        MIDCOM_TOOLBAR_POST_HIDDENARGS => array
                        (
                            'subscribe' => 1,
                            'target'      => 'folder',
                        ),
                    )
                );
            }
        }

        $this->_node_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "orphans/",
                MIDCOM_TOOLBAR_LABEL => $this->_request_data['l10n']->get('orphaned pages'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/editcut.png',
            )
        );

        return true;
    }

    /**
     * Indexes a wiki page.
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

    function initialize_index_article($topic)
    {
        $page = new net_nemein_wiki_wikipage();
        $page->topic = $topic->id;
        $page->name = 'index';
        $page->title = $topic->extra;
        $page->content = $this->_l10n->get('wiki default page content');
        $page->author = midcom_connection::get_user();
        if ($page->create())
        {
                return $page;
        }
        return false;
    }
}
?>