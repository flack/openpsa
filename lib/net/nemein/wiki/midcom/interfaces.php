<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

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
    public function _on_reindex($topic, $config, &$indexer)
    {
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $topic->id);
        $result = $qb->execute();

        if ($result)
        {
            $schemadb = midcom_helper_datamanager2_schema::load_database($config->get('schemadb'));
            $datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

            foreach ($result as $wikipage)
            {
                if (!$datamanager->autoset_storage($wikipage))
                {
                    debug_add("Warning, failed to initialize datamanager for Wiki page {$wikipage->id}. Skipping it.", MIDCOM_LOG_WARN);
                    continue;
                }

                net_nemein_wiki_viewer::index($datamanager, $indexer, $topic);
            }
        }

        return true;
    }

    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if (   $object instanceof midcom_db_article
            && $topic->id == $object->topic)
        {
            if ($object->name == 'index')
            {
                return '';
            }
            return "{$object->name}/";
        }

        return null;
    }

    /**
     * Check whether given wikiword is free in given node
     *
     * Returns true if word is free, false if reserved
     */
    public static function node_wikiword_is_free($node, $wikiword)
    {
        if (empty($node))
        {
            //Invalid node
            debug_add('given node is not valid', MIDCOM_LOG_ERROR);
            return false;
        }
        $generator = midcom::get()->serviceloader->load('midcom_core_service_urlgenerator');
        $wikiword_name = $generator->from_string($wikiword);
        $qb = new midgard_query_builder('midgard_article');
        $qb->add_constraint('topic', '=', $node[MIDCOM_NAV_OBJECT]->id);
        $qb->add_constraint('name', '=', $wikiword_name);
        $ret = $qb->execute();
        if (!empty($ret))
        {
            //Match found, word is reserved
            debug_add("QB found matches for name '{$wikiword_name}' in topic #{$node[MIDCOM_NAV_OBJECT]->id}, given word '{$wikiword}' is reserved", MIDCOM_LOG_INFO);
            debug_print_r('QB results:', $ret);
            return false;
        }
        return true;
    }
}
