<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Blog MidCOM interface class.
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver
{
    /**
     * Iterate over all articles and create index record using the datamanager indexer method.
     */
    public function _on_reindex($topic, midcom_helper_configuration $config, midcom_services_indexer $indexer)
    {
        if ($config->get('disable_indexing')) {
            debug_add("The topic {$topic->id} is not to be indexed, skipping indexing.");
        } else {
            $qb = midcom::get()->dbfactory->new_query_builder(midcom_db_article::class);
            $qb->add_constraint('topic', '=', $topic->id);

            $dm = datamanager::from_schemadb($config->get('schemadb'));

            foreach ($qb->execute() as $article) {
                try {
                    $dm->set_storage($article);
                } catch (midcom_error $e) {
                    $e->log(MIDCOM_LOG_WARN);
                    continue;
                }
                net_nehmer_blog_viewer::index($dm, $indexer, $topic);
            }
        }

        return true;
    }

    /**
     * Try to map the guid to an article of out topic.
     */
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object) : ?string
    {
        if (!($object instanceof midcom_db_article)) {
            return null;
        }
        $config = $this->get_config_for_topic($topic);
        if ($config->get('disable_permalinks')) {
            return null;
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

    public function get_opengraph_default(midcom_core_dbaobject $object) : string
    {
        if (midcom::get()->dbfactory->is_a($object, 'midgard_topic')) {
            return 'blog';
        }

        return 'article';
    }
}
