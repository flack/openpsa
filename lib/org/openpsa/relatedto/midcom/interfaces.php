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
    public function __construct()
    {
        $this->_autoload_files = array('dba.php');
    }

    public function _on_watched_dba_create($object)
    {
        $ret = array();
        //Check if we have data in session, if so use that.
        $session = new midcom_services_session('org.openpsa.relatedto');
        if ($session->exists('relatedto2get_array'))
        {
            $relatedto_arr = $session->get('relatedto2get_array');
            $session->remove('relatedto2get_array');
        }
        else
        {
            $relatedto_arr = org_openpsa_relatedto_plugin::get2relatedto();
        }
        foreach ($relatedto_arr as $k => $rel)
        {
            $ret[$k] = array ('stat' => false, 'method' => false, 'obj' => false);
            $rel->fromClass = get_class($object);
            $rel->fromGuid = $object->guid;
            if (!$rel->id)
            {
                $ret[$k]['method'] = 'create';
                $ret[$k]['stat'] = $rel->create();
            }
            else
            {
                //In theory we should not ever hit this, but better to be sure.
                $ret[$k]['method'] = 'update';
                $ret[$k]['stat'] = $rel->update();
            }
            $ret[$k]['obj'] = $rel;
        }
    }

    /**
     * Ensure relatedto links pointing to an object are deleted when the object is
     */
    public function _on_watched_dba_delete($object)
    {
        $qb = org_openpsa_relatedto_dba::new_query_builder();
        $qb->begin_group('OR');
            $qb->add_constraint('fromGuid', '=', $object->guid);
            $qb->add_constraint('toGuid', '=', $object->guid);
        $qb->end_group();
        if ($qb->count_unchecked() == 0)
        {
            return;
        }
        midcom::get('auth')->request_sudo($this->_component);
        $links = $qb->execute();
        foreach ($links as $link)
        {
            $link->delete();
        }
        midcom::get('auth')->drop_sudo();
    }

    /**
     * Support for contacts person merge
     */
    function org_openpsa_contacts_duplicates_merge_person(&$person1, &$person2, $mode)
    {
        switch($mode)
        {
            case 'all':
                break;
            case 'future':
                // Relatedto does not have future references so we have nothing to transfer...
                return true;

            default:
                // Mode not implemented
                debug_add("mode {$mode} not implemented", MIDCOM_LOG_ERROR);
                return false;
        }

        $qb = org_openpsa_relatedto_dba::new_query_builder();
        $qb->begin_group('OR');
            $qb->add_constraint('fromGuid', '=', $person2->guid);
            $qb->add_constraint('toGuid', '=', $person2->guid);
        $qb->end_group();
        $links = $qb->execute();
        if ($links === false)
        {
            // QB Error
            return false;
        }
        foreach ($links as $link)
        {
            if ($link->fromGuid == $person2->guid)
            {
                debug_add("Transferred link->fromGuid #{$link->id} to person #{$person1->id} (from {$link->fromGuid})");
                $link->fromGuid = $person1->guid;
            }
            if ($link->toGuid == $person2->guid)
            {
                debug_add("Transferred link->toGuid #{$link->id} to person #{$person1->id} (from {$link->toGuid})");
                $link->toGuid = $person1->guid;
            }
        }

        // TODO: Check for duplicates and remove those (also from the links array...)

        // Save updates to remaining links
        foreach ($links as $link)
        {
            if (!$link->update())
            {
                // Failure updating
                return false;
            }
        }

        // TODO: 1.8 metadata format support

        // All done
        return true;
    }
}
?>