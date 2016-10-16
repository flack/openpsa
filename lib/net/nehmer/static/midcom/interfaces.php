<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * n.n.static MidCOM interface class.
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver
{
    /**
     * {@inheritdoc}
     */
    public function _on_watched_dba_delete($object)
    {
        midcom::get()->auth->request_sudo($this->_component);
        // Delete all the links pointing to the article
        $qb = net_nehmer_static_link_dba::new_query_builder();
        $qb->add_constraint('article', '=', $object->id);
        $links = $qb->execute_unchecked();

        foreach ($links as $link)
        {
            $link->delete();
        }
        midcom::get()->auth->drop_sudo();
    }

    /**
     * Iterate over all articles and create index record using the datamanager indexer method.
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        if (is_null($config->get('symlink_topic')))
        {
            $qb = midcom::get()->dbfactory->new_query_builder('midcom_db_article');
            $qb->add_constraint('topic', '=', $topic->id);
            $result = $qb->execute();

            $schemadb = midcom_helper_datamanager2_schema::load_database($config->get('schemadb'));
            $datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

            foreach ($result as $article)
            {
                if (! $datamanager->autoset_storage($article))
                {
                    debug_add("Warning, failed to initialize datamanager for Article {$article->id}. Skipping it.", MIDCOM_LOG_WARN);
                    continue;
                }

                net_nehmer_static_viewer::index($datamanager, $indexer, $topic);
            }
        }
        else
        {
            debug_add("The topic {$topic->id} is symlinked to another topic, skipping indexing.");
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if (!($object instanceof midcom_db_article))
        {
            return null;
        }
        $config = $this->get_config_for_topic($topic);
        $topic_guid = $config->get('symlink_topic');
        if (   !empty($topic_guid)
            && mgd_is_guid($topic_guid))
        {
            try
            {
                $new_topic = new midcom_db_topic($topic_guid);
                $topic = $new_topic;
            }
            catch (midcom_error $e)
            {
                $e->log();
            }
        }
        if ($object->topic != $topic->id)
        {
            return null;
        }

        if (   $object->name == 'index'
            && !$config->get('autoindex'))
        {
            return '';
        }

        return "{$object->name}/";
    }

    public function get_opengraph_default($object)
    {
        return 'article';
    }
}
