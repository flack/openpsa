<?php
/**
 * @package org.openpsa.notifications
 * @author Henri Bergius, http://bergie.iki.fi
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
    /**
     * Sends a notice to a selected person
     *
     * @param string $component_action Key of the event in format component:event
     * @param string $recipient GUID of the receiving person
     * @param array $message Notification message in array format
     */
    public static function notify($component_action, $recipient, $message)
    {
        // Parse action to component and action
        $action_parts = explode(':', $component_action);
        if (count($action_parts) != 2) {
            return false;
        }

        $component = $action_parts[0];
        $action = $action_parts[1];

        // Find in which ways to notify the user
        $notification_type = self::_merge_notification_prefences($component, $action, $recipient);
        if ($notification_type == 'none') {
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
        debug_add("Notifying {$recipient} with type {$notification_type}");
        $method = "send_{$notification_type}";
        if (!method_exists($notifier, $method)) {
            return false;
        }
        return $notifier->$method($message);
    }

    /**
     * Find out how a person prefers to get the event notification
     *
     * @param string $component Component name
     * @param string $action Event name
     * @param string $recipient GUID of the receiving person
     * @return Array options supported by user
     */
    private static function _merge_notification_prefences($component, $action, $recipient)
    {
        // TODO: Should we sudo here to ensure getting correct prefs regardless of ACLs?
        $preference = 'none';
        try {
            $recipient = new midcom_db_person($recipient);
        } catch (midcom_error $e) {
            return $preference;
        }

        // If user has preference for this message, we use that
        $personal_preferences = $recipient->list_parameters('org.openpsa.notifications');
        if (   count($personal_preferences) > 0
            && array_key_exists("{$component}:{$action}", $personal_preferences)) {
            return $personal_preferences[$action];
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
        foreach ($memberships as $member) {
            try {
                $group = new midcom_db_group($member->gid);
                $qb->add_constraint('parentguid', '=', $group->guid);
            } catch (midcom_error $e) {
                $e->log();
            }
        }
        $qb->end_group();

        $group_preferences = $qb->execute();
        if (count($group_preferences) > 0) {
            return $group_preferences[0]->value;
        }

        // Fall back to component defaults
        $customdata = midcom::get()->componentloader->get_all_manifest_customdata('org.openpsa.notifications');
        if (!empty($customdata[$component][$action]['default'])) {
            $preference = $customdata[$component][$action]['default'];
        }

        return $preference;
    }

    public function load_schemadb()
    {
        $schemadb = array
        (
            'default' => array
            (
                'description' => 'notifications',
                'fields'      => array()
            )
        );
        $schemadb = midcom_helper_datamanager2_schema::load_database($schemadb);
        $notifiers = $this->_list_notifiers();

        // Load actions of various components
        $customdata = midcom::get()->componentloader->get_all_manifest_customdata('org.openpsa.notifications');

        foreach ($customdata as $component => $actions) {
            $i = 0;
            $total = sizeof($actions);
            foreach ($actions as $action => $settings) {
                $action_key = "{$component}:{$action}";
                $field_config = array
                (
                    'title'   => $this->_i18n->get_string("action {$action}", $component),
                    'storage' => array
                    (
                        'location' => 'configuration',
                        'domain'   => 'org.openpsa.notifications',
                        'name'     => $action_key,
                    ),
                    'type'    => 'select',
                    'widget'  => 'radiocheckselect',
                    'type_config' => array
                    (
                        'options' => $notifiers,
                    ),
                );
                if (!empty($settings['default'])) {
                    $field_config['default'] = $settings['default'];
                }
                if ($i == 0) {
                    $field_config['start_fieldset'] = array
                    (
                        'title' => $this->_i18n->get_string($component, $component),
                        'css_group' => 'area',
                    );
                }
                if (++$i == $total) {
                    $field_config['end_fieldset'] = '';
                }

                $schemadb['default']->append_field
                (
                    str_replace(array(':', '.'), '_', $action_key),
                    $field_config
                );
            }
        }
        return $schemadb;
    }

    private function _list_notifiers()
    {
        // TODO: Figure out which notifiers are possible
        return array
        (
            ''         => $this->_l10n->get('inherit'),
            'none'     => $this->_l10n->get('none'),
            'email'    => $this->_l10n->get('email'),
        );
    }
}
