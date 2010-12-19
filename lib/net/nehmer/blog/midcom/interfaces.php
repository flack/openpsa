<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Blog MidCOM interface class.
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    public function __construct()
    {
        define('NET_NEHMER_BLOG_LEAFID_FEEDS', 2);

        $this->_autoload_libraries = array
        (
            'midcom.helper.datamanager2'
        );

        if ($GLOBALS['midcom_config']['positioning_enable'])
        {
            $this->_autoload_libraries[] = 'org.routamc.positioning';
        }
    }

    /**
     * Iterate over all articles and create index record using the datamanager indexer
     * method.
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        if (   is_null($config->get('symlink_topic'))
            && !$config->get('disable_indexing'))
        {
            $qb = $_MIDCOM->dbfactory->new_query_builder('midcom_db_article');
            $qb->add_constraint('topic', '=', $topic->id);
            $result = $qb->execute();

            if ($result)
            {
                $schemadb = midcom_helper_datamanager2_schema::load_database($config->get('schemadb'));
                $datamanager = new midcom_helper_datamanager2_datamanager($schemadb);
                if (! $datamanager)
                {
                    debug_add('Warning, failed to create a datamanager instance with this schemapath:' . $config->get('schemadb'),
                        MIDCOM_LOG_WARN);
                    continue;
                }

                foreach ($result as $article)
                {
                    if (! $datamanager->autoset_storage($article))
                    {
                        debug_add("Warning, failed to initialize datamanager for Article {$article->id}. Skipping it.", MIDCOM_LOG_WARN);
                        continue;
                    }

                    net_nehmer_blog_viewer::index($datamanager, $indexer, $topic);
                }
            }
        }
        elseif (is_null($config->get('symlink_topic'))
                && !$config->get('disable_search'))
        {
            debug_add("The topic {$topic->id} is is not to be indexed, skipping indexing.");
        }
        else
        {
            debug_add("The topic {$topic->id} is symlinked to another topic, skipping indexing.");
        }

        return true;
    }

    /**
     * Simple lookup method which tries to map the guid to an article of out topic.
     */
    public function _on_resolve_permalink($topic, $config, $guid)
    {
        if (   isset($config)
            && $config->get('disable_permalinks'))
        {
            return null;
        }

        $topic_guid = $config->get('symlink_topic');
        if (   !empty($topic_guid)
            && mgd_is_guid($topic_guid))
        {
            $new_topic = new midcom_db_topic($topic_guid);
            // Validate topic.

            if (   is_object($new_topic)
                && isset($new_topic->guid)
                && empty($new_topic->guid))
            {
                $topic = $new_topic;
            }
        }

        $article = new midcom_db_article($guid);
        if (   ! $article
            || $article->topic != $topic->id)
        {
            return null;
        }
        $arg = $article->name ? $article->name : $article->guid;

        if ($config->get('view_in_url'))
        {
            return "view/{$arg}/";
        }
        else
        {
            return "{$arg}/";
        }
    }

    public function get_opengraph_default($object)
    {
        if ($_MIDCOM->dbfactory->is_a($object, 'midgard_topic'))
        {
            return 'blog';
        }

        return 'article';
    }
}
?>
