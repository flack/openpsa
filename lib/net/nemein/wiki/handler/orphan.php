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
    public function _handler_orphan(array &$data)
    {
        $data['view_title'] = sprintf($this->_l10n->get('orphaned pages in wiki %s'), $this->_topic->extra);
        midcom::get()->head->set_pagetitle($data['view_title']);
        $this->_node_toolbar->hide_item('orphans/');

        $data['orphans'] = [];
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_constraint('name', '<>', 'index');
        $qb->add_order('name');

        foreach ($qb->execute() as $wikipage) {
            $link_qb = net_nemein_wiki_link_dba::new_query_builder();
            $link_qb->add_constraint('topage', '=', $wikipage->title);
            $links = $link_qb->count_unchecked();

            if ($links == 0) {
                $data['orphans'][] = $wikipage;
            }
        }

        $this->add_breadcrumb('', $data['view_title']);

        return $this->show('view-orphans');
    }
}
