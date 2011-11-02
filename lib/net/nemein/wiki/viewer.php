<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
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
    public function _on_handle($handler_id, $args)
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
    }

    public function load_page($wikiword)
    {
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_constraint('name', '=', $wikiword);
        $result = $qb->execute();

        if (count($result) > 0)
        {
            return $result[0];
        }
        throw new midcom_error_notfound('The page "' . $wikiword . '" could not be found.');
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
            $topic = new midcom_db_topic($topic);
        }

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