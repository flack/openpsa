<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: interfaces.php 17358 2008-09-03 12:21:13Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wiki MidCOM interface class.
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    function __construct()
    {
        $this->_autoload_libraries = array
        (
            'midcom.helper.datamanager2',
        );
    }

    /**
     * Iterate over all wiki pages and create index record using the datamanager2 indexer
     * method.
     */
    function _on_reindex($topic, $config, &$indexer)
    {
        $qb = net_nemein_wiki_wikipage::new_query_builder();
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

            foreach ($result as $wikipage)
            {
                if (! $datamanager->autoset_storage($wikipage))
                {
                    debug_add("Warning, failed to initialize datamanager for Wiki page {$wikipage->id}. Skipping it.", MIDCOM_LOG_WARN);
                    continue;
                }

                net_nemein_wiki_viewer::index($datamanager, $indexer, $topic);
            }
        }

        return true;
    }

    function _on_resolve_permalink($topic, $config, $guid)
    {
        $article = new midcom_db_article($guid);
        if (   ! $article
            || $article->topic != $topic->id)
        {
            return null;
        }
        if ($article->name == 'index')
        {
            return '';
        }
        return "{$article->name}/";
    }

    /**
     * Check whether given wikiword is free in given node
     *
     * Returns true if word is free, false if reserved
     */
    function node_wikiword_is_free(&$node, $wikiword)
    {
        if (empty($node))
        {
            //Invalid node
            debug_add('given node is not valid', MIDCOM_LOG_ERROR);
            return false;
        }
        $wikiword_name = midcom_generate_urlname_from_string($wikiword);
        $qb = new midgard_query_builder('midgard_article');
        $qb->add_constraint('topic', '=', $node[MIDCOM_NAV_OBJECT]->id);
        $qb->add_constraint('name', '=', $wikiword_name);
        $ret = @$qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            //Match found, word is reserved
            debug_add("QB found matches for name '{$wikiword_name}' in topic #{$node[MIDCOM_NAV_OBJECT]->id}, given word '{$wikiword}' is reserved", MIDCOM_LOG_INFO);
            //sprint_r is not part of MidCOM helpers
            ob_start();
            print_r($ret);
            $ret_r = ob_get_contents();
            ob_end_clean();
            debug_add("QB results:\n===\n{$ret_r}===\n");
            return false;
        }
        return true;
    }
}

?>