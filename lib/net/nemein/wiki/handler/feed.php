<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wikipage feed handler
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_handler_feed extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_rss($handler_id, array $args, array &$data)
    {
        $data['nap'] = new midcom_helper_nav();
        $data['node'] = $data['nap']->get_node($this->_topic->id);

        midcom::get()->cache->content->content_type("text/xml; charset=UTF-8");
        midcom::get()->skip_page_style = true;

        $data['rss_creator'] = new UniversalFeedCreator();
        $data['rss_creator']->title = $data['node'][MIDCOM_NAV_NAME];
        $data['rss_creator']->link = $data['node'][MIDCOM_NAV_FULLURL];
        $data['rss_creator']->syndicationURL = "{$data['node'][MIDCOM_NAV_FULLURL]}rss.xml";
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_rss($handler_id, array &$data)
    {
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic.component', '=', midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT));
        $qb->add_constraint('topic', 'INTREE', $this->_topic->id);
        $qb->add_order('metadata.revised', 'DESC');
        $qb->set_limit($this->_config->get('rss_count'));

        foreach ($qb->execute() as $wikipage) {
            if ($wikipage->topic == $this->_topic->id) {
                $node = $data['node'];
            } else {
                $node = $data['nap']->get_node($wikipage->topic);
            }
            $item = new FeedItem();
            $item->title = $wikipage->title;
            if ($wikipage->name == 'index') {
                $item->link = "{$node[MIDCOM_NAV_FULLURL]}";
            } else {
                $item->link = "{$node[MIDCOM_NAV_FULLURL]}{$wikipage->name}/";
            }
            $item->date = $wikipage->metadata->revised;
            try {
                $author = new midcom_db_person($wikipage->metadata->revisor);
                $item->author = $author->name;
            } catch (midcom_error $e) {
                $e->log();
            }

            $parser = new net_nemein_wiki_parser($wikipage);
            $item->description = $parser->get_html();
            $data['rss_creator']->addItem($item);
        }
        $data['rss'] = $data['rss_creator']->createFeed('RSS2.0');

        echo $data['rss'];
    }
}
