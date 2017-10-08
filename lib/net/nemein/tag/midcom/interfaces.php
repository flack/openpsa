<?php
/**
 * @package net.nemein.tag
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Tag handling library interface class
 *
 * @package net.nemein.tag
 */
class net_nemein_tag_interface extends midcom_baseclasses_components_interface
{
    /**
     * Ensure tag links pointing to an object are deleted when the object is
     */
    public function _on_watched_dba_delete($object)
    {
        $qb = net_nemein_tag_link_dba::new_query_builder();
        $qb->add_constraint('fromGuid', '=', $object->guid);
        if ($qb->count_unchecked() == 0) {
            return;
        }
        midcom::get()->auth->request_sudo($this->_component);
        foreach ($qb->execute() as $tag_link) {
            $tag_link->delete();
        }
        midcom::get()->auth->drop_sudo();
    }
}
