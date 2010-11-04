<?php
/**
 * @package org.openpsa.notifications
 * @author Henri Bergius, http://bergie.iki.fi
 * @version $Id: main.php 23015 2009-07-28 08:50:55Z flack $
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Class for notifying users of different events. Usage is reasonably straightforward:
 *
 * <code>
 * // Populate the message
 * $message = array();
 *
 * // Add content for long notification formats (email and RSS)
 * $message['title'] = 'Something has happened';
 * $message['content'] = 'Somebody did something...';
 *
 * // Add content for short notification formats (XMPP, SMS, Growl)
 * $message['abstract'] = 'Somebody did something';
 *
 * // Send it
 * org_openpsa_notifications::notify('net.example.component:some_action', $recipient_guid, $message);
 * </code>
 *
 * If you want users to take some action related to the message, it is a good idea to include
 * URLs in the message.
 *
 * @package org.openpsa.notifications
 */
class org_openpsa_notifications extends midcom_baseclasses_components_purecode
{

    function __construct()
    {
        $this->_component = 'org.openpsa.notifications';

        parent::__construct();
    }

    /**
     * Sends a notice to a selected person
     *
     * @param string $component_action Key of the event in format component:event
     * @param string $recipient GUID of the receiving person
     * @param Array $message Notification message in array format
     */
    function notify($component_action, $recipient, $message)
    {
        // Parse action to component and action
        $action_parts = explode(':', $component_action);
        if (count($action_parts) != 2)
        {
            return false;
        }

        $component = $action_parts[0];
        $action = $action_parts[1];

        // Find in which ways to notify the user
        $notification_type = org_openpsa_notifications::merge_notification_prefences($component, $action, $recipient);
        if ($notification_type == 'none')
        {
            // User doesn't wish to be notified
            return true;
        }

        // Add the action to the message
        $message['action'] = $component_action;

        // Figure out notification rendering handler
        // TODO: Support component-specific renderers via class_exists() or handler-like autoloading
        // For example: if (class_exists('org_openpsa_calendar_notifications'))
        $notifier = new org_openpsa_notifications_notifier($recipient);

        // Send the type requested by user
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("Notifying {$recipient} with type {$notification_type}", MIDCOM_LOG_DEBUG);
        debug_pop();
        $method = "send_{$notification_type}";
        if (!method_exists($notifier, $method))
        {
            return false;
        }
        $notifier->$method($message);
        return true;
    }

    /**
     * Find out how a person prefers to get the event notification
     *
     * @param string $action Key of the event in format component:event
     * @param string $recipient GUID of the receiving person
     * @return Array options supported by user
     */
    function merge_notification_prefences($component, $action, $recipient)
    {
        // TODO: Should we sudo here to ensure getting correct prefs regardless of ACLs?
        $preference = 'none';
        $recipient = new midcom_db_person($recipient);

        if (   !$recipient
            || !isset($recipient->guid)
            || empty($recipient->guid))
        {
            return $preference;
        }

        // If user has preference for this message, we use that
        $personal_preferences = $recipient->list_parameters('org.openpsa.notifications');
        if (   count($personal_preferences) > 0
            && array_key_exists("{$component}:{$action}", $personal_preferences))
        {
            $preference = $personal_preferences[$action];
            return $preference;
        }

        // Fall back to component defaults
        $customdata = $_MIDCOM->componentloader->get_all_manifest_customdata('org.openpsa.notifications');
        if (array_key_exists($component, $customdata))
        {
            if (array_key_exists($action, $customdata[$component]))
            {
                if (array_key_exists('default', $customdata[$component][$action]))
                {
                    $preference = $customdata[$component][$action]['default'];
                }
            }
        }

        // Seek possible preferences for this action from user's groups
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.openpsa.notifications');
        $qb->add_constraint('name', '=', "{$component}:{$action}");

        // Seek user's groups
        $member_qb = midcom_db_member::new_query_builder();
        $member_qb->add_constraint('uid', '=', (int)$recipient->id);
        $memberships = $member_qb->execute();
        $qb->begin_group('OR');
        foreach ($memberships as $member)
        {
            $group = new midcom_db_group($member->gid);
            $qb->add_constraint('parentguid', '=', $group->guid);
        }
        $qb->end_group();

        $group_preferences = $qb->execute();
        if (count($group_preferences) > 0)
        {
            foreach ($group_preferences as $preference)
            {
                $preference = $preference->value;
            }
        }

        return $preference;
    }
}
?>