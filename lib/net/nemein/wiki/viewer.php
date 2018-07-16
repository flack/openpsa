<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Wiki Site interface class.
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_viewer extends midcom_baseclasses_components_request
{
    public function _on_handle($handler_id, array $args)
    {
        // Add machine-readable RSS link
        midcom::get()->head->add_link_head([
            'rel'   => 'alternate',
            'type'  => 'application/rss+xml',
            'title' => sprintf($this->_l10n->get('latest updates in %s'), $this->_topic->extra),
            'href'  => $this->router->generate('rss'),
        ]);

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/net.nemein.wiki/wiki.css");

        if (midcom::get()->auth->user) {
            $user = midcom::get()->auth->user->get_storage();
            if ($this->_topic->get_parameter('net.nemein.wiki:watch', $user->guid)) {
                $action = 'unsubscribe';
            } else {
                $action = 'subscribe';
            }
            $this->_node_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => "subscribe/index/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get($action),
                MIDCOM_TOOLBAR_GLYPHICON => 'envelope-o',
                MIDCOM_TOOLBAR_POST => true,
                MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                    $action => 1,
                    'target'      => 'folder',
                ]
            ]);
        }

        $this->_node_toolbar->add_item([
            MIDCOM_TOOLBAR_URL => "orphans/",
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('orphaned pages'),
            MIDCOM_TOOLBAR_GLYPHICON => 'chain-broken',
        ]);
    }

    /**
     * Indexes a wiki page.
     *
     * @param datamanager $dm The Datamanager encapsulating the event.
     * @param midcom_services_indexer $indexer The indexer instance to use.
     * @param midcom_db_topic|midcom_core_dbaproxy The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    public static function index(datamanager $dm, $indexer, $topic)
    {
        $nav = new midcom_helper_nav();
        $node = $nav->get_node($topic->id);

        $document = $indexer->new_document($dm);
        $document->topic_guid = $topic->guid;
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->get_storage()->get_value());
        $document->component = $topic->component;
        $indexer->index($document);
    }
}
