<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Blog Feed handler
 *
 * Prints the various supported feeds using the FeedCreator library.
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_feed extends midcom_baseclasses_components_handler
{
    /**
     * The articles to display
     *
     * @var Array
     */
    private $_articles = null;

    /**
     * The feedcreator instance used.
     *
     * @var UniversalFeedCreator
     */
    private $_feed = null;

    /**
     * Shows the autoindex list. Nothing to do in the handle phase except setting last modified
     * dates.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_feed($handler_id, array $args, array &$data)
    {
        midcom::get()->cache->content->content_type("text/xml; charset=UTF-8");
        midcom::get()->skip_page_style = true;

        // Prepare control structures
        $data['datamanager'] = new datamanager($data['schemadb']);

        // Get the articles,
        $qb = midcom_db_article::new_query_builder();
        $this->_master->article_qb_constraints($qb, $handler_id);

        $qb->add_order('metadata.published', 'DESC');

        if ($handler_id == 'feed-category-rss2') {
            if (!in_array($args[0], $this->_request_data['categories'])) {
                // This is not a predefined category from configuration, check if site maintainer allows us to show it
                if (!$this->_config->get('categories_custom_enable')) {
                    throw new midcom_error('Custom category support is disabled');
                }
            }

            // TODO: Check for ".xml" suffix
            $this->_request_data['category'] = trim(strip_tags($args[0]));

            $this->_master->apply_category_constraint($qb, $this->_request_data['category']);
        }

        $qb->set_limit($this->_config->get('rss_count'));

        $this->_articles = $qb->execute();

        // Prepare the feed (this will also validate the handler_id)
        $this->_create_feed($handler_id);

        midcom::get()->metadata->set_request_metadata(net_nehmer_blog_viewer::get_last_modified($this->_topic), $this->_topic->guid);
    }

    /**
     * Creates the Feedcreator instance.
     */
    private function _create_feed($handler_id)
    {
        $this->_feed = new UniversalFeedCreator();
        if ($this->_config->get('rss_title')) {
            $this->_feed->title = $this->_config->get('rss_title');
        } else {
            $this->_feed->title = $this->_topic->extra;
        }
        $this->_feed->description = $this->_config->get('rss_description');
        $this->_feed->language = $this->_config->get('rss_language');
        $this->_feed->editor = $this->_config->get('rss_webmaster');
        $this->_feed->link = midcom::get()->get_host_name() . midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

        switch ($handler_id) {
            case 'feed-rss2':
                $this->_feed->syndicationURL = "{$this->_feed->link}rss.xml";
                break;

            case 'feed-rss1':
                $this->_feed->syndicationURL = "{$this->_feed->link}rss1.xml";
                break;

            case 'feed-rss091':
                $this->_feed->syndicationURL = "{$this->_feed->link}rss091.xml";
                break;

            case 'feed-atom':
                $this->_feed->syndicationURL = "{$this->_feed->link}atom.xml";
                break;

            case 'feed-category-rss2':
                $this->_feed->title = sprintf($this->_l10n->get('%s category %s'), $this->_feed->title, $this->_request_data['category']);
                $this->_feed->syndicationURL = "{$this->_feed->link}feeds/category/{$this->_request_data['category']}";
                break;

            default:
                throw new midcom_error("The feed handler {$handler_id} is unsupported");
        }
    }

    /**
     * Displays the feed
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_feed($handler_id, array &$data)
    {
        $data['feedcreator'] = $this->_feed;

        // Add each article now.
        if ($this->_articles) {
            foreach ($this->_articles as $article) {
                $data['datamanager']->set_storage($article);
                $data['article'] = $article;
                midcom_show_style('feeds-item');
            }
        }

        switch ($handler_id) {
            case 'feed-rss2':
            case 'feed-category-rss2':
                echo $this->_feed->createFeed('RSS2.0');
                break;

            case 'feed-rss1':
                echo $this->_feed->createFeed('RSS1.0');
                break;

            case 'feed-rss091':
                echo $this->_feed->createFeed('RSS0.91');
                break;

            case 'feed-atom':
                echo $this->_feed->createFeed('ATOM');
                break;
        }
    }

    /**
     * Shows a simple available-feeds page.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_index($handler_id, array $args, array &$data)
    {
        $this->set_active_leaf(net_nehmer_blog_navigation::LEAFID_FEEDS);
        midcom::get()->metadata->set_request_metadata($this->_topic->metadata->revised, $this->_topic->guid);

        return $this->show('feeds');
    }
}
