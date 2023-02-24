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
    use net_nehmer_blog_handler;

    /**
     * @var midcom_db_article[]
     */
    private $_articles;

    private UniversalFeedCreator $_feed;

    private $category;

    /**
     * Shows the autoindex list. Nothing to do in the handle phase except setting last modified
     * dates.
     */
    public function _handler_feed(string $handler_id, array $args, array &$data)
    {
        midcom::get()->cache->content->content_type("text/xml; charset=UTF-8");
        midcom::get()->skip_page_style = true;

        // Prepare control structures
        $data['datamanager'] = new datamanager($data['schemadb']);

        // Get the articles,
        $qb = midcom_db_article::new_query_builder();
        $this->article_qb_constraints($qb);

        $qb->add_order('metadata.published', 'DESC');

        if ($handler_id == 'feed-category-rss2') {
            // This is not a predefined category from configuration, check if site maintainer allows us to show it
            if (   !in_array($args[0], $data['categories'])
                && !$this->_config->get('categories_custom_enable')) {
                throw new midcom_error('Custom category support is disabled');
            }

            // TODO: Check for ".xml" suffix
            $this->category = trim(strip_tags($args[0]));

            $this->apply_category_constraint($qb, $this->category);
        }

        $qb->set_limit($this->_config->get('rss_count'));

        $this->_articles = $qb->execute();

        // Prepare the feed (this will also validate the handler_id)
        $this->_create_feed();

        midcom::get()->metadata->set_request_metadata($this->get_last_modified(), $this->_topic->guid);
    }

    /**
     * Creates the Feedcreator instance.
     */
    private function _create_feed()
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
    }

    /**
     * Displays the feed
     */
    public function _show_feed(string $handler_id, array &$data)
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
            case 'feed-category-rss2':
                $this->_feed->title = sprintf($this->_l10n->get('%s category %s'), $this->_feed->title, $this->category);
                $this->_feed->syndicationURL = "{$this->_feed->link}feeds/category/{$this->category}";
                // Fall-through

            case 'feed-rss2':
                echo $this->_feed->createFeed('RSS2.0');
                break;

            case 'feed-rss1':
                $this->_feed->syndicationURL = $this->_feed->link . 'rss1.xml';
                echo $this->_feed->createFeed('RSS1.0');
                break;

            case 'feed-rss091':
                echo $this->_feed->createFeed('RSS0.91');
                break;

            case 'feed-atom':
                $this->_feed->syndicationURL = $this->_feed->link . 'atom.xml';
                echo $this->_feed->createFeed('ATOM');
                break;
        }
    }

    /**
     * Shows a simple available-feeds page.
     */
    public function _handler_index()
    {
        $this->set_active_leaf(net_nehmer_blog_navigation::LEAFID_FEEDS);
        midcom::get()->metadata->set_request_metadata($this->_topic->metadata->revised, $this->_topic->guid);

        return $this->show('feeds');
    }
}
