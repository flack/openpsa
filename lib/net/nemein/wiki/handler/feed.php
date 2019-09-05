<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Response;

/**
 * Wikipage feed handler
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_handler_feed extends midcom_baseclasses_components_handler
{
    /**
     * @return Response
     */
    public function _handler_rss()
    {
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($this->_topic->id);

        $rss_creator = new UniversalFeedCreator();
        $rss_creator->title = $node[MIDCOM_NAV_NAME];
        $rss_creator->link = $node[MIDCOM_NAV_FULLURL];
        $rss_creator->syndicationURL = "{$node[MIDCOM_NAV_FULLURL]}rss.xml";

        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic.component', '=', midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT));
        $qb->add_constraint('topic', 'INTREE', $this->_topic->id);
        $qb->add_order('metadata.revised', 'DESC');
        $qb->set_limit($this->_config->get('rss_count'));

        foreach ($qb->execute() as $wikipage) {
            if ($wikipage->topic == $this->_topic->id) {
                $pagenode = $node;
            } else {
                $pagenode = $nap->get_node($wikipage->topic);
            }
            $item = new FeedItem();
            $item->title = $wikipage->title;
            $item->link = $pagenode[MIDCOM_NAV_FULLURL];
            if ($wikipage->name != 'index') {
                $item->link .= "{$wikipage->name}/";
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
            $rss_creator->addItem($item);
        }

        return new Response($rss_creator->createFeed('RSS2.0'), Response::HTTP_OK, [
            'Content-Type' => 'text/xml; charset=UTF-8'
        ]);
    }
}
