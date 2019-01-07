<?php
/**
 * @package net.nemein.rss
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Feed management class.
 *
 * @package net.nemein.rss
 */
class net_nemein_rss_handler_list extends midcom_baseclasses_components_handler
{
    /**
     * @param array $data The local request data.
     */
    public function _handler_opml(array &$data)
    {
        midcom::get()->cache->content->content_type("text/xml; charset=UTF-8");
        midcom::get()->skip_page_style = true;

        $qb = net_nemein_rss_feed_dba::new_query_builder();
        $qb->add_order('title');
        $qb->add_constraint('node', '=', $this->_topic->id);
        $data['feeds'] = $qb->execute();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_opml($handler_id, array &$data)
    {
        $opml = new OPMLCreator();
        $opml->title = $this->_topic->extra;

        foreach ($data['feeds'] as $feed) {
            $item = new FeedItem();
            $item->title = $feed->title;
            $item->xmlUrl = $feed->url;
            $opml->addItem($item);
        }

        echo $opml->createFeed();
    }

    /**
     * @param array $data The local request data.
     */
    public function _handler_list(array &$data)
    {
        $qb = net_nemein_rss_feed_dba::new_query_builder();
        $qb->add_order('title');
        $qb->add_constraint('node', '=', $this->_topic->id);
        $data['feeds'] = $qb->execute();

        \midcom\workflow\delete::add_head_elements();
        $this->add_breadcrumb($this->router->generate('feeds_list'), $this->_l10n->get('manage feeds'));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        $data['folder'] = $this->_topic;
        midcom_show_style('net-nemein-rss-feeds-list-header');

        foreach ($data['feeds'] as $feed) {
            $data['feed'] = $feed;
            $data['feed_category'] = 'feed:' . md5($feed->url);
            $data['feed_toolbar'] = $this->create_toolbar($feed);
            $data['topic'] = $this->_topic;
            midcom_show_style('net-nemein-rss-feeds-list-item');
        }

        midcom_show_style('net-nemein-rss-feeds-list-footer');
    }

    /**
     *
     * @param net_nemein_rss_feed_dba $feed
     * @return midcom_helper_toolbar
     */
    private function create_toolbar(net_nemein_rss_feed_dba $feed)
    {
        $toolbar = new midcom_helper_toolbar();
        $buttons = [];
        if ($feed->can_do('midgard:update')) {
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $this->router->generate('feeds_edit', ['guid' => $feed->guid]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_GLYPHICON => 'pencil',
            ];
        }

        if ($this->_topic->can_do('midgard:create')) {
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $this->router->generate('feeds_fetch', ['guid' => $feed->guid]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('refresh feed'),
                MIDCOM_TOOLBAR_GLYPHICON => 'refresh',
            ];
        }

        if ($feed->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', ['object' => $feed]);
            $buttons[] = $workflow->get_button($this->router->generate('feeds_delete', ['guid' => $feed->guid]));
        }
        $toolbar->add_items($buttons);
        return $toolbar;
    }
}
