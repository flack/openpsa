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
implements midcom_services_permalinks_resolver
{
    /**
     * {@inheritdoc}
     */
    public function _on_watched_dba_delete($object)
    {
        midcom::get()->auth->request_sudo($this->_component);
        // Delete all the links pointing to the article
        $qb = net_nehmer_blog_link_dba::new_query_builder();
        $qb->add_constraint('article', '=', $object->id);
        $links = $qb->execute_unchecked();

        foreach ($links as $link) {
            $link->delete();
        }
        midcom::get()->auth->drop_sudo();
    }

    /**
     * Iterate over all articles and create index record using the datamanager indexer method.
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        if ($config->get('symlink_topic')) {
            debug_add("The topic {$topic->id} is symlinked to another topic, skipping indexing.");
        } elseif (!$config->get('disable_indexing')) {
            debug_add("The topic {$topic->id} is not to be indexed, skipping indexing.");
        } else {
            $qb = midcom::get()->dbfactory->new_query_builder('midcom_db_article');
            $qb->add_constraint('topic', '=', $topic->id);
            $result = $qb->execute();

            $schemadb = midcom_helper_datamanager2_schema::load_database($config->get('schemadb'));
            $datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

            foreach ($result as $article) {
                if (!$datamanager->autoset_storage($article)) {
                    debug_add("Warning, failed to initialize datamanager for Article {$article->id}. Skipping it.", MIDCOM_LOG_WARN);
                    continue;
                }

                net_nehmer_blog_viewer::index($datamanager, $indexer, $topic);
            }
        }

        return true;
    }

    /**
     * Try to map the guid to an article of out topic.
     */
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if (!($object instanceof midcom_db_article)) {
            return null;
        }
        $config = $this->get_config_for_topic($topic);
        if ($config->get('disable_permalinks')) {
            return null;
        }

        $topic_guid = $config->get('symlink_topic');
        if (mgd_is_guid($topic_guid)) {
            try {
                $new_topic = new midcom_db_topic($topic_guid);
                $topic = $new_topic;
            } catch (midcom_error $e) {
                $e->log();
            }
        }

        if ($object->topic != $topic->id) {
            return null;
        }

        $arg = $object->name ?: $object->guid;

        if ($config->get('view_in_url')) {
            return "view/{$arg}/";
        }
        return "{$arg}/";
    }

    public function get_opengraph_default($object)
    {
        if (midcom::get()->dbfactory->is_a($object, 'midgard_topic')) {
            return 'blog';
        }

        return 'article';
    }
}
