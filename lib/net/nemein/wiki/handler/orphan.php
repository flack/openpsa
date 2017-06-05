<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wiki orphan handler
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_handler_orphan extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_orphan($handler_id, array $args, array &$data)
    {
        $data['wiki_name'] = $this->_topic->extra;

        $data['view_title'] = sprintf($this->_l10n->get('orphaned pages in wiki %s'), $data['wiki_name']);
        midcom::get()->head->set_pagetitle($data['view_title']);
        $this->_node_toolbar->hide_item('orphans/');

        $data['orphans'] = [];
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_constraint('name', '<>', 'index');
        $qb->add_order('name');
        $wikipages = $qb->execute();

        foreach ($wikipages as $wikipage) {
            $link_qb = net_nemein_wiki_link_dba::new_query_builder();
            $link_qb->add_constraint('topage', '=', $wikipage->title);
            $links = $link_qb->count_unchecked();

            if ($links == 0) {
                $data['orphans'][] = $wikipage;
            }
        }

        $this->add_breadcrumb('orphans/', $data['view_title']);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_orphan($handler_id, array &$data)
    {
        midcom_show_style('view-orphans');
    }
}
