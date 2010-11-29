<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id$
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wikipage "pages by tag" handler
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_handler_tag extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_tagged($handler_id, $args, &$data)
    {
        $data['tag'] = $args[0];

        // Get wiki page GUIDs from tag links
        $mc = net_nemein_tag_link_dba::new_collector('metadata.deleted', false);
        $mc->add_value_property('fromGuid');
        $mc->add_constraint('fromClass', '=', 'net_nemein_wiki_wikipage');
        $mc->add_constraint('tag.tag', '=', $data['tag']);
        $mc->execute();
        $tags = $mc->list_keys();

        if (count($tags) == 0)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "No wiki pages tagged with {$data['tag']}");
            // This will exit
        }

        $wikipage_guids = array();
        foreach ($tags as $tag_guid => $values)
        {
            $wikipage_guids[] = $mc->get_subkey($tag_guid, 'fromGuid');
        }

        if (empty($wikipage_guids))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "No wiki pages tagged with {$data['tag']}");
            // This will exit
        }

        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', 'INTREE', $this->_topic->id);
        $qb->add_constraint('guid', 'IN', $wikipage_guids);
        $qb->add_order('metadata.score', 'DESC');
        $data['wikipages'] = $qb->execute();
        if (count($data['wikipages']) == 0)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "No wiki pages tagged with {$data['tag']}");
            // This will exit
        }

        $data['view_title'] = sprintf($this->_request_data['l10n']->get('pages tagged with %s in %s'), $data['tag'], $this->_topic->extra);
        $_MIDCOM->set_pagetitle($data['view_title']);

        $this->add_breadcrumb("tags/{$data['tag']}/", $data['view_title']);

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_tagged($handler_id, &$data)
    {
        midcom_show_style('view-tagged');
    }
}
?>