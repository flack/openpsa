<?php
/**
 * @package org.openpsa.core
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.core ACL synchronizer.
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_acl_synchronizer
{
    public function write_acls($object, $owner_id, $accesstype)
    {
        if (   empty($owner_id)
            || empty($accesstype))
        {
            return false;
        }

        // TODO: Figure out what kind of write handler we need based on the situation (calendar/document etc)
        return $this->_write_full_midcom_acls($object, $owner_id, $accesstype);
    }

    private function _write_full_midcom_acls($object, $owner_id, $accesstype)
    {
        $owner_object = midcom::get('auth')->get_assignee($owner_id);
        if (empty($owner_object->id))
        {
            debug_add('Given owner was invalid, aborting');
            return false;
        }

        // Clear old ACLs applying to others than current user or selected owner group
        $privileges = $object->get_privileges();
        if ($privileges)
        {
            foreach ($privileges as $privilege)
            {
                if (   $privilege->assignee != midcom::get('auth')->user->id
                    && $privilege->assignee != $owner_id)
                {
                    if (is_array($privilege->assignee))
                    {
                        $assignee_key = $privilege->assignee['identifier'];
                    }
                    else
                    {
                        $assignee_key = $privilege->assignee;
                    }
                    debug_add("Removing privilege {$privilege->privilegename} from {$assignee_key}");
                    $object->unset_privilege($privilege->privilegename, $privilege->assignee);
                }
            }
        }

        // Handle ACL storage
        switch ($accesstype)
        {
            case org_openpsa_core_acl::ACCESS_PUBLIC:
            case org_openpsa_core_acl::ACCESS_AGGREGATED:
                debug_add("Public object, everybody can read");
                $object->set_privilege('midgard:read', 'EVERYONE', MIDCOM_PRIVILEGE_ALLOW);
                $this->_set_attachment_permission($object, 'midgard:read', 'EVERYONE', MIDCOM_PRIVILEGE_ALLOW);
                $object->set_privilege('midgard:owner', $owner_id, MIDCOM_PRIVILEGE_ALLOW);
                break;
            case org_openpsa_core_acl::ACCESS_PRIVATE:
                debug_add("Private object, only user can read and write");
                $object->set_privilege('midgard:read', 'EVERYONE', MIDCOM_PRIVILEGE_DENY);
                $object->set_privilege('midgard:owner', midcom::get('auth')->user->id, MIDCOM_PRIVILEGE_ALLOW);
                $this->_set_attachment_permission($object, 'midgard:read', midcom::get('auth')->user->id, MIDCOM_PRIVILEGE_ALLOW);
                break;
            case org_openpsa_core_acl::ACCESS_WGRESTRICTED:
                debug_add("Restricted object, only workgroup members can read and write. Subscribers can read");
                //fall-through. Once subscriber groups get reimplemented, the appropriate code should be added here
            case org_openpsa_core_acl::ACCESS_WGPRIVATE:
                debug_add("Private object, only workgroup members can read and write");
                $object->set_privilege('midgard:read', 'EVERYONE', MIDCOM_PRIVILEGE_DENY);
                $object->set_privilege('midgard:owner', $owner_id, MIDCOM_PRIVILEGE_ALLOW);
                $this->_set_attachment_permission($object, 'midgard:read', $owner_id, MIDCOM_PRIVILEGE_ALLOW);
                break;
        }
        return true;
    }

    private function _set_attachment_permission($object, $privilege, $assignee, $value)
    {
        $attachments = $object->list_attachments();
        if ($attachments)
        {
            foreach ($attachments as $attachment)
            {
                debug_add("Setting {$value} to privilege {$privilege} for {$assignee} to attachment #{$attachment->id}");
                $attachment->set_privilege($privilege, $assignee, $value);
            }
        }
    }
}
?>