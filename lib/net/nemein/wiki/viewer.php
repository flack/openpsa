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
    public function _on_handle($handler_id, array $args)
    {
        $this->_request_data['schemadb'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        // Add machine-readable RSS link
        midcom::get()->head->add_link_head(
            array(
                'rel'   => 'alternate',
                'type'  => 'application/rss+xml',
                'title' => sprintf($this->_l10n->get('latest updates in %s'), $this->_topic->extra),
                'href'  => midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . 'rss.xml',
            )
        );

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/net.nemein.wiki/wiki.css");

        if (midcom::get()->auth->user) {
            $user = midcom::get()->auth->user->get_storage();
            if ($this->_topic->get_parameter('net.nemein.wiki:watch', $user->guid)) {
                $action = 'unsubscribe';
            } else {
                $action = 'subscribe';
            }
            $this->_node_toolbar->add_item(
                array(
                    MIDCOM_TOOLBAR_URL => "subscribe/index/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get($action),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_mail.png',
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_POST_HIDDENARGS => array(
                        $action => 1,
                        'target'      => 'folder',
                    ),
                )
            );
        }

        $this->_node_toolbar->add_item(
            array(
                MIDCOM_TOOLBAR_URL => "orphans/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('orphaned pages'),
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

        if (count($result) > 0) {
            return $result[0];
        }
        throw new midcom_error_notfound('The page "' . $wikiword . '" could not be found.');
    }

    /**
     * Indexes a wiki page.
     *
     * @param midcom_helper_datamanager2_datamanager $dm The Datamanager encapsulating the event.
     * @param midcom_services_indexer $indexer The indexer instance to use.
     * @param midcom_db_topic The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    public static function index($dm, $indexer, $topic)
    {
        if (!is_object($topic)) {
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

    public static function initialize_index_article($topic)
    {
        $page = new net_nemein_wiki_wikipage();
        $page->topic = $topic->id;
        $page->name = 'index';
        $page->title = $topic->extra;
        $page->content = midcom::get()->i18n->get_string('wiki default page content', 'net.nemein.wiki');
        $page->author = midcom_connection::get_user();
        if (!$page->create()) {
            throw new midcom_error('Failed to create index article: ' . midcom_connection::get_error_string());
        }
        return $page;
    }
}
