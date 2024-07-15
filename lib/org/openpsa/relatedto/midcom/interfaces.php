<?php
/**
 * @package org.openpsa.relatedto
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA relatedto library, handled saving and retrieving "related to" information
 *
 * Startup loads main class, which is used for all operations.
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_interface extends midcom_baseclasses_components_interface
{
    public function _on_watched_dba_create(midcom_core_dbaobject $object)
    {
        //Check if we have data in session, if so use that.
        $session = new midcom_services_session('org.openpsa.relatedto');
        if ($session->exists('relatedto2get_array')) {
            $relatedto_arr = $session->get('relatedto2get_array');
            $session->remove('relatedto2get_array');
        } else {
            $relatedto_arr = org_openpsa_relatedto_plugin::get2relatedto();
        }
        foreach ($relatedto_arr as $rel) {
            $rel->fromClass = $object::class;
            $rel->fromGuid = $object->guid;
            if (!$rel->id) {
                $rel->create();
            } else {
                //In theory we should not ever hit this, but better to be sure.
                $rel->update();
            }
        }
    }

    /**
     * Ensure relatedto links pointing to an object are deleted when the object is
     */
    public function _on_watched_dba_delete(midcom_core_dbaobject $object)
    {
        $qb = org_openpsa_relatedto_dba::new_query_builder();
        $qb->begin_group('OR');
        $qb->add_constraint('fromGuid', '=', $object->guid);
        $qb->add_constraint('toGuid', '=', $object->guid);
        $qb->end_group();
        if ($qb->count_unchecked() == 0) {
            return;
        }
        midcom::get()->auth->request_sudo($this->_component);
        foreach ($qb->execute() as $link) {
            $link->delete();
        }
        midcom::get()->auth->drop_sudo();
    }
}
