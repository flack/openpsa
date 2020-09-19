<?php
/**
 * @package org.openpsa.notifications
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class for notifying users of different events. Usage is reasonably straightforward:
 *
 * <code>
 * // Populate the message
 * $message = [];
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
    public static function notify(string $component_action, string $recipient, array $message)
    {
        // Parse action to component and action
        $action_parts = explode(':', $component_action);
        if (count($action_parts) != 2) {
            return false;
        }

        $component = $action_parts[0];
        $action = $action_parts[1];

        // TODO: Should we sudo here to ensure getting correct prefs regardless of ACLs?
        try {
            $recipient = midcom_db_person::get_cached($recipient);
        } catch (midcom_error $e) {
            return false;
        }

        // Find in which ways to notify the user
        $notification_type = self::_merge_notification_preferences($component, $action, $recipient);
        if ($notification_type == 'none') {
            // User doesn't wish to be notified
            return true;
        }

        // Add the action to the message
        $message['action'] = $component_action;

        // Figure out notification rendering handler
        $class_name = 'org_openpsa_notifications_notifier_' . $notification_type;
        if (!class_exists($class_name)) {
            return false;
        }
        $notifier = new $class_name();

        // Send the type requested by user
        debug_add("Notifying {$recipient->guid} with type {$notification_type}");

        $ret = $notifier->send($recipient, $message);
        if ($ret) {
            $l10n = midcom::get()->i18n->get_l10n('org.openpsa.notifications');
            midcom::get()->uimessages->add($l10n->get('org.openpsa.notifications'), sprintf($l10n->get('notification sent to %s'), $recipient->name));
        }
        return $ret;
    }

    /**
     * Find out how a person prefers to get the event notification
     *
     * @param string $component Component name
     * @param string $action Event name
     * @param midcom_db_person $recipient The receiving person
     * @return string option supported by user
     */
    private static function _merge_notification_preferences(string $component, string $action, midcom_db_person $recipient)
    {
        $preference = 'none';

        // If user has preference for this message, we use that
        $personal_preferences = $recipient->list_parameters('org.openpsa.notifications');
        if (   !empty($personal_preferences)
            && array_key_exists("{$component}:{$action}", $personal_preferences)) {
            return $personal_preferences[$action];
        }

        // Seek possible preferences for this action from user's groups
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->get_doctrine()
            ->leftJoin('midgard_group', 'g', Join::WITH, 'g.guid = c.parentguid')
            ->leftJoin('midgard_member', 'm', Join::WITH, 'm.gid = g.id')
            ->where('m.uid = :uid')
            ->setParameter('uid', $recipient->id);

        $qb->add_constraint('domain', '=', 'org.openpsa.notifications');
        $qb->add_constraint('name', '=', "{$component}:{$action}");
        $group_preferences = $qb->execute();

        if (!empty($group_preferences)) {
            return $group_preferences[0]->value;
        }

        // Fall back to component defaults
        $customdata = midcom::get()->componentloader->get_all_manifest_customdata('org.openpsa.notifications');
        if (!empty($customdata[$component][$action]['default'])) {
            $preference = $customdata[$component][$action]['default'];
        }

        return $preference;
    }

    public function load_datamanager() : datamanager
    {
        $schema = [
            'description' => 'notifications',
            'fields'      => []
        ];
        $notifiers = $this->_list_notifiers();

        // Load actions of various components
        $customdata = midcom::get()->componentloader->get_all_manifest_customdata('org.openpsa.notifications');

        foreach ($customdata as $component => $actions) {
            $i = 0;
            $total = count($actions);
            foreach ($actions as $action => $settings) {
                $action_key = "{$component}:{$action}";
                $field_config = [
                    'title'   => $this->_i18n->get_string("action {$action}", $component),
                    'storage' => [
                        'location' => 'configuration',
                        'domain'   => 'org.openpsa.notifications',
                        'name'     => $action_key,
                    ],
                    'type'    => 'select',
                    'widget'  => 'radiocheckselect',
                    'type_config' => [
                        'options' => $notifiers,
                    ],
                ];
                if (!empty($settings['default'])) {
                    $field_config['default'] = $settings['default'];
                }
                if ($i == 0) {
                    $field_config['start_fieldset'] = [
                        'title' => $this->_i18n->get_string($component, $component),
                        'css_group' => 'area',
                    ];
                }
                if (++$i == $total) {
                    $field_config['end_fieldset'] = '';
                }
                $schema['fields'][str_replace([':', '.'], '_', $action_key)] = $field_config;
            }
        }
        return new datamanager(new schemadb(['default' => $schema]));
    }

    private function _list_notifiers() : array
    {
        // TODO: Figure out which notifiers are possible
        return [
            ''         => $this->_l10n->get('inherit'),
            'none'     => $this->_l10n->get('none'),
            'email'    => $this->_l10n->get('email'),
        ];
    }
}
