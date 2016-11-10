<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
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
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_tagged($handler_id, array $args, array &$data)
    {
        $data['tag'] = $args[0];

        // Get wiki page GUIDs from tag links
        $mc = net_nemein_tag_link_dba::new_collector('fromClass', 'net_nemein_wiki_wikipage');
        $mc->add_constraint('tag.tag', '=', $data['tag']);
        $wikipage_guids = $mc->get_values('fromGuid');

        if (empty($wikipage_guids)) {
            throw new midcom_error_notfound("No wiki pages tagged with {$data['tag']}");
        }

        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', 'INTREE', $this->_topic->id);
        $qb->add_constraint('guid', 'IN', $wikipage_guids);
        $qb->add_order('metadata.score', 'DESC');
        $data['wikipages'] = $qb->execute();
        if (count($data['wikipages']) == 0) {
            throw new midcom_error_notfound("No wiki pages tagged with {$data['tag']}");
        }

        $data['view_title'] = sprintf($this->_l10n->get('pages tagged with %s in %s'), $data['tag'], $this->_topic->extra);
        midcom::get()->head->set_pagetitle($data['view_title']);

        $this->add_breadcrumb("tags/{$data['tag']}/", $data['view_title']);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_tagged($handler_id, array &$data)
    {
        midcom_show_style('view-tagged');
    }
}
