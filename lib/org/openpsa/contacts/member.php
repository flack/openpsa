<?php
/**
 * @package org.openpsa.contacts
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @property integer $id Local non-replication-safe database identifier
 * @property integer $uid Identifier of the user that belongs to a group
 * @property integer $gid Identifier of the group that the user belongs to
 * @property string $extra Additional information about the membership
 * @property string $guid
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_member_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_member';
}
