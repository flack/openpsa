<?php
/**
 * @package org.openpsa.notifications
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @property integer $id Local non-replication-safe database identifier
 * @property string $component Component that sent the notification
 * @property string $componentaction Component's action that sent the notification
 * @property integer $recipient Recipient of the notification
 * @property integer $sender Sender of the notification in case an user action caused it
 * @property string $title Title for the notification
 * @property string $abstract Short description of the notification
 * @property string $content Full notification text
 * @property string $objectguid GUID of object the notification is about
 * @property string $guid
 * @package org.openpsa.notifications
 */
class org_openpsa_notifications_notification_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_notifications_notification';
}
