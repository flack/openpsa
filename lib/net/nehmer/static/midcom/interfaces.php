<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * n.n.static MidCOM interface class.
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver
{
    /**
     * Iterate over all articles and create index record using the datamanager indexer method.
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        $qb = midcom::get()->dbfactory->new_query_builder('midcom_db_article');
        $qb->add_constraint('topic', '=', $topic->id);
        $result = $qb->execute();

        $datamanager = datamanager::from_schemadb($config->get('schemadb'));

        foreach ($result as $article) {
            try {
                $datamanager->set_storage($article);
            } catch (midcom_error $e) {
                $e->log(MIDCOM_LOG_WARN);
                continue;
            }

            net_nehmer_static_viewer::index($datamanager, $indexer, $topic);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if (!($object instanceof midcom_db_article)) {
            return null;
        }
        if ($object->topic != $topic->id) {
            return null;
        }

        $config = $this->get_config_for_topic($topic);
        if (   $object->name == 'index'
            && !$config->get('autoindex')) {
            return '';
        }

        return "{$object->name}/";
    }

    public function get_opengraph_default($object)
    {
        return 'article';
    }
}
