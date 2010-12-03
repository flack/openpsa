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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_rss($handler_id, $args, &$data)
    {
        $_MIDCOM->load_library('de.bitfolge.feedcreator');

        $data['nap'] = new midcom_helper_nav();
        $data['node'] = $data['nap']->get_node($this->_topic->id);

        $_MIDCOM->cache->content->content_type("text/xml; charset=UTF-8");
        $_MIDCOM->header("Content-type: text/xml; charset=UTF-8");

        $_MIDCOM->skip_page_style = true;

        $data['rss_creator'] = new UniversalFeedCreator();
        $data['rss_creator']->title = $data['node'][MIDCOM_NAV_NAME];
        $data['rss_creator']->link = $data['node'][MIDCOM_NAV_FULLURL];
        $data['rss_creator']->syndicationURL = "{$data['node'][MIDCOM_NAV_FULLURL]}rss.xml";
        $data['rss_creator']->cssStyleSheet = false;

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_rss($handler_id, &$data)
    {
        $_MIDCOM->load_library('net.nehmer.markdown');
        
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic.component', '=', $_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT));
        $qb->add_constraint('topic', 'INTREE', $this->_topic->id);
        $qb->add_order('metadata.revised', 'DESC');
        $qb->set_limit($this->_config->get('rss_count'));
        $result = $qb->execute();

        foreach ($result as $wikipage)
        {
            if ($wikipage->topic == $this->_topic->id)
            {
                $node = $data['node'];
            }
            else
            {
                $node = $data['nap']->get_node($wikipage->topic);
            }
            $author = new midcom_db_person($wikipage->metadata->revisor);
            $item = new FeedItem();
            $item->title = $wikipage->title;
            if ($wikipage->name == 'index')
            {
                $item->link = "{$node[MIDCOM_NAV_FULLURL]}";
            }
            else
            {
                $item->link = "{$node[MIDCOM_NAV_FULLURL]}{$wikipage->name}/";
            }
            $item->date = $wikipage->metadata->revised;
            $item->author = $author->name;
            $item->description = Markdown(preg_replace_callback($this->_config->get('wikilink_regexp'), array($wikipage, 'replace_wikiwords'), $wikipage->content));
            $data['rss_creator']->addItem($item);
        }
        $data['rss'] = $data['rss_creator']->createFeed('RSS2.0');

        echo $data['rss'];
    }
}
?>