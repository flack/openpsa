<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Wiki MidCOM interface class.
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver
{
    /**
     * @inheritdoc
     */
    public function _on_reindex($topic, midcom_helper_configuration $config, midcom_services_indexer $indexer)
    {
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $topic->id);

        if ($result = $qb->execute()) {
            $datamanager = datamanager::from_schemadb($config->get('schemadb'));

            foreach ($result as $wikipage) {
                try {
                    $datamanager->set_storage($wikipage);
                } catch (midcom_error $e) {
                    $e->log(MIDCOM_LOG_WARN);
                    continue;
                }

                net_nemein_wiki_viewer::index($datamanager, $indexer, $topic);
            }
        }

        return true;
    }

    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object) : ?string
    {
        if (   $object instanceof midcom_db_article
            && $topic->id == $object->topic) {
            if ($object->name == 'index') {
                return '';
            }
            return "{$object->name}/";
        }

        return null;
    }

    /**
     * Check whether given wikiword is free in given node
     */
    public static function node_wikiword_is_free(array $node, string $wikiword) : bool
    {
        if (empty($node)) {
            //Invalid node
            debug_add('given node is not valid', MIDCOM_LOG_ERROR);
            return false;
        }
        $wikiword_name = midcom_helper_misc::urlize($wikiword);
        $qb = new midgard_query_builder('midgard_article');
        $qb->add_constraint('topic', '=', $node[MIDCOM_NAV_OBJECT]->id);
        $qb->add_constraint('name', '=', $wikiword_name);
        if ($ret = $qb->execute()) {
            //Match found, word is reserved
            debug_add("QB found matches for name '{$wikiword_name}' in topic #{$node[MIDCOM_NAV_OBJECT]->id}, given word '{$wikiword}' is reserved", MIDCOM_LOG_INFO);
            debug_print_r('QB results:', $ret);
            return false;
        }
        return true;
    }
}
