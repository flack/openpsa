<?php
/**
 * @package net.nemein.rss
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Response;

/**
 * Feed management class.
 *
 * @package net.nemein.rss
 */
class net_nemein_rss_handler_list extends midcom_baseclasses_components_handler
{
    /**
     * @var net_nemein_rss_feed_dba[]
     */
    private array $feeds;

    public function _handler_opml() : Response
    {
        $opml = new OPMLCreator();
        $opml->title = $this->_topic->extra;

        $qb = net_nemein_rss_feed_dba::new_query_builder();
        $qb->add_order('title');
        $qb->add_constraint('node', '=', $this->_topic->id);

        foreach ($qb->execute() as $feed) {
            $item = new FeedItem();
            $item->title = $feed->title;
            $item->xmlUrl = $feed->url;
            $opml->addItem($item);
        }

        return new Response($opml->createFeed(), Response::HTTP_OK, [
            'Content-Type' => 'text/xml; charset=UTF-8'
        ]);
    }

    public function _handler_list()
    {
        $qb = net_nemein_rss_feed_dba::new_query_builder();
        $qb->add_order('title');
        $qb->add_constraint('node', '=', $this->_topic->id);
        $this->feeds = $qb->execute();

        \midcom\workflow\delete::add_head_elements();
        $this->add_breadcrumb($this->router->generate('feeds_list'), $this->_l10n->get('manage feeds'));
    }

    public function _show_list(string $handler_id, array &$data)
    {
        midcom_show_style('net-nemein-rss-feeds-list-header');

        foreach ($this->feeds as $feed) {
            $data['feed'] = $feed;
            $data['feed_category'] = 'feed:' . md5($feed->url);
            $data['feed_toolbar'] = $this->create_toolbar($feed);
            midcom_show_style('net-nemein-rss-feeds-list-item');
        }

        midcom_show_style('net-nemein-rss-feeds-list-footer');
    }

    private function create_toolbar(net_nemein_rss_feed_dba $feed) : midcom_helper_toolbar
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
